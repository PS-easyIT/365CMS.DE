<?php
/**
 * 365CMS Vendor Autoloader (Produktiv)
 *
 * Zentraler Autoloader für alle externen PHP-Libraries in CMS/assets/.
 * Dieses Verzeichnis wird komplett aufs Shared Hosting deployed.
 *
 * ASSETS/ (Repo-Root) ist nur lokale Entwicklungs-Ablage und wird NICHT deployed.
 * Alle benötigten Library-Dateien liegen hier in CMS/assets/.
 *
 * Verwendung (automatisch via Bootstrap.php):
 *   require_once ABSPATH . 'assets/autoload.php';
 *
 * @package 365CMS\Vendor
 */

declare(strict_types=1);

// Basisverzeichnis der CMS-Assets
if (!defined('CMS_VENDOR_PATH')) {
    define('CMS_VENDOR_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// ─── HTMLPurifier ──────────────────────────────────────────────────────────
// Library: htmlpurifier/ (Quelle: htmlpurifier-4.19.0)
$_htmlPurifierLib = CMS_VENDOR_PATH . 'htmlpurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php';
if (file_exists($_htmlPurifierLib)) {
    require_once $_htmlPurifierLib;
}
unset($_htmlPurifierLib);

// schema-org/ (Spatie) – ersetzt durch melbahja/seo (Schema-Modul)

// melbahja/seo – Sitemap, IndexNow, Schema
// PSR-4: Melbahja\Seo\ → melbahja-seo/src/
spl_autoload_register(function (string $class): void {
    $prefix = 'Melbahja\\Seo\\';
    $baseDir = CMS_VENDOR_PATH . 'melbahja-seo' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── TNTSearch ─────────────────────────────────────────────────────────────
// PSR-4: TeamTNT\TNTSearch\ → tntsearchsrc/
spl_autoload_register(function (string $class): void {
    $prefix = 'TeamTNT\\TNTSearch\\';
    $baseDir = CMS_VENDOR_PATH . 'tntsearchsrc' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// TNTSearch Helper-Funktionen
$_tntHelpers = CMS_VENDOR_PATH . 'tntsearchhelper' . DIRECTORY_SEPARATOR . 'helpers.php';
if (file_exists($_tntHelpers)) {
    require_once $_tntHelpers;
}
unset($_tntHelpers);

// ─── Carbon (nesbot/carbon) ────────────────────────────────────────────────
// PSR-4: Carbon\ → Carbon/
// Genutzt von time_ago() in includes/functions.php (mit class_exists-Guard)
spl_autoload_register(function (string $class): void {
    $prefix = 'Carbon\\';
    $baseDir = CMS_VENDOR_PATH . 'Carbon' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Symfony Translation ───────────────────────────────────────────────────
// PSR-4: Symfony\Component\Translation\ → translation/
// Genutzt von TranslationService (mit class_exists-Guard)
spl_autoload_register(function (string $class): void {
    $prefix = 'Symfony\\Component\\Translation\\';
    $baseDir = CMS_VENDOR_PATH . 'translation' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── lbuchs/WebAuthn ──────────────────────────────────────────────────────
// Passkey / FIDO2: webauthn/
// Hinweis: Library verwendet interne require_once-Aufrufe.
spl_autoload_register(function (string $class): void {
    $prefix = 'lbuchs\\WebAuthn\\';
    $baseDir = CMS_VENDOR_PATH . 'webauthn' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── RobThree/TwoFactorAuth ───────────────────────────────────────────────
// PSR-4: RobThree\Auth\ → twofactorauth/
spl_autoload_register(function (string $class): void {
    $prefix = 'RobThree\\Auth\\';
    $baseDir = CMS_VENDOR_PATH . 'twofactorauth' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── DirectoryTree/LdapRecord ──────────────────────────────────────────────
// PSR-4: LdapRecord\ → ldaprecord/
spl_autoload_register(function (string $class): void {
    $prefix = 'LdapRecord\\';
    $baseDir = CMS_VENDOR_PATH . 'ldaprecord' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Firebase/php-jwt ──────────────────────────────────────────────────────
// PSR-4: Firebase\JWT\ → php-jwt/
spl_autoload_register(function (string $class): void {
    $prefix = 'Firebase\\JWT\\';
    $baseDir = CMS_VENDOR_PATH . 'php-jwt' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── PSR Log (minimale Kompatibilität) ─────────────────────────────────────
// PSR-4: Psr\Log\ → psr/Log/
spl_autoload_register(function (string $class): void {
    $prefix = 'Psr\\Log\\';
    $baseDir = CMS_VENDOR_PATH . 'psr' . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Symfony Mime ──────────────────────────────────────────────────────────
// PSR-4: Symfony\Component\Mime\ → mime/
spl_autoload_register(function (string $class): void {
    $prefix = 'Symfony\\Component\\Mime\\';
    $baseDir = CMS_VENDOR_PATH . 'mime' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Symfony Mailer ────────────────────────────────────────────────────────
// PSR-4: Symfony\Component\Mailer\ → mailer/
spl_autoload_register(function (string $class): void {
    $prefix = 'Symfony\\Component\\Mailer\\';
    $baseDir = CMS_VENDOR_PATH . 'mailer' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── PSR Event Dispatcher (minimale Kompatibilität) ────────────────────────
// PSR-4: Psr\EventDispatcher\ → psr/EventDispatcher/
spl_autoload_register(function (string $class): void {
    $prefix = 'Psr\\EventDispatcher\\';
    $baseDir = CMS_VENDOR_PATH . 'psr' . DIRECTORY_SEPARATOR . 'EventDispatcher' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
