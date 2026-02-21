<?php
/**
 * Member Service
 *
 * Business-Logik für den eingeloggten Member-Bereich:
 * Profil, Passwort, 2FA, Benachrichtigungen, Datenschutz,
 * Subscription und Dashboard-Daten.
 *
 * @package CMS\Services
 * @version 1.0.0
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

class MemberService
{
    private static ?self $instance = null;
    private Database $db;
    private string $prefix;

    // ── Singleton ─────────────────────────────────────────────────────────────

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    // ── Profil ────────────────────────────────────────────────────────────────

    /**
     * Profildaten aktualisieren.
     *
     * @param int   $userId
     * @param array $data  Keys: first_name, last_name, email, bio, website, phone
     * @return true|string  true bei Erfolg, Fehlermeldung als String
     */
    public function updateProfile(int $userId, array $data): bool|string
    {
        // E-Mail-Uniqueness prüfen
        if (!empty($data['email'])) {
            $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return 'Ungültige E-Mail-Adresse.';
            }
            $conflict = $this->db->get_var(
                "SELECT id FROM {$this->prefix}users WHERE email = ? AND id != ?",
                [$email, $userId]
            );
            if ($conflict) {
                return 'E-Mail-Adresse wird bereits verwendet.';
            }
            $this->db->execute(
                "UPDATE {$this->prefix}users SET email = ? WHERE id = ?",
                [$email, $userId]
            );
        }

        // Meta-Felder
        $metaFields = ['first_name', 'last_name', 'bio', 'website', 'phone', 'position', 'company'];
        foreach ($metaFields as $field) {
            if (array_key_exists($field, $data)) {
                $this->setUserMeta($userId, $field, (string)($data[$field] ?? ''));
            }
        }

        return true;
    }

    /**
     * User-Meta als Array zurückgeben.
     *
     * @param int $userId
     * @return array<string, string>
     */
    public function getUserMeta(int $userId): array
    {
        $rows = $this->db->get_results(
            "SELECT meta_key, meta_value FROM {$this->prefix}user_meta WHERE user_id = ?",
            [$userId]
        );

        $meta = [];
        foreach ((array)$rows as $row) {
            $meta[$row->meta_key] = $row->meta_value;
        }
        return $meta;
    }

    // ── Passwort & 2FA ────────────────────────────────────────────────────────

    /**
     * Passwort ändern.
     *
     * @return true|string  true bei Erfolg, Fehlermeldung als String
     */
    public function changePassword(int $userId, string $current, string $new, string $confirm): bool|string
    {
        if (empty($current) || empty($new) || empty($confirm)) {
            return 'Alle Felder müssen ausgefüllt sein.';
        }
        if ($new !== $confirm) {
            return 'Neues Passwort und Bestätigung stimmen nicht überein.';
        }
        if (strlen($new) < 8) {
            return 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
        }

        $hash = $this->db->get_var(
            "SELECT password FROM {$this->prefix}users WHERE id = ?",
            [$userId]
        );

        if (!$hash || !Security::verifyPassword($current, $hash)) {
            return 'Das aktuelle Passwort ist nicht korrekt.';
        }

        $this->db->execute(
            "UPDATE {$this->prefix}users SET password = ? WHERE id = ?",
            [Security::hashPassword($new), $userId]
        );

        return true;
    }

    /**
     * Zwei-Faktor-Authentifizierung aktivieren / deaktivieren.
     *
     * @return true|string
     */
    public function toggle2FA(int $userId, bool $enable): bool|string
    {
        $this->setUserMeta($userId, '2fa_enabled', $enable ? '1' : '0');
        return true;
    }

    // ── Benachrichtigungen ────────────────────────────────────────────────────

    /**
     * Gespeicherte Benachrichtigungs-Präferenzen laden.
     *
     * @return array<string, bool|string>
     */
    public function getNotificationPreferences(int $userId): array
    {
        $meta = $this->getUserMeta($userId);
        $raw  = $meta['notification_preferences'] ?? null;
        $defaults = [
            'email_notifications'    => true,
            'email_marketing'        => false,
            'email_updates'          => true,
            'email_security'         => true,
            'browser_notifications'  => false,
            'desktop_notifications'  => false,
            'mobile_notifications'   => false,
            'notify_new_features'    => true,
            'notify_promotions'      => false,
            'notification_frequency' => 'immediate',
        ];

        if ($raw) {
            $saved = json_decode($raw, true);
            if (is_array($saved)) {
                return array_merge($defaults, $saved);
            }
        }

        return $defaults;
    }

    /**
     * Benachrichtigungs-Präferenzen speichern.
     */
    public function updateNotificationPreferences(int $userId, array $preferences): bool
    {
        $this->setUserMeta($userId, 'notification_preferences', json_encode($preferences));
        return true;
    }

    /**
     * Letzte N Benachrichtigungen abrufen.
     *
     * @return array<int, object>
     */
    public function getRecentNotifications(int $userId, int $limit = 10): array
    {
        $table = $this->prefix . 'notifications';

        // Tabelle kann optional fehlen
        try {
            $rows = $this->db->get_results(
                "SELECT * FROM {$table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
                [$userId, $limit]
            );
            return (array)$rows;
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Datenschutz ───────────────────────────────────────────────────────────

    /**
     * Datenschutz-Einstellungen laden.
     *
     * @return array<string, mixed>
     */
    public function getPrivacySettings(int $userId): array
    {
        $meta = $this->getUserMeta($userId);
        $raw  = $meta['privacy_settings'] ?? null;
        $defaults = [
            'profile_visibility' => 'members',
            'show_email'         => false,
            'show_activity'      => true,
        ];

        if ($raw) {
            $saved = json_decode($raw, true);
            if (is_array($saved)) {
                return array_merge($defaults, $saved);
            }
        }

        return $defaults;
    }

    /**
     * Datenschutz-Einstellungen speichern.
     */
    public function updatePrivacySettings(int $userId, array $settings): bool
    {
        $allowed = ['profile_visibility', 'show_email', 'show_activity'];
        $clean   = array_intersect_key($settings, array_flip($allowed));
        $this->setUserMeta($userId, 'privacy_settings', json_encode($clean));
        return true;
    }

    /**
     * Daten-Übersicht für den User (für Privacy-Seite).
     *
     * @return array<string, int>
     */
    public function getDataOverview(int $userId): array
    {
        return [
            'stored_meta_fields' => count($this->getUserMeta($userId)),
            'sessions'           => count($this->getActiveSessions($userId)),
            'notifications'      => count($this->getRecentNotifications($userId, 999)),
        ];
    }

    /**
     * User-Daten als Array exportieren (JSON-Download).
     *
     * @return array<string, mixed>|null
     */
    public function exportUserData(int $userId): ?array
    {
        $user = $this->db->get_row(
            "SELECT id, username, email, role, status, created_at FROM {$this->prefix}users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return null;
        }

        return [
            'account'           => (array)$user,
            'meta'              => $this->getUserMeta($userId),
            'privacy_settings'  => $this->getPrivacySettings($userId),
            'notifications'     => $this->getNotificationPreferences($userId),
            'exported_at'       => date('c'),
        ];
    }

    /**
     * Account zur Löschung markieren (Soft-Delete, 30-Tage-Frist).
     */
    public function requestAccountDeletion(int $userId): bool
    {
        $deletionAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $this->setUserMeta($userId, 'deletion_requested_at', date('c'));
        $this->setUserMeta($userId, 'deletion_scheduled_at', $deletionAt);
        $this->db->execute(
            "UPDATE {$this->prefix}users SET status = 'pending_deletion' WHERE id = ?",
            [$userId]
        );
        return true;
    }

    // ── Sicherheit ────────────────────────────────────────────────────────────

    /**
     * Sicherheits-Übersicht laden.
     * Berechnet Score (0-100) + Empfehlungen auf Basis der gespeicherten Meta-Daten.
     *
     * @return array<string, mixed>
     */
    public function getSecurityData(int $userId): array
    {
        $meta        = $this->getUserMeta($userId);
        $twoFA       = ($meta['2fa_enabled'] ?? '0') === '1';
        $pwChanged   = $meta['password_changed_at'] ?? null;
        $lastLogin   = $meta['last_login'] ?? null;
        $lastLoginIp = $meta['last_login_ip'] ?? null;

        // ── Score berechnen ───────────────────────────────────────────
        $score = 40; // Basis
        $recs  = [];

        if ($twoFA) {
            $score += 30;
            $recs[] = ['done' => true,  'text' => 'Zwei-Faktor-Authentifizierung ist aktiv'];
        } else {
            $recs[] = ['done' => false, 'text' => 'Zwei-Faktor-Authentifizierung aktivieren (+30 Punkte)'];
        }

        if ($pwChanged) {
            $daysSinceChange = (int) floor((time() - strtotime($pwChanged)) / 86400);
            if ($daysSinceChange <= 90) {
                $score += 20;
                $recs[] = ['done' => true,  'text' => 'Passwort kürzlich geändert (vor ' . $daysSinceChange . ' Tagen)'];
            } elseif ($daysSinceChange <= 180) {
                $score += 10;
                $recs[] = ['done' => false, 'text' => 'Passwort ist ' . $daysSinceChange . ' Tage alt – bald ändern empfohlen'];
            } else {
                $recs[] = ['done' => false, 'text' => 'Passwort ist älter als 180 Tage – bitte ändern (+20 Punkte)'];
            }
        } else {
            $recs[] = ['done' => false, 'text' => 'Passwort noch nie manuell geändert (+20 Punkte)'];
        }

        $score = min(100, $score);

        if ($score >= 80) {
            $scoreMsg = 'Sehr gut! Dein Account ist gut geschützt.';
        } elseif ($score >= 50) {
            $scoreMsg = 'Guter Start – ein paar Maßnahmen können die Sicherheit erhöhen.';
        } else {
            $scoreMsg = 'Handlungsbedarf: Bitte aktiviere weitere Schutzmaßnahmen.';
        }

        return [
            '2fa_enabled'      => $twoFA,
            'last_login'       => $lastLogin,
            'last_login_ip'    => $lastLoginIp,
            'password_changed' => $pwChanged,
            'score'            => $score,
            'score_message'    => $scoreMsg,
            'recommendations'  => $recs,
        ];
    }

    /**
     * Aktive Sessions abrufen.
     *
     * @return array<int, object>
     */
    public function getActiveSessions(int $userId): array
    {
        $table = $this->prefix . 'sessions';
        try {
            $rows = $this->db->get_results(
                "SELECT * FROM {$table} WHERE user_id = ? ORDER BY last_activity DESC",
                [$userId]
            );
            return (array)$rows;
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Subscription ──────────────────────────────────────────────────────────

    /**
     * Aktives Subscription-Paket des Users laden.
     */
    public function getUserSubscription(int $userId): ?object
    {
        if (class_exists('\\CMS\\SubscriptionManager')) {
            $sub = \CMS\SubscriptionManager::instance()->getUserSubscription($userId);
            // Map fields if necessary to match old structure expecting 'package_name'
            if ($sub && !isset($sub->package_name) && isset($sub->name)) {
                $sub->package_name = $sub->name;
            }
            return $sub;
        }

        // Fallback or legacy code removed
        return null;
    }

    /**
     * Alle buchbaren Pakete laden.
     *
     * @return array<int, object>
     */
    public function getAvailablePackages(): array
    {
        $table = $this->prefix . 'subscription_packages';
        try {
            return (array)$this->db->get_results(
                "SELECT * FROM {$table} WHERE active = 1 ORDER BY sort_order ASC"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Berechtigungen des Users auf Basis seines Subscription-Pakets.
     *
     * @return array<string, bool>
     */
    public function getUserPermissions(int $userId): array
    {
        $sub = $this->getUserSubscription($userId);
        $defaults = [
            'can_post'           => true,
            'can_message'        => true,
            'can_view_profiles'  => true,
            'premium_features'   => false,
        ];

        if ($sub && !empty($sub->features)) {
            $features = json_decode($sub->features, true);
            if (is_array($features)) {
                return array_merge($defaults, $features);
            }
        }

        return $defaults;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    /**
     * Alle Dashboard-Daten für den Member auf einmal laden.
     *
     * @return array<string, mixed>
     */
    public function getMemberDashboardData(int $userId): array
    {
        $user = $this->db->get_row(
            "SELECT id, username, email, role, status, created_at FROM {$this->prefix}users WHERE id = ?",
            [$userId]
        );

        $security     = $this->getSecurityData($userId);
        $subscription = $this->getUserSubscription($userId);

        // ── Letzter Login formatiert ──────────────────────────────────
        $lastLoginRaw  = $security['last_login'] ?? null;
        $lastLoginTs   = $lastLoginRaw ? strtotime($lastLoginRaw) : null;

        if ($lastLoginTs) {
            $lastLoginFormatted = date('d.m.Y H:i', $lastLoginTs);
            $diffSec = time() - $lastLoginTs;
            if ($diffSec < 60) {
                $lastLoginRelative = 'gerade eben';
            } elseif ($diffSec < 3600) {
                $lastLoginRelative = 'vor ' . (int)($diffSec / 60) . ' Min.';
            } elseif ($diffSec < 86400) {
                $lastLoginRelative = 'vor ' . (int)($diffSec / 3600) . ' Std.';
            } else {
                $lastLoginRelative = 'vor ' . (int)($diffSec / 86400) . ' Tagen';
            }
        } else {
            $lastLoginFormatted = 'Noch nicht erfasst';
            $lastLoginRelative  = '–';
        }

        // ── Account-Alter ─────────────────────────────────────────────
        $createdAt      = $user->created_at ?? null;
        $accountAgeDays = $createdAt
            ? (int) floor((time() - strtotime($createdAt)) / 86400)
            : 0;

        // ── Login-Zähler (30 Tage, optional aus Activity-Log) ─────────
        $loginCount30d = 0;
        try {
            $loginCount30d = (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}activity_log
                  WHERE user_id = ? AND action = 'login'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                [$userId]
            );
        } catch (\Throwable) { /* Tabelle optional */ }

        // ── Passwort-Änderung relativ ──────────────────────────────────
        $pwChangedRaw      = $security['password_changed'] ?? null;
        $pwChangedRelative = '–';
        if ($pwChangedRaw) {
            $pwTs    = strtotime($pwChangedRaw);
            $diffSec = time() - $pwTs;
            if ($diffSec < 86400) {
                $pwChangedRelative = 'vor ' . (int)($diffSec / 3600) . ' Std.';
            } elseif ($diffSec < 30 * 86400) {
                $pwChangedRelative = 'vor ' . (int)($diffSec / 86400) . ' Tagen';
            } else {
                $pwChangedRelative = date('d.m.Y', $pwTs);
            }
        }

        // ── Aktive Sessions (nur Anzahl) ───────────────────────────────
        $activeSessions = count($this->getActiveSessions($userId));

        return [
            'user'                      => $user,
            'meta'                      => $this->getUserMeta($userId),
            'subscription'              => $subscription,
            'subscription_visible'      => $subscription !== null,
            'permissions'               => $this->getUserPermissions($userId),
            'recent_notifications'      => $this->getRecentNotifications($userId, 5),
            'security'                  => $security,
            // Formatierte Werte für Views
            'last_login_formatted'      => $lastLoginFormatted,
            'last_login_relative'       => $lastLoginRelative,
            'account_age_days'          => $accountAgeDays,
            'login_count_30d'           => $loginCount30d,
            'two_factor_enabled'        => $security['2fa_enabled'],
            'password_changed_relative' => $pwChangedRelative,
            'active_sessions'           => $activeSessions,
        ];
    }

    // ── Interne Helfer ────────────────────────────────────────────────────────

    /**
     * User-Meta-Feld schreiben (upsert).
     */
    private function setUserMeta(int $userId, string $key, string $value): void
    {
        $exists = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}user_meta WHERE user_id = ? AND meta_key = ?",
            [$userId, $key]
        );

        if ((int)$exists > 0) {
            $this->db->execute(
                "UPDATE {$this->prefix}user_meta SET meta_value = ? WHERE user_id = ? AND meta_key = ?",
                [$value, $userId, $key]
            );
        } else {
            $this->db->execute(
                "INSERT INTO {$this->prefix}user_meta (user_id, meta_key, meta_value) VALUES (?, ?, ?)",
                [$userId, $key, $value]
            );
        }
    }
}
