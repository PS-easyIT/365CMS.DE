<?php
/**
 * Zentraler Mail-Service für 365CMS.
 *
 * Unterstützt:
 * - PHP mail() Fallback
 * - klassisches SMTP mit Benutzername/Passwort
 * - Microsoft 365 SMTP via Azure OAuth2 / XOAUTH2
 *
 * Die Laufzeitkonfiguration wird bevorzugt aus der bestehenden Tabelle
 * `{prefix}settings` gelesen und fällt bei Bedarf auf `config/app.php` zurück.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Logger;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

if (!defined('ABSPATH')) {
    exit;
}

class MailService
{
    private static ?self $instance = null;

    private SettingsService $settings;
    private MailLogService $mailLogs;
    private Logger $logger;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = SettingsService::getInstance();
        $this->mailLogs = MailLogService::getInstance();
        $this->logger = Logger::instance()->withChannel('mail');
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function getTransportInfo(): array
    {
        $config = $this->getEffectiveConfig();

        return [
            'uses_smtp' => $config['use_smtp'],
            'transport' => $config['driver'],
            'transport_label' => $config['transport_label'],
            'provider' => $config['provider'],
            'host' => $config['smtp_host'],
            'port' => $config['smtp_port'],
            'encryption' => $config['smtp_encryption'] !== '' ? $config['smtp_encryption'] : 'none',
            'encryption_raw' => $config['smtp_encryption'],
            'username' => $config['smtp_username'],
            'from_email' => $config['from_email'],
            'from_name' => $config['from_name'],
            'auth_mode' => $config['auth_mode'],
            'auth_mode_label' => $config['auth_mode'] === 'oauth2' ? 'Azure OAuth2 / XOAUTH2' : 'Benutzername + Passwort',
        ];
    }

    /**
     * @return array{success:bool,message?:string,error?:string,transport?:string}
     */
    public function sendBackendTestEmail(string $to, string $source = 'admin'): array
    {
        $recipient = trim($to);
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'Bitte eine gültige Empfänger-E-Mail-Adresse angeben.'];
        }

        $transport = $this->getTransportInfo();
        $sent = $this->send(
            $recipient,
            '365CMS Test-E-Mail',
            $this->buildBackendTestBody($recipient, $source, $transport),
            [
                'X-365CMS-Test-Mail' => '1',
                'X-365CMS-Test-Source' => $source,
            ]
        );

        if (!$sent) {
            return [
                'success' => false,
                'error' => 'Die Test-E-Mail konnte nicht versendet werden. Bitte Transport, Authentifizierung und Absenderkonfiguration prüfen.',
                'transport' => (string) ($transport['transport_label'] ?? ''),
            ];
        }

        return [
            'success' => true,
            'message' => 'Test-E-Mail erfolgreich an ' . $recipient . ' versendet (' . ($transport['transport_label'] ?? 'Mailversand') . ').',
            'transport' => (string) ($transport['transport_label'] ?? ''),
        ];
    }

    public function send(string $to, string $subject, string $htmlBody, array $headers = []): bool
    {
        try {
            $plainBody = $this->createPlainTextBody($htmlBody);
            $email = $this->createBaseEmail($to, $subject, $headers)
                ->html($htmlBody)
                ->text($plainBody);

            return $this->dispatchMessage($to, $subject, $email, function () use ($to, $subject, $htmlBody, $headers): bool {
                return $this->sendMessageFallback($to, $subject, $htmlBody, $headers, true);
            }, $headers);
        } catch (\Throwable $e) {
            $this->logFailure($to, $subject, 'mail_exception', $e->getMessage(), $headers);
            return false;
        }
    }

    public function sendPlain(string $to, string $subject, string $plainBody, array $headers = []): bool
    {
        try {
            $email = $this->createBaseEmail($to, $subject, $headers)
                ->text($plainBody);

            return $this->dispatchMessage($to, $subject, $email, function () use ($to, $subject, $plainBody, $headers): bool {
                return $this->sendMessageFallback($to, $subject, $plainBody, $headers, false);
            }, $headers);
        } catch (\Throwable $e) {
            $this->logFailure($to, $subject, 'mail_exception', $e->getMessage(), $headers);
            return false;
        }
    }

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
            $this->logFailure($to, $subject, 'attachment_missing', 'Anhang nicht lesbar: ' . $attachmentPath, $headers);
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

            return $this->dispatchMessage($to, $subject, $email, function () use ($to, $subject, $htmlBody, $attachmentPath, $attachmentName, $isHtml, $headers): bool {
                return $this->sendWithAttachmentFallback($to, $subject, $htmlBody, $attachmentPath, $attachmentName, $isHtml, $headers);
            }, $headers);
        } catch (\Throwable $e) {
            $this->logFailure($to, $subject, 'mail_exception', $e->getMessage(), $headers);
            return false;
        }
    }

    private function createBaseEmail(string $to, string $subject, array $headers = []): Email
    {
        $config = $this->getEffectiveConfig();

        $email = (new Email())
            ->from($this->formatAddress((string) $config['from_email'], (string) $config['from_name']))
            ->replyTo((string) $config['from_email'])
            ->to($to)
            ->subject($subject)
            ->date(new \DateTimeImmutable());

        $email->getHeaders()->addTextHeader('X-Mailer', '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0'));

        $this->applyHeaders($email, $headers);

        return $email;
    }

    private function dispatchMessage(string $to, string $subject, Email $email, callable $fallback, array $headers = []): bool
    {
        $config = $this->getEffectiveConfig();
        $source = $this->resolveSource($headers);

        if ($config['use_smtp']) {
            $result = $this->sendViaSymfony($email, $config);
            if (!empty($result['success'])) {
                $this->logSuccess($to, $subject, $config, $result['message_id'] ?? null, $headers, $source);
                return true;
            }

            $this->logFailure($to, $subject, (string) ($config['provider'] ?? 'smtp'), (string) ($result['error'] ?? 'Unbekannter SMTP-Fehler'), $headers, $source, $config);
            return false;
        }

        $success = (bool) $fallback();
        if ($success) {
            $this->logSuccess($to, $subject, $config, null, $headers, $source);
        } else {
            $this->logFailure($to, $subject, 'mail', 'PHP mail() konnte die Nachricht nicht versenden.', $headers, $source, $config);
        }

        return $success;
    }

    /**
     * @param array<string, mixed> $config
     * @return array{success:bool,message_id?:string,error?:string}
     */
    private function sendViaSymfony(Email $email, array $config): array
    {
        $transport = $this->createTransport($config);

        try {
            $sentMessage = $transport->send($email);
            return [
                'success' => true,
                'message_id' => $this->extractMessageId($sentMessage),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Symfony-Mailer-Fehler: {message}', [
                'message' => $e->getMessage(),
                'provider' => $config['provider'],
                'host' => $config['smtp_host'],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            try {
                $transport->stop();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createTransport(array $config): EsmtpTransport
    {
        $encryption = strtolower(trim((string) $config['smtp_encryption']));
        $transport = new EsmtpTransport(
            (string) $config['smtp_host'],
            (int) $config['smtp_port'],
            $encryption === 'ssl'
        );

        if ($encryption === '') {
            $transport->setAutoTls(false);
        } elseif ($encryption === 'tls') {
            $transport->setRequireTls(true);
        } else {
            $transport->setAutoTls(false);
        }

        $username = trim((string) $config['smtp_username']);
        if ($username !== '') {
            $transport->setUsername($username);
        }

        if ((string) $config['auth_mode'] === 'oauth2') {
            $token = AzureMailTokenProvider::getInstance()->getAccessToken();
            $transport->setPassword((string) $token['access_token']);
            $transport->setAuthenticators([new XOAuth2Authenticator()]);
        } elseif ($username !== '' && (string) $config['smtp_password'] !== '') {
            $transport->setPassword((string) $config['smtp_password']);
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
        $config = $this->getEffectiveConfig();
        $messageHeaders = $this->buildHeaders(
            array_merge(
                [
                    'Content-Type' => $isHtml
                        ? 'text/html; charset=UTF-8'
                        : 'text/plain; charset=UTF-8',
                ],
                $this->normalizeHeaders($headers)
            ),
            (string) $config['from_email'],
            (string) $config['from_name']
        );

        return mail($to, $subject, $body, $messageHeaders);
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
        $config = $this->getEffectiveConfig();
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
            ),
            (string) $config['from_email'],
            (string) $config['from_name']
        );

        return mail($to, $subject, $messageBody, $messageHeaders);
    }

    private function createPlainTextBody(string $htmlBody): string
    {
        return strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    }

    /**
     * @param array<string, bool|int|string> $transport
     */
    private function buildBackendTestBody(string $recipient, string $source, array $transport): string
    {
        $siteName = defined('SITE_NAME') ? (string) SITE_NAME : '365CMS';
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        $timestamp = date('d.m.Y H:i:s');

        return '<h2>365CMS Test-E-Mail</h2>'
            . '<p>Diese Nachricht wurde erfolgreich aus dem 365CMS-Backend ausgelöst.</p>'
            . '<ul>'
            . '<li><strong>Empfänger:</strong> ' . htmlspecialchars($recipient, ENT_QUOTES) . '</li>'
            . '<li><strong>Quelle:</strong> ' . htmlspecialchars($source, ENT_QUOTES) . '</li>'
            . '<li><strong>Zeitpunkt:</strong> ' . htmlspecialchars($timestamp, ENT_QUOTES) . '</li>'
            . '<li><strong>Transport:</strong> ' . htmlspecialchars((string) ($transport['transport_label'] ?? 'Mailversand'), ENT_QUOTES) . '</li>'
            . '<li><strong>Authentifizierung:</strong> ' . htmlspecialchars((string) ($transport['auth_mode_label'] ?? '—'), ENT_QUOTES) . '</li>'
            . '<li><strong>SMTP-Host:</strong> ' . htmlspecialchars((string) ($transport['host'] ?? '—'), ENT_QUOTES) . '</li>'
            . '<li><strong>SMTP-Port:</strong> ' . htmlspecialchars((string) ($transport['port'] ?? '—'), ENT_QUOTES) . '</li>'
            . '<li><strong>Verschlüsselung:</strong> ' . htmlspecialchars((string) ($transport['encryption'] ?? '—'), ENT_QUOTES) . '</li>'
            . '<li><strong>Absender:</strong> ' . htmlspecialchars((string) ($transport['from_name'] ?? ''), ENT_QUOTES) . ' &lt;' . htmlspecialchars((string) ($transport['from_email'] ?? ''), ENT_QUOTES) . '&gt;</li>'
            . '</ul>'
            . '<p><strong>Website:</strong> ' . htmlspecialchars($siteName, ENT_QUOTES)
            . ($siteUrl !== '' ? ' (<a href="' . htmlspecialchars($siteUrl, ENT_QUOTES) . '">' . htmlspecialchars($siteUrl, ENT_QUOTES) . '</a>)' : '')
            . '</p>'
            . '<p>Wenn diese Nachricht ankommt, ist die zentrale Mail-Implementierung des CMS korrekt verbunden.</p>';
    }

    /**
     * @param array<string,string> $extra
     */
    private function buildHeaders(array $extra = [], string $fromEmail = '', string $fromName = ''): string
    {
        $email = $fromEmail !== '' ? $fromEmail : (defined('SMTP_FROM_EMAIL') ? (string) SMTP_FROM_EMAIL : (defined('ADMIN_EMAIL') ? (string) ADMIN_EMAIL : 'noreply@localhost'));
        $name = $fromName !== '' ? $fromName : (defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : (defined('SITE_NAME') ? (string) SITE_NAME : 'CMS'));

        $headers = [
            'From' => $this->formatAddress($email, $name),
            'Reply-To' => $email,
            'MIME-Version' => '1.0',
            'X-Mailer' => '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0'),
        ];

        foreach ($extra as $key => $value) {
            $headers[$key] = $value;
        }

        $lines = [];
        foreach ($headers as $nameKey => $value) {
            $lines[] = $nameKey . ': ' . $value;
        }

        return implode("\r\n", $lines);
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        if ($name === '') {
            return $email;
        }

        $safeName = str_replace(['"', '\\'], '', $name);
        return '"' . $safeName . '" <' . $email . '>';
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

    /**
     * @param array<string, mixed> $config
     */
    private function logSuccess(string $recipient, string $subject, array $config, ?string $messageId, array $headers, string $source): void
    {
        $this->mailLogs->log(
            $recipient,
            $subject,
            'sent',
            (string) ($config['driver'] ?? 'mail'),
            (string) ($config['provider'] ?? 'default'),
            $messageId,
            null,
            [
                'auth_mode' => $config['auth_mode'] ?? 'password',
                'host' => $config['smtp_host'] ?? '',
                'port' => $config['smtp_port'] ?? 0,
                'header_keys' => array_keys($this->normalizeHeaders($headers)),
            ],
            $source
        );
    }

    /**
     * @param array<string, mixed>|null $config
     */
    private function logFailure(string $recipient, string $subject, string $provider, string $error, array $headers = [], string $source = 'system', ?array $config = null): void
    {
        $transport = $config['driver'] ?? 'mail';
        $providerName = $config['provider'] ?? $provider;

        $this->logger->error('Mail-Versand fehlgeschlagen', [
            'recipient' => $recipient,
            'subject' => $subject,
            'provider' => $providerName,
            'error_text' => $error,
        ]);

        $this->mailLogs->log(
            $recipient,
            $subject,
            'failed',
            (string) $transport,
            (string) $providerName,
            null,
            $error,
            [
                'auth_mode' => $config['auth_mode'] ?? 'password',
                'host' => $config['smtp_host'] ?? '',
                'port' => $config['smtp_port'] ?? 0,
                'header_keys' => array_keys($this->normalizeHeaders($headers)),
            ],
            $source
        );
    }

    private function resolveSource(array $headers): string
    {
        $normalized = $this->normalizeHeaders($headers);
        return trim((string) ($normalized['X-365CMS-Test-Source'] ?? 'system'));
    }

    private function extractMessageId(?SentMessage $sentMessage): ?string
    {
        if ($sentMessage === null) {
            return null;
        }

        $messageId = trim($sentMessage->getMessageId());
        return $messageId !== '' ? $messageId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getEffectiveConfig(): array
    {
        $driver = $this->settings->getString('mail', 'driver', defined('SMTP_HOST') && (string) SMTP_HOST !== '' ? 'smtp' : 'mail');
        $driver = $driver === 'smtp' ? 'smtp' : 'mail';

        $authMode = $this->settings->getString('mail', 'auth_mode', 'password');
        $authMode = $authMode === 'oauth2' ? 'oauth2' : 'password';

        $smtpHost = $this->settings->getString(
            'mail',
            'smtp_host',
            $authMode === 'oauth2'
                ? 'smtp.office365.com'
                : (defined('SMTP_HOST') ? (string) SMTP_HOST : '')
        );
        if ($authMode === 'oauth2' && $smtpHost === '') {
            $smtpHost = 'smtp.office365.com';
        }

        $smtpPort = $this->settings->getInt('mail', 'smtp_port', defined('SMTP_PORT') ? (int) SMTP_PORT : 587);
        $smtpEncryption = $this->settings->getString('mail', 'smtp_encryption', defined('SMTP_ENCRYPTION') ? (string) SMTP_ENCRYPTION : 'tls');
        if (!in_array($smtpEncryption, ['tls', 'ssl', ''], true)) {
            $smtpEncryption = 'tls';
        }

        $oauthMailbox = $this->settings->getString('mail', 'azure_mailbox');
        $smtpUsername = $this->settings->getString('mail', 'smtp_username', defined('SMTP_USER') ? (string) SMTP_USER : '');
        if ($authMode === 'oauth2' && $oauthMailbox !== '') {
            $smtpUsername = $oauthMailbox;
        }

        $smtpPassword = $this->settings->getString('mail', 'smtp_password', defined('SMTP_PASS') ? (string) SMTP_PASS : '');
        $fromEmail = $this->settings->getString('mail', 'from_email', defined('SMTP_FROM_EMAIL') ? (string) SMTP_FROM_EMAIL : (defined('ADMIN_EMAIL') ? (string) ADMIN_EMAIL : 'noreply@localhost'));
        $fromName = $this->settings->getString('mail', 'from_name', defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : (defined('SITE_NAME') ? (string) SITE_NAME : 'CMS'));

        $useSmtp = $driver === 'smtp' && $smtpHost !== '';
        $provider = $useSmtp
            ? ($authMode === 'oauth2' ? 'smtp-oauth2' : 'smtp-basic')
            : 'mail';
        $transportLabel = match ($provider) {
            'smtp-oauth2' => 'SMTP via Symfony Mailer (Microsoft 365 OAuth2)',
            'smtp-basic' => 'SMTP via Symfony Mailer',
            default => 'PHP mail() Fallback',
        };

        return [
            'driver' => $driver,
            'use_smtp' => $useSmtp,
            'provider' => $provider,
            'transport_label' => $transportLabel,
            'auth_mode' => $authMode,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort > 0 ? $smtpPort : 587,
            'smtp_encryption' => $smtpEncryption,
            'smtp_username' => $smtpUsername,
            'smtp_password' => $smtpPassword,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
        ];
    }
}
