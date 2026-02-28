<?php
/**
 * TOTP – Time-based One-Time Password (RFC 6238)
 *
 * Pure-PHP-Implementierung ohne externe Abhängigkeiten.
 * Kompatibel mit Google Authenticator, Authy, Microsoft Authenticator.
 *
 * @package CMSv2\Core
 * @see     RFC 6238 (TOTP), RFC 4226 (HOTP), RFC 4648 (Base32)
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Totp
{
    /** Zeitschritt in Sekunden (RFC 6238 Standard: 30 s) */
    private const STEP = 30;

    /** Anzahl der OTP-Ziffern (Standard: 6) */
    private const DIGITS = 6;

    /** Toleranzfenster: ±N Zeitschritte für Uhr-Abweichung (N=1 → ±30 s) */
    private const WINDOW = 1;

    /** Länge des Rohschlüssels in Bytes (20 Bytes → 32-Zeichen Base32) */
    private const SECRET_BYTES = 20;

    /** Base32-Zeichensatz (RFC 4648, ohne Padding) */
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // ── Singleton ────────────────────────────────────────────────────────────

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Öffentliche API ───────────────────────────────────────────────────────

    /**
     * Neues zufälliges TOTP-Geheimnis erzeugen (Base32-kodiert, 20 Bytes = 32 Chars).
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * TOTP-Code für einen gegebenen Zeitstempel erzeugen.
     *
     * @param string   $secret    Base32-kodiertes Geheimnis
     * @param int|null $timestamp Unix-Timestamp (null = jetzt)
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $time = (int)(($timestamp ?? time()) / self::STEP);
        $key  = $this->base32Decode($secret);

        // HMAC-SHA1 über den 8-Byte Big-Endian-Counter (RFC 4226 §5.3)
        $timeBytes = pack('N*', 0) . pack('N*', $time);
        $hash      = hash_hmac('sha1', $timeBytes, $key, true);

        // Dynamic truncation (RFC 4226 §5.4)
        $offset = ord($hash[19]) & 0x0F;
        $code   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Nutzer-eingabe gegen aktuellen (und ±WINDOW) Zeitschritte prüfen.
     * Nutzt `hash_equals()` für zeitkonstanten Vergleich (Timing-Safe).
     *
     * @param string $secret Base32-kodiertes Geheimnis
     * @param string $code   6-stelliger Code vom Nutzer
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Nur Ziffern, exakt 6 Stellen
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = time();
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $expected = $this->generateCode($secret, $now + ($i * self::STEP));
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * otpauth:// URI für QR-Code-Generierung erzeugen.
     *
     * @param string $secret  Base32-kodiertes Geheimnis
     * @param string $account E-Mail oder Benutzername des Nutzers
     * @param string $issuer  Anzeigename der Anwendung / Domain
     */
    public function getOtpAuthUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::STEP
        );
    }

    /**
     * QR-Code-Bild-URL via api.qrserver.com (kein Google, DSGVO-freundlicher).
     *
     * @param string $secret  Base32-kodiertes Geheimnis
     * @param string $account E-Mail oder Benutzername
     * @param string $issuer  Anzeigename der Anwendung
     * @return string OTP-Auth-URI (zur lokalen QR-Code-Generierung im Frontend)
     *
     * @deprecated Verwende getOtpAuthUri() und generiere den QR-Code lokal
     *             (z. B. via JS-Bibliothek im Frontend). NIEMALS das Secret an
     *             externe APIs senden!
     */
    public function getQrCodeUrl(string $secret, string $account, string $issuer): string
    {
        // SICHERHEITSHINWEIS: Secret darf NICHT an externe Server übermittelt werden!
        // Gib die OTP-Auth-URI zurück – der QR-Code muss lokal generiert werden
        // (z. B. über eine JS-Bibliothek wie qrcode.js im Frontend).
        return $this->getOtpAuthUri($secret, $account, $issuer);
    }

    // ── Base32 (RFC 4648) ─────────────────────────────────────────────────────

    /**
     * Binärdaten in Base32 kodieren (ohne Padding).
     */
    public function base32Encode(string $data): string
    {
        $chars  = self::BASE32_CHARS;
        $output = '';
        $v      = 0;
        $vbits  = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $v    = ($v << 8) | ord($data[$i]);
            $vbits += 8;
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $chars[($v >> $vbits) & 0x1F];
            }
        }

        if ($vbits > 0) {
            $output .= $chars[($v << (5 - $vbits)) & 0x1F];
        }

        return $output;
    }

    /**
     * Base32-kodierten String dekodieren (robust: Padding + Leerzeichen ignorieren).
     */
    public function base32Decode(string $data): string
    {
        // Normalisieren: uppercase, Padding weg, Leerzeichen weg
        $data   = strtoupper(str_replace([' ', '-', '='], '', $data));
        $chars  = self::BASE32_CHARS;
        $output = '';
        $v      = 0;
        $vbits  = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $c = strpos($chars, $data[$i]);
            if ($c === false) {
                continue; // Ungültige Zeichen überspringen
            }
            $v    = ($v << 5) | $c;
            $vbits += 5;
            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 0xFF);
            }
        }

        return $output;
    }
}
