# Member-Dashboard – Admin-Verwaltung

Kurzbeschreibung: Beschreibt die aktuelle Admin-Konfiguration des Member-Dashboards mit Sektionen, gespeicherten Einstellungen und der Trennung zwischen Verwaltungsoberfläche und Frontend-Mitgliederbereich.

Letzte Aktualisierung: 2026-05-13 · Version 2.9.782

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

Seit `2.9.734` lassen sich Kern-Widgets, eigene Info-Widgets und Plugin-Widgets zusätzlich sortieren. Die Admin-UI nutzt Drag-&-Drop als Komfortpfad und Auf/Ab-Buttons als Fallback; gespeichert wird ausschließlich über den bestehenden POST-/CSRF-Flow, serverseitig allowlist-validiert und ohne neue GET-Mutationen.

Seit `2.9.735` zeigt `/admin/member-dashboard-onboarding` zusätzlich read-only Onboarding-Analytics mit aggregierter Abschlussrate. Die Kennzahlen werden aus bestehenden Signalen wie aktuell aktiven Konten, konfigurierten Profilfeldern, MFA-/Passkey-Adoption und erfolgreichen Logins der letzten 30 Tage abgeleitet. Es gibt bewusst keine neue Tracking-Tabelle, keine zusätzliche Schreibroute und keine personenbezogene Einzelauflistung.

Seit `2.9.782` zeigt `/admin/member-dashboard-profile-fields` zusätzlich eine read-only Kompatibilitätsvorschau für Profilfeld-Änderungen. Admins sehen vor dem Speichern, wie viele aktive Konten durch neue Completion-Felder voraussichtlich unvollständig werden und erhalten begrenzte Beispielkonten zur Support-Orientierung. Optional kann im bestehenden POST-/CSRF-Speicherpfad der vorhandene Onboarding-/Profilabschluss-Hinweis reaktiviert werden; dabei werden keine Profile geändert und keine E-Mails versendet.

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
- Kompatibilitätsvorschau für Profilfeld-/Completion-Änderungen mit optionalem Onboarding-Re-Trigger
- Design- und Layout-Optionen für Member-Seiten
- aktivierbare Frontend-Module
- Benachrichtigungslogik und Standardtexte
- Onboarding-Elemente für neue Mitglieder
- plugin-gelieferte Widgets oder Erweiterungsmodule
- persistente Reihenfolge für Kern-Widgets, eigene Info-Widgets und Plugin-Widgets
- read-only Onboarding-Analytics mit Abschlussrate, Security-Adoption und Aktivitätsquote
- read-only Dashboard-Preview mit Welcome-Bereich, gespeicherter Bereichsreihenfolge, Schnellstart, Statistik-Beispielen, Kern-/Info-/Plugin-Widgets, Profilfeldern, Onboarding und Benachrichtigungstexten

Die konkreten Werte werden über zahlreiche `member_*`-Settings persistent gespeichert.

## Request-Handling

Die Seite nutzt aktuell eine einheitliche POST-Verarbeitung:

- CSRF-Kontext: `admin_member_dashboard`
- Aktion: `save`
- Dispatch nach Abschnitt über den gesetzten Member-Section-Kontext

Nach dem Speichern werden Statusmeldungen in `$_SESSION['admin_alert']` hinterlegt und per Redirect wieder ausgegeben.

Der Preview-Modus ist dagegen ein reiner GET-/Lesepfad. Er liest gespeicherte Settings, normalisiert Farben, Reihenfolge, Widgets und Plugin-Sichtbarkeiten serverseitig und speichert keine Daten. Dadurch entstehen weder zusätzliche CSRF-Anforderungen noch Token-Fragilität beim Öffnen, Aktualisieren oder Teilen der Admin-Preview-URL innerhalb einer bestehenden Admin-Sitzung.

Die neue Widget-Sortierung bleibt davon getrennt: Sie läuft ausschließlich über den vorhandenen `save`-POST, nutzt denselben CSRF-Kontext `admin_member_dashboard`, nimmt nur bekannte Widget-Keys bzw. Slot-IDs an, entfernt Duplikate, ergänzt fehlende bekannte Werte kontrolliert und bleibt dadurch auch bei unvollständigen Browserdaten oder deaktivierten Erweiterungen fail-soft.

Die Onboarding-Analytics folgen demselben read-only Prinzip: Sie lesen nur bereits vorhandene Datenquellen (`users`, `user_meta`, optional `passkey_credentials`, optional `activity_log`), verdichten diese serverseitig zu Quoten/KPIs und fallen bei fehlenden optionalen Tabellen auf sichere Defaultwerte zurück. Technische Fehlerdetails bleiben serverseitig geloggt und erscheinen nicht roh im Admin-UI.

Die Profilfeld-Kompatibilitätsvorschau folgt demselben Sicherheitsvertrag: Sie liest `users` und `user_meta` aggregiert, begrenzt Beispielkonten pro Feld, schreibt keine Profildaten und nutzt für den optionalen Onboarding-Re-Trigger ausschließlich den bestehenden `save`-POST mit `admin_member_dashboard`-CSRF. Der Re-Trigger setzt nur die vorhandenen Onboarding-/Profilabschluss-Schalter, statt Benutzer einzeln zu markieren oder Mails zu versenden.

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
| Route `/admin/member-dashboard?preview=1` | read-only Vorschau der gespeicherten Runtime-Konfiguration |
| `CMS/admin/views/member/onboarding.php` | Onboarding-Konfiguration plus read-only Analytics-/Abschlussraten-Karten |
| `CMS/admin/views/member/profile-fields.php` | Profilfeld-Auswahl mit read-only Kompatibilitätsvorschau und optionalem Onboarding-Re-Trigger |
| `CMS/admin/views/member/widgets.php` | Kern-Widgets, Spalten, Bereichsreihenfolge und sortierbare Info-Widgets |
| `CMS/admin/views/member/plugin-widgets.php` | Plugin-Widgets mit Sichtbarkeit und sortierbarer Reihenfolge |
| `CMS/assets/js/admin-member-dashboard.js` | Drag-&-Drop- und Button-Fallback für Widget-Sortierung |
| `CMS/member/includes/class-member-controller.php` | Runtime-Laden der Member-Einstellungen für `/member/...` |

## Verwandte Dokumente

- [../../member/README.md](../../member/README.md)
- [../../member/SECURITY.md](../../member/SECURITY.md)
- [../users-groups/USERS.md](../users-groups/USERS.md)
- [../subscription/SUBSCRIPTION-SYSTEM.md](../subscription/SUBSCRIPTION-SYSTEM.md)
