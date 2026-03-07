<?php
/**
 * TotpAdapter – TOTP-MFA über RobThree/TwoFactorAuth
 *
 * Ergänzt die bestehende CMS\Totp-Klasse um die robustere Library-Implementierung
 * von robthree/twofactorauth (v3.0.3). Bietet dasselbe Interface wie CMS\Totp,
 * nutzt aber die externe Library mit QR-Code-Support und Zeitsynchronisierung.
 *
 * Fallback: Wenn die Library nicht verfügbar ist, delegiert an CMS\Totp.
 *
 * @package CMSv2\Core\Auth\MFA
 * @see     https://github.com/RobThree/TwoFactorAuth
 */

declare(strict_types=1);

namespace CMS\Auth\MFA;

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Totp;

final class TotpAdapter
{
    private static ?self $instance = null;

    /** @var \RobThree\Auth\TwoFactorAuth|null */
    private ?object $tfa = null;
    private bool $libraryAvailable;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->libraryAvailable = class_exists(\RobThree\Auth\TwoFactorAuth::class);
    }

    // ── Library-Zugriff ──────────────────────────────────────────────────────

    /**
     * Gibt die RobThree-Instanz zurück (lazy init).
     * Nutzt den ServerQrCodeProvider nicht (QR wird im Frontend per JS erzeugt).
     */
    private function getTfa(): ?\RobThree\Auth\TwoFactorAuth
    {
        if (!$this->libraryAvailable) {
            return null;
        }

        if ($this->tfa === null) {
            $issuer = defined('SITE_NAME') ? SITE_NAME : '365CMS';
            // Ohne QR-Provider: URI wird ans Frontend zurückgegeben
            $this->tfa = new \RobThree\Auth\TwoFactorAuth(
                new \RobThree\Auth\Providers\Qr\QRServerProvider(),
                $issuer
            );
        }

        return $this->tfa;
    }

    // ── Öffentliche API ──────────────────────────────────────────────────────

    /**
     * Neues TOTP-Secret erzeugen (Base32, 160 Bit).
     */
    public function generateSecret(): string
    {
        $tfa = $this->getTfa();
        if ($tfa !== null) {
            return $tfa->createSecret();
        }

        // Fallback auf eingebaute Implementierung
        return Totp::instance()->generateSecret();
    }

    /**
     * TOTP-Code verifizieren.
     *
     * @param int    $userId User-ID (liest Secret aus user_meta)
     * @param string $code   6-stelliger Code
     */
    public function verifyCode(int $userId, string $code): bool
    {
        $secret = $this->getUserSecret($userId);
        if ($secret === null) {
            return false;
        }

        return $this->verifyCodeWithSecret($secret, $code);
    }

    /**
     * TOTP-Code direkt gegen ein Secret prüfen (z. B. während Setup).
     */
    public function verifyCodeWithSecret(string $secret, string $code): bool
    {
        // Nur Ziffern, exakt 6 Stellen
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $tfa = $this->getTfa();
        if ($tfa !== null) {
            return $tfa->verifyCode($secret, $code, 1);
        }

        return Totp::instance()->verifyCode($secret, $code);
    }

    /**
     * OTP-Auth-URI für QR-Code-Generierung im Frontend.
     */
    public function getOtpAuthUri(string $secret, string $account): string
    {
        $tfa = $this->getTfa();
        if ($tfa !== null) {
            return $tfa->getQRText($account, $secret);
        }

        $issuer = defined('SITE_NAME') ? SITE_NAME : '365CMS';
        return Totp::instance()->getOtpAuthUri($secret, $account, $issuer);
    }

    // ── MFA-Setup-Flow ───────────────────────────────────────────────────────

    /**
     * Beginnt den MFA-Einrichtungsflow: Erzeugt ein Pending-Secret.
     *
     * @return array{secret: string, otp_uri: string}
     */
    public function startSetup(int $userId, string $accountLabel): array
    {
        $secret = $this->generateSecret();
        $this->setUserMeta($userId, 'mfa_pending_secret', $secret);

        return [
            'secret'  => $secret,
            'otp_uri' => $this->getOtpAuthUri($secret, $accountLabel),
        ];
    }

    /**
     * Bestätigt MFA-Einrichtung: Prüft Code gegen Pending-Secret.
     */
    public function confirmSetup(int $userId, string $code): bool
    {
        $pendingSecret = $this->getUserMeta($userId, 'mfa_pending_secret');
        if ($pendingSecret === null) {
            return false;
        }

        if (!$this->verifyCodeWithSecret($pendingSecret, $code)) {
            return false;
        }

        $this->setUserMeta($userId, 'mfa_secret', $pendingSecret);
        $this->setUserMeta($userId, 'mfa_enabled', '1');
        $this->deleteUserMeta($userId, 'mfa_pending_secret');

        return true;
    }

    /**
     * MFA deaktivieren.
     */
    public function disable(int $userId): void
    {
        $this->deleteUserMeta($userId, 'mfa_secret');
        $this->deleteUserMeta($userId, 'mfa_enabled');
        $this->deleteUserMeta($userId, 'mfa_pending_secret');
    }

    /**
     * Prüft ob MFA für den User aktiv ist.
     */
    public function isEnabled(int $userId): bool
    {
        return $this->getUserMeta($userId, 'mfa_enabled') === '1';
    }

    // ── user_meta-Zugriff ────────────────────────────────────────────────────

    private function getUserSecret(int $userId): ?string
    {
        return $this->getUserMeta($userId, 'mfa_secret');
    }

    private function getUserMeta(int $userId, string $key): ?string
    {
        $db = Database::instance();
        $stmt = $db->prepare(
            "SELECT meta_value FROM {$db->getPrefix()}user_meta WHERE user_id = ? AND meta_key = ? LIMIT 1"
        );
        $stmt->execute([$userId, $key]);
        $row = $stmt->fetch();
        return $row ? (string)$row->meta_value : null;
    }

    private function setUserMeta(int $userId, string $key, string $value): void
    {
        $db = Database::instance();
        $db->execute(
            "INSERT INTO {$db->getPrefix()}user_meta (user_id, meta_key, meta_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
            [$userId, $key, $value]
        );
    }

    private function deleteUserMeta(int $userId, string $key): void
    {
        $db = Database::instance();
        $db->execute(
            "DELETE FROM {$db->getPrefix()}user_meta WHERE user_id = ? AND meta_key = ?",
            [$userId, $key]
        );
    }
}
