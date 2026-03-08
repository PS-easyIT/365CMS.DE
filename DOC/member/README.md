# 365CMS – Member-Bereich
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

<!-- ADDED: 2026-03-08 -->

## Überblick

Der Member-Bereich ist die benutzerseitige Oberfläche für eingeloggte Mitglieder.
Er bietet Zugriff auf persönliche Einstellungen, Abonnements, Medien und Kommunikation.
Die Implementierung liegt in `CMS/member/` mit eigenem Partial-System für Sidebar und Layout.

## Verfügbare Funktionen

| Seite | Datei | Beschreibung |
|---|---|---|
| Dashboard | `dashboard.php` | Persönliche Übersicht mit Aktivitäten und Schnellzugriffen |
| Profil | `profile.php` | Benutzerprofil bearbeiten, Avatar und Anzeigename |
| Abonnement | `subscription.php` | Aktuelles Paket, Limits und Upgrade-Optionen |
| Sicherheit | `security.php` | Passwort ändern, Zwei-Faktor-Authentifizierung, Sessions |
| Datenschutz | `privacy.php` | Datenschutzeinstellungen und Datenanfragen |
| Medien | `media.php` | Eigene Medienbibliothek verwalten |
| Nachrichten | `messages.php` | Internes Nachrichtensystem |
| Benachrichtigungen | `notifications.php` | Benachrichtigungszentrale und Einstellungen |
| Favoriten | `favorites.php` | Gespeicherte Inhalte und Lesezeichen |
| Plugin-Bereich | `plugin-section.php` | Erweiterungsseiten von installierten Plugins |

## Benötigte Rechte

- Rolle **Member** (eingeloggter Benutzer) erforderlich
- Einzelne Funktionen können durch Paket-Limits eingeschränkt sein

## Verwandte Dokumente

- [../admin/subscription/README.md](../admin/subscription/README.md)
- [../admin/security/README.md](../admin/security/README.md)
- [../admin/plugins/README.md](../admin/plugins/README.md)
