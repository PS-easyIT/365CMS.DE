<?php
/**
 * Mail Service – Leichtgewichtiger SMTP/Mail-Service
 *
 * Sendet E-Mails über SMTP (TLS/SSL) oder PHP mail() als Fallback.
 * Keine externen Dependencies — reines PHP mit fsockopen().
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

use CMS\Logger;

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
        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        return $this->sendMessage($to, $subject, $htmlBody, $plainBody, $headers);
    }

    /**
     * Plain-Text-E-Mail senden.
     */
    public function sendPlain(string $to, string $subject, string $plainBody, array $headers = []): bool
    {
        return $this->sendMessage($to, $subject, '', $plainBody, $headers);
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
        string $attachmentName = ''
    ): bool {
        if (!file_exists($attachmentPath) || !is_readable($attachmentPath)) {
            $this->log("Anhang nicht lesbar: {$attachmentPath}");
            return false;
        }

        $attachmentName = $attachmentName ?: basename($attachmentPath);
        $boundary       = '----=_CMS_' . bin2hex(random_bytes(16));
        $plainBody      = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($htmlBody) . "\r\n\r\n";

        $fileContent = chunk_split(base64_encode(file_get_contents($attachmentPath)));
        $mimeType    = mime_content_type($attachmentPath) ?: 'application/octet-stream';

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}; name=\"{$attachmentName}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--{$boundary}--";

        $messageHeaders = $this->buildHeaders([
            'Content-Type' => "multipart/mixed; boundary=\"{$boundary}\"",
        ]);

        if ($this->useSmtp) {
            return $this->smtpSend($to, $subject, $body, $messageHeaders);
        }

        return @mail($to, $subject, $body, $messageHeaders);
    }

    // ─── Internes ──────────────────────────────────────────────────────────

    private function sendMessage(
        string $to,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array  $extraHeaders
    ): bool {
        $isHtml  = $htmlBody !== '';
        $headers = $this->buildHeaders(
            array_merge(
                [
                    'Content-Type' => $isHtml
                        ? 'text/html; charset=UTF-8'
                        : 'text/plain; charset=UTF-8',
                ],
                $extraHeaders
            )
        );

        $body = $isHtml ? $htmlBody : $plainBody;

        try {
            if ($this->useSmtp) {
                return $this->smtpSend($to, $subject, $body, $headers);
            }

            return @mail($to, $subject, $body, $headers);
        } catch (\Throwable $e) {
            $this->log("Mail-Fehler an {$to}: " . $e->getMessage());
            return false;
        }
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
        // RFC 2822 Display-Name Format
        $safeName = str_replace(['"', '\\'], '', $name);
        return "\"{$safeName}\" <{$email}>";
    }

    // ─── SMTP ──────────────────────────────────────────────────────────────

    /**
     * E-Mail über SMTP senden (TLS/SSL).
     */
    private function smtpSend(string $to, string $subject, string $body, string $headers): bool
    {
        $host = $this->smtpHost;
        $port = $this->smtpPort;

        // SSL-Wrapper
        if ($this->smtpEncryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            $this->log("SMTP-Verbindung fehlgeschlagen: {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($socket, 30);

        try {
            $this->smtpReadResponse($socket, 220);

            // EHLO
            $this->smtpCommand($socket, 'EHLO ' . gethostname(), 250);

            // STARTTLS
            if ($this->smtpEncryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new \RuntimeException('TLS-Handshake fehlgeschlagen');
                }
                // Nach TLS erneut EHLO
                $this->smtpCommand($socket, 'EHLO ' . gethostname(), 250);
            }

            // AUTH LOGIN
            if ($this->smtpUser !== '') {
                $this->smtpCommand($socket, 'AUTH LOGIN', 334);
                $this->smtpCommand($socket, base64_encode($this->smtpUser), 334);
                $this->smtpCommand($socket, base64_encode($this->smtpPass), 235);
            }

            // Envelope
            $this->smtpCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>', 250);
            $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', 250);

            // DATA
            $this->smtpCommand($socket, 'DATA', 354);

            // Message
            $message  = "Subject: {$subject}\r\n";
            $message .= "To: {$to}\r\n";
            $message .= $headers . "\r\n";
            $message .= "\r\n";
            $message .= $body . "\r\n";
            $message .= ".";

            $this->smtpCommand($socket, $message, 250);

            // QUIT
            $this->smtpCommand($socket, 'QUIT', 221);

            fclose($socket);
            return true;

        } catch (\Throwable $e) {
            $this->log("SMTP-Fehler: " . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    /**
     * @param resource $socket
     */
    private function smtpCommand($socket, string $command, int $expectedCode): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpReadResponse($socket, $expectedCode);
    }

    /**
     * @param resource $socket
     */
    private function smtpReadResponse($socket, int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Letzte Zeile: Code + Leerzeichen (kein '-')
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \RuntimeException("SMTP erwartet {$expectedCode}, bekommen {$code}: " . trim($response));
        }

        return $response;
    }

    private function log(string $message): void
    {
        error_log('[MailService] ' . $message);
        if (function_exists('cms_log')) {
            cms_log($message, 'error', 'mail');
        }
    }
}
