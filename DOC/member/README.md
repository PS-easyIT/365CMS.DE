# 365CMS – Mitgliederbereich

Kurzbeschreibung: Übersicht über den persönlichen Bereich für eingeloggte Benutzer inklusive Dashboard, Profil, Kommunikation, Datenschutz und Abonnements.

Letzte Aktualisierung: 2026-03-07

---

## Überblick

Der Mitgliederbereich liegt unter `/member` und bündelt alle self-service-orientierten Funktionen für registrierte Benutzer. Er ist technisch vom klassischen Admin getrennt, nutzt aber gemeinsame Core-Services für Authentifizierung, Sicherheit, Datenbankzugriff und Plugin-Erweiterungen.

---

## Aktuelle Routen

| Route | Datei | Zweck |
|---|---|---|
| `/member` | `member/index.php` | Dashboard |
| `/member/profile` | `member/profile.php` | Profildaten |
| `/member/media` | `member/media.php` | eigene Medien |
| `/member/messages` | `member/messages.php` | Nachrichten |
| `/member/notifications` | `member/notifications.php` | Benachrichtigungen |
| `/member/subscription` | `member/subscription.php` | Abo und Pakete |
| `/member/favorites` | `member/favorites.php` | Favoriten |
| `/member/privacy` | `member/privacy.php` | Datenschutz |
| `/member/security` | `member/security.php` | Sicherheit |
| `/member/orders` | `member/order_public.php` | Bestellungen |

Weitere Hilfsdateien sind u. a. `media-ajax.php`, `media_handler.php`, `plugin-section.php` und `includes/class-member-controller.php`.

---

## Zugriff und Verhalten

Der Bereich verlangt einen eingeloggten Benutzer. Anders als in älteren Dokumentationsständen werden Administratoren dabei nicht grundsätzlich ausgesperrt; sie können den Member-Bereich weiterhin aufrufen und erhalten dort zusätzlich Verweise in Richtung Admin.

Wesentliche Punkte:

- Login-Pflicht über `Auth::instance()->isLoggedIn()`
- zentrale Weiterleitung über `MemberController::redirect()`
- gemeinsame Sicherheits- und Formularlogik in `MemberController`
- Initialisierung der `PluginDashboardRegistry` bereits im Konstruktor

---

## Funktionsbereiche

| Bereich | Beschreibung |
|---|---|
| Dashboard | Willkommens-, Aktivitäts- und Widget-Bereich |
| Profil | persönliche Daten und Metainformationen |
| Medien | eigene Uploads und Speicherverbrauch |
| Nachrichten | Direktnachrichten und Konversationsübersichten |
| Benachrichtigungen | Präferenzen und Verlauf |
| Subscription | Paketansicht und Upgrades |
| Datenschutz | Export- und Löschprozesse |
| Sicherheit | Passwort, 2FA, Sitzungen und Login-Historie |

---

## Architektur in Kurzform

Der Member-Bereich ist filebasiert aufgebaut:

- Request-Dateien unter `CMS/member/`
- Basislogik in `CMS/member/includes/`
- Template-Dateien unter `CMS/member/partials/`

Zentrale Basisklasse ist `MemberController`. Sie stellt u. a. bereit:

- `generateToken()`
- `verifyToken()`
- `getPost()`
- `isChecked()`
- `render()`
- `handleSecurityActions()`
- `handleNotificationActions()`
- `handlePrivacyActions()`

---

## Plugin-Erweiterbarkeit

Der Bereich ist explizit auf Erweiterbarkeit ausgelegt. Besonders relevant sind:

- zusätzliche Sidebar-Einträge über `member_menu_items`
- Dashboard-Widgets über `member_dashboard_widgets`
- Benachrichtigungssektionen über `member_notification_settings_sections`
- zusätzliche Präferenzwerte über `member_notification_preferences`

---

## Weiterführende Dokumente

| Dokument | Schwerpunkt |
|---|---|
| [CONTROLLERS.md](CONTROLLERS.md) | Controller, Datenübergaben, POST-Aktionen |
| [VIEWS.md](VIEWS.md) | Partials, Variablen und Renderlogik |
| [HOOKS.md](HOOKS.md) | Hooks für Plugins und Erweiterungen |
| [SECURITY.md](SECURITY.md) | Sicherheitsmodell des Member-Bereichs |
| [general/DASHBOARD.md](general/DASHBOARD.md) | Dashboard-Details |
| [general/PROFILE.md](general/PROFILE.md) | Profilfelder und Self-Service |
| [general/SECURITY.md](general/SECURITY.md) | Sicherheitsfunktionen im Detail |
| [general/NOTIFICATIONS.md](general/NOTIFICATIONS.md) | Notification-Logik und Präferenzen |
| [general/PRIVACY.md](general/PRIVACY.md) | Datenschutz und DSGVO-Prozesse |
| [general/SUBSCRIPTION.md](general/SUBSCRIPTION.md) | Abo- und Paketbezug |

