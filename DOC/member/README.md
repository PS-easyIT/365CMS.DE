# 365CMS – Member-Bereich
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

<!-- UPDATED: 2026-03-28 -->

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
| Sicherheit | `security.php` | Passwort ändern, Zwei-Faktor-Authentifizierung, Passkeys, Sessions |
| Datenschutz | `privacy.php` | Datenschutzeinstellungen und Datenanfragen |
| Medien | `media.php` | Eigene Medienbibliothek verwalten |
| Nachrichten | `messages.php` | Internes Nachrichtensystem |
| Benachrichtigungen | `notifications.php` | Benachrichtigungszentrale und Einstellungen |
| Favoriten | `favorites.php` | Gespeicherte Inhalte und Lesezeichen |
| Plugin-Bereich | `plugin-section.php` | Erweiterungsseiten von installierten Plugins |

## Benötigte Rechte

- Rolle **Member** (eingeloggter Benutzer) erforderlich
- Einzelne Funktionen können durch Paket-Limits eingeschränkt sein

## Member-Medien im Stand 2.8.0 RC

Die Medienseite unter `/member/media` arbeitet vollständig root-scoped auf dem persönlichen Pfad `member/user-<id>`.

Aktueller Funktionsumfang:

- native Upload-Form statt aktiver FilePond-Runtime
- Breadcrumbs und konsistente Redirects im aktuellen Ordner
- Ordner anlegen innerhalb des persönlichen Root-Pfads
- Rename-/Move-Aktionen über zentrale Modale mit vorbereiteten Zielordnern
- optionales Löschen eigener Dateien/Ordner abhängig von `member_delete_own`
- Pfad-Normalisierung verhindert Ausbrüche aus dem User-Root

## Dokumentationshinweis

Die Member-Dokumentation ist aktuell bewusst in dieser Datei gebündelt. Für Medien- und Upload-Details sind ergänzend die Admin-/Workflow-Dokumente maßgeblich:

- [../admin/media/README.md](../admin/media/README.md)
- [../workflow/MEDIA-UPLOAD-WORKFLOW.md](../workflow/MEDIA-UPLOAD-WORKFLOW.md)
- [../core/SERVICES.md](../core/SERVICES.md)

## Verwandte Dokumente

- [../admin/subscription/README.md](../admin/subscription/README.md)
- [../admin/security/README.md](../admin/security/README.md)
- [../admin/plugins/README.md](../admin/plugins/README.md)
