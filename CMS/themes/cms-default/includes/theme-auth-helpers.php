<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function theme_is_logged_in(): bool
{
    return meridian_is_logged_in();
}

function theme_get_flash(): ?array
{
    return meridian_get_flash();
}

function theme_get_menu(string $location): array
{
    try {
        return \CMS\ThemeManager::instance()->getMenu($location) ?? [];
    } catch (\Throwable $e) {
        return [];
    }
}

function theme_login_user(string $email, string $password): bool|string
{
    try {
        $auth = \CMS\Auth::instance();
        $result = $auth->login($email, $password);
        if ($result === true) {
            return true;
        }

        return is_string($result) ? $result : 'Ungültige Zugangsdaten.';
    } catch (\Throwable $e) {
        return 'Anmeldung fehlgeschlagen: ' . $e->getMessage();
    }
}

function theme_register_user(string $email, string $username, string $password): bool|string
{
    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $exists = $db->execute(
            "SELECT id FROM {$prefix}users WHERE email = ? OR username = ? LIMIT 1",
            [$email, $username]
        )->fetch();
        if ($exists) {
            return 'E-Mail-Adresse oder Benutzername bereits vergeben.';
        }

        if (method_exists(\CMS\Auth::instance(), 'register')) {
            $result = \CMS\Auth::instance()->register($email, $username, $password);
            return $result === true ? true : (is_string($result) ? $result : 'Registrierung fehlgeschlagen.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->execute(
            "INSERT INTO {$prefix}users (email, username, password, role, created_at) VALUES (?, ?, ?, 'member', NOW())",
            [$email, $username, $hash]
        );

        return true;
    } catch (\Throwable $e) {
        return 'Registrierung fehlgeschlagen: ' . $e->getMessage();
    }
}
