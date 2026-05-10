# Member-Dashboard – Admin-Verwaltung

Kurzbeschreibung: Beschreibt die aktuelle Admin-Konfiguration des Member-Dashboards mit Sektionen, gespeicherten Einstellungen und der Trennung zwischen Verwaltungsoberfläche und Frontend-Mitgliederbereich.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.733

## Überblick

Die zentrale Administrationsoberfläche für den Mitgliederbereich basiert auf `CMS/admin/member-dashboard-page.php` und dem `MemberDashboardModule`. Sie dient nicht primär zur Inhaltsmoderation einzelner Nutzerprofile, sondern zur Konfiguration des gesamten Member-Frontends.

Die Basisroute lautet `/admin/member-dashboard`. Je nach Einbindung kann die Seite in mehrere Unterrouten oder Abschnittsansichten aufgeteilt werden.

## Architektur

Die aktuelle Implementierung trennt klar zwischen:

- Admin-Konfiguration des Member-Bereichs
- Frontend-Dashboard unter `/member/...`
- optionalen Plugin-Erweiterungen über Widgets oder Module

Der Entry-Point lädt die Admin-Daten über `MemberDashboardModule::getData()` und speichert abschnittsweise über `saveSection()`.

Seit `2.9.620` ist der öffentliche Member-Runtime-Pfad davon sauber getrennt: Das Frontend unter `/member/...` liest persistierte Member-Settings über einen eigenen Runtime-Lesepfad (`MemberDashboardModule::getRuntimeSettings()` via `MemberController`), statt an den admin-geschützten Read-Contract der Konfigurationsoberfläche gekoppelt zu sein.

Seit `2.9.732` bietet `/admin/member-dashboard?preview=1` zusätzlich eine read-only Vorschau der gespeicherten Member-Dashboard-Konfiguration. Sie nutzt denselben Settings-Vertrag wie die Runtime, zeigt aber bewusst Beispielwerte statt personenbezogener Live-Daten und erzeugt keine neue POST-Aktion, keinen zusätzlichen CSRF-Token-Pfad und keinen Token in der URL.

Seit `2.9.733` zeigt diese Vorschau zusätzlich die gespeicherte Bereichsreihenfolge sichtbar an und lädt Plugin-Widget-Metadaten im Admin-Übersichtspfad nur einmal pro Request. Dadurch bleibt die Vorschau vollständiger und schlanker, ohne den sicheren read-only Vertrag zu verändern.

## Aktuelle Konfigurationsbereiche

Das Modul unterstützt derzeit insbesondere diese Sektionen:

- `general`
- `widgets`
- `profile-fields`
- `design`
- `frontend-modules`
- `notifications`
- `onboarding`
- `plugin-widgets`

Ältere Beschreibungen, die die Seite primär als Moderationszentrale für private Nachrichten, Medien oder Favoriten darstellen, decken den aktuellen Funktionsschwerpunkt nur noch unvollständig ab.

## Typische Einstellungsinhalte

Je nach Sektion werden unter anderem folgende Aspekte gepflegt:

- allgemeine Aktivierung und Basisoptionen des Member-Bereichs
- verfügbare Dashboard-Widgets
- zusätzliche oder optionale Profilfelder
- Design- und Layout-Optionen für Member-Seiten
- aktivierbare Frontend-Module
- Benachrichtigungslogik und Standardtexte
- Onboarding-Elemente für neue Mitglieder
- plugin-gelieferte Widgets oder Erweiterungsmodule
- read-only Dashboard-Preview mit Welcome-Bereich, gespeicherter Bereichsreihenfolge, Schnellstart, Statistik-Beispielen, Kern-/Info-/Plugin-Widgets, Profilfeldern, Onboarding und Benachrichtigungstexten

Die konkreten Werte werden über zahlreiche `member_*`-Settings persistent gespeichert.

## Request-Handling

Die Seite nutzt aktuell eine einheitliche POST-Verarbeitung:

- CSRF-Kontext: `admin_member_dashboard`
- Aktion: `save`
- Dispatch nach Abschnitt über den gesetzten Member-Section-Kontext

Nach dem Speichern werden Statusmeldungen in `$_SESSION['admin_alert']` hinterlegt und per Redirect wieder ausgegeben.

Der Preview-Modus ist dagegen ein reiner GET-/Lesepfad. Er liest gespeicherte Settings, normalisiert Farben, Reihenfolge, Widgets und Plugin-Sichtbarkeiten serverseitig und speichert keine Daten. Dadurch entstehen weder zusätzliche CSRF-Anforderungen noch Token-Fragilität beim Öffnen, Aktualisieren oder Teilen der Admin-Preview-URL innerhalb einer bestehenden Admin-Sitzung.

## Verhältnis zum Frontend

Diese Admin-Seite konfiguriert das Verhalten des tatsächlichen Mitgliederbereichs, ersetzt ihn aber nicht. Relevante Frontend-Routen liegen weiterhin im Member-Bereich, zum Beispiel:

- `/member`
- `/member/profile`
- `/member/messages`
- `/member/media`
- `/member/security`

Welche Module im Frontend sichtbar oder aktiv sind, wird wesentlich durch die im Admin gespeicherten Member-Einstellungen beeinflusst.

## Erweiterbarkeit

Die aktuelle Struktur ist auf Erweiterungen durch Plugins vorbereitet. Insbesondere der Bereich `plugin-widgets` zeigt, dass der Member-Bereich nicht mehr nur ein fester Kern ist, sondern um zusätzliche Komponenten ergänzt werden kann.

## Sicherheit

Die Admin-Konfiguration folgt dem Standardmuster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_member_dashboard')`
- serverseitige Abschnittsvalidierung im Modul
- Session-basierte Erfolgs- und Fehlermeldungen
- generische Fehlerausgabe bei Schreibfehlern; technische Exception-Texte bleiben aus Audit-/Admin-Ausgaben heraus

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/member-dashboard-page.php` | zentraler Admin-Entry-Point |
| `CMS/admin/modules/member/MemberDashboardModule.php` | Laden und Speichern der Member-Konfiguration |
| `CMS/admin/views/member/dashboard.php` | Ausgabe der Admin-Oberfläche |
| `CMS/admin/views/member/dashboard.php?preview=1` | read-only Vorschau der gespeicherten Runtime-Konfiguration |
| `CMS/member/includes/class-member-controller.php` | Runtime-Laden der Member-Einstellungen für `/member/...` |

## Verwandte Dokumente

- [../../member/README.md](../../member/README.md)
- [../../member/SECURITY.md](../../member/SECURITY.md)
- [../users-groups/USERS.md](../users-groups/USERS.md)
- [../subscription/SUBSCRIPTION-SYSTEM.md](../subscription/SUBSCRIPTION-SYSTEM.md)
