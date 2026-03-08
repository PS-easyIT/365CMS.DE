<?php
/**
 * Mail Service – Symfony-basierter SMTP-/Mail-Service
 *
 * Sendet E-Mails über lokale Symfony-Mailer-/Mime-Komponenten aus `CMS/assets/`
 * oder verwendet `mail()` als Fallback, wenn kein SMTP-Host konfiguriert ist.
 *
 * Verwendung:
 *   MailService::getInstance()->send('to@example.com', 'Betreff', '<p>HTML Body</p>');
 *   MailService::getInstance()->sendPlain('to@example.com', 'Betreff', 'Plain text');
 *
 * Konfiguration über Konstanten in config/app.php:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_ENCRYPTION,
 *   SMTP_FROM_EMAIL, SMTP_FROM_NAME
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

if (!defined('ABSPATH')) {
    exit;
}

class MailService
{
    private static ?self $instance = null;

    private readonly string $smtpHost;
    private readonly int    $smtpPort;
    private readonly string $smtpUser;
    private readonly string $smtpPass;
    private readonly string $smtpEncryption;
    private readonly string $fromEmail;
    private readonly string $fromName;
    private readonly bool   $useSmtp;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->smtpHost       = defined('SMTP_HOST')       ? SMTP_HOST       : '';
        $this->smtpPort       = defined('SMTP_PORT')       ? (int) SMTP_PORT : 587;
        $this->smtpUser       = defined('SMTP_USER')       ? SMTP_USER       : '';
        $this->smtpPass       = defined('SMTP_PASS')       ? SMTP_PASS       : '';
        $this->smtpEncryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
        $this->fromEmail      = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'noreply@localhost');
        $this->fromName       = defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : (defined('SITE_NAME')   ? SITE_NAME   : 'CMS');
        $this->useSmtp        = $this->smtpHost !== '';
    }

    /**
     * HTML-E-Mail senden.
     *
     * @param string       $to          Empfänger-Adresse
     * @param string       $subject     Betreff
     * @param string       $htmlBody    HTML-Inhalt
     * @param array<string,string> $headers Zusätzliche Header (z. B. 'Reply-To')
     * @return bool
     */
    public function send(string $to, string $subject, string $htmlBody, array $headers = []): bool
    {
        try {
            $plainBody = $this->createPlainTextBody($htmlBody);
            $email = $this->createBaseEmail($to, $subject, $headers)
                ->html($htmlBody)
                ->text($plainBody);

            return $this->dispatch($email, function () use ($to, $subject, $htmlBody, $headers): bool {
                return $this->sendMessageFallback($to, $subject, $htmlBody, $headers, true);
            });
        } catch (\Throwable $e) {
            $this->log("Mail-Fehler an {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Plain-Text-E-Mail senden.
     */
    public function sendPlain(string $to, string $subject, string $plainBody, array $headers = []): bool
    {
        try {
            $email = $this->createBaseEmail($to, $subject, $headers)
                ->text($plainBody);

            return $this->dispatch($email, function () use ($to, $subject, $plainBody, $headers): bool {
                return $this->sendMessageFallback($to, $subject, $plainBody, $headers, false);
            });
        } catch (\Throwable $e) {
            $this->log("Mail-Fehler an {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * E-Mail mit Dateianhang senden.
     *
     * @param string $to
     * @param string $subject
     * @param string $htmlBody
     * @param string $attachmentPath  Absoluter Pfad zur Datei
     * @param string $attachmentName  Dateiname im Anhang
     * @return bool
     */
    public function sendWithAttachment(
        string $to,
        string $subject,
        string $htmlBody,
        string $attachmentPath,
        string $attachmentName = '',
        bool $isHtml = true,
        array $headers = []
    ): bool {
        if (!file_exists($attachmentPath) || !is_readable($attachmentPath)) {
            $this->log("Anhang nicht lesbar: {$attachmentPath}");
            return false;
        }

        try {
            $attachmentName = $attachmentName !== '' ? $attachmentName : basename($attachmentPath);
            $mimeType = mime_content_type($attachmentPath) ?: 'application/octet-stream';
            $email = $this->createBaseEmail($to, $subject, $headers)
                ->attachFromPath($attachmentPath, $attachmentName, $mimeType);

            if ($isHtml) {
                $email
                    ->html($htmlBody)
                    ->text($this->createPlainTextBody($htmlBody));
            } else {
                $email->text($htmlBody);
            }

            return $this->dispatch($email, function () use ($to, $subject, $htmlBody, $attachmentPath, $attachmentName, $isHtml, $headers): bool {
                return $this->sendWithAttachmentFallback($to, $subject, $htmlBody, $attachmentPath, $attachmentName, $isHtml, $headers);
            });
        } catch (\Throwable $e) {
            $this->log("Mail mit Anhang fehlgeschlagen an {$to}: " . $e->getMessage());
            return false;
        }
    }

    // ─── Internes ──────────────────────────────────────────────────────────

    private function createBaseEmail(string $to, string $subject, array $headers = []): Email
    {
        $email = (new Email())
            ->from($this->formatAddress($this->fromEmail, $this->fromName))
            ->replyTo($this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->date(new \DateTimeImmutable());

        $email->getHeaders()->addTextHeader('X-Mailer', '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0'));

        $this->applyHeaders($email, $headers);

        return $email;
    }

    private function dispatch(Email $email, callable $fallback): bool
    {
        if ($this->useSmtp) {
            return $this->sendViaSymfony($email);
        }

        return $fallback();
    }

    private function sendViaSymfony(Email $email): bool
    {
        $transport = $this->createTransport();

        try {
            $transport->send($email);
            $transport->stop();
            return true;
        } catch (\Throwable $e) {
            try {
                $transport->stop();
            } catch (\Throwable) {
            }

            $this->log('Symfony Mailer Fehler: ' . $e->getMessage());
            return false;
        }
    }

    private function createTransport(): EsmtpTransport
    {
        $encryption = strtolower(trim($this->smtpEncryption));
        $transport = new EsmtpTransport(
            $this->smtpHost,
            $this->smtpPort,
            $encryption === 'ssl'
        );

        if ($encryption === '') {
            $transport->setAutoTls(false);
        } elseif ($encryption === 'tls') {
            $transport->setRequireTls(true);
        } else {
            $transport->setAutoTls(false);
        }

        if ($this->smtpUser !== '') {
            $transport->setUsername($this->smtpUser);
            $transport->setPassword($this->smtpPass);
        }

        $localDomain = $this->resolveLocalDomain();
        if ($localDomain !== '') {
            $transport->setLocalDomain($localDomain);
        }

        return $transport;
    }

    private function applyHeaders(Email $email, array $headers): void
    {
        foreach ($this->normalizeHeaders($headers) as $name => $value) {
            $headerName = strtolower($name);

            switch ($headerName) {
                case 'reply-to':
                    $email->replyTo(...$this->parseAddressList($value));
                    break;
                case 'cc':
                    $email->cc(...$this->parseAddressList($value));
                    break;
                case 'bcc':
                    $email->bcc(...$this->parseAddressList($value));
                    break;
                case 'from':
                    $email->from(...$this->parseAddressList($value));
                    break;
                case 'sender':
                    $addresses = $this->parseAddressList($value);
                    if ($addresses !== []) {
                        $email->sender($addresses[0]);
                    }
                    break;
                case 'content-type':
                case 'mime-version':
                case 'x-mailer':
                    break;
                default:
                    $email->getHeaders()->addTextHeader($name, $value);
            }
        }
    }

    /**
     * @return array<string,string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                if (!is_string($value) || !str_contains($value, ':')) {
                    continue;
                }

                [$headerName, $headerValue] = explode(':', $value, 2);
                $normalized[trim($headerName)] = trim($headerValue);
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $normalized[(string) $key] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function parseAddressList(string $value): array
    {
        $addresses = array_map('trim', explode(',', $value));
        return array_values(array_filter($addresses, static fn (string $address): bool => $address !== ''));
    }

    private function sendMessageFallback(string $to, string $subject, string $body, array $headers, bool $isHtml): bool
    {
        $messageHeaders = $this->buildHeaders(
            array_merge(
                [
                    'Content-Type' => $isHtml
                        ? 'text/html; charset=UTF-8'
                        : 'text/plain; charset=UTF-8',
                ],
                $this->normalizeHeaders($headers)
            )
        );

        return @mail($to, $subject, $body, $messageHeaders);
    }

    private function sendWithAttachmentFallback(
        string $to,
        string $subject,
        string $body,
        string $attachmentPath,
        string $attachmentName,
        bool $isHtml,
        array $headers
    ): bool {
        $boundary = '----=_CMS_' . bin2hex(random_bytes(16));
        $mimeType = mime_content_type($attachmentPath) ?: 'application/octet-stream';
        $plainBody = $isHtml ? $this->createPlainTextBody($body) : $body;

        $messageBody  = "--{$boundary}\r\n";
        $messageBody .= 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $messageBody .= quoted_printable_encode($isHtml ? $body : $plainBody) . "\r\n\r\n";

        $fileContent = chunk_split(base64_encode((string) file_get_contents($attachmentPath)));

        $messageBody .= "--{$boundary}\r\n";
        $messageBody .= "Content-Type: {$mimeType}; name=\"{$attachmentName}\"\r\n";
        $messageBody .= "Content-Transfer-Encoding: base64\r\n";
        $messageBody .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
        $messageBody .= $fileContent . "\r\n";
        $messageBody .= "--{$boundary}--";

        $messageHeaders = $this->buildHeaders(
            array_merge(
                [
                    'Content-Type' => "multipart/mixed; boundary=\"{$boundary}\"",
                ],
                $this->normalizeHeaders($headers)
            )
        );

        return @mail($to, $subject, $messageBody, $messageHeaders);
    }

    private function createPlainTextBody(string $htmlBody): string
    {
        return strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    }

    /**
     * Standard-Header zusammenbauen.
     *
     * @param array<string,string> $extra
     */
    private function buildHeaders(array $extra = []): string
    {
        $h = [
            'From'         => $this->formatAddress($this->fromEmail, $this->fromName),
            'Reply-To'     => $this->fromEmail,
            'MIME-Version' => '1.0',
            'X-Mailer'     => '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0'),
        ];

        foreach ($extra as $k => $v) {
            $h[$k] = $v;
        }

        $lines = [];
        foreach ($h as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        return implode("\r\n", $lines);
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        if ($name === '') {
            return $email;
        }

        $safeName = str_replace(['"', '\\'], '', $name);
        return "\"{$safeName}\" <{$email}>";
    }

    private function resolveLocalDomain(): string
    {
        $host = defined('SITE_URL') ? (string) parse_url((string) SITE_URL, PHP_URL_HOST) : '';

        if ($host !== '') {
            return $host;
        }

        $hostname = gethostname();
        return is_string($hostname) ? $hostname : '';
    }

    private function log(string $message): void
    {
        error_log('[MailService] ' . $message);
        if (function_exists('cms_log')) {
            cms_log($message, 'error', 'mail');
        }
    }
}
