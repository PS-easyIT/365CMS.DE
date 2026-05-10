# 365CMS – Member-Bereich
> **Stand:** 2026-05-10 | **Version:** 2.9.736 | **Status:** Aktuell

<!-- UPDATED: 2026-05-09 -->

## Überblick

Der Member-Bereich ist die benutzerseitige Oberfläche für eingeloggte Mitglieder.
Er bietet Zugriff auf persönliche Einstellungen, Abonnements, Medien und Kommunikation.
Die Implementierung liegt in `CMS/member/` mit eigenem Partial-System für Sidebar und Layout.

Seit `2.9.620` liest der öffentliche Member-Bereich seine Dashboard-Konfiguration wieder über einen eigenen Runtime-Settings-Pfad, der **nicht** an die admin-geschützte Leselogik der Konfigurationsoberfläche gekoppelt ist. Dadurch wirken Schalter aus `/admin/member-dashboard*` – etwa Dashboard-Aktivierung, Frontend-Module, Onboarding und Notification-Center – wieder zuverlässig im echten `/member/dashboard`-Frontend.

Seit `2.9.732` kann die gespeicherte Dashboard-Konfiguration im Admin unter `/admin/member-dashboard?preview=1` read-only geprüft werden. Diese Vorschau ersetzt nicht den echten Member-Bereich, hilft aber beim Gegencheck von Welcome-Bereich, Frontend-Modulen, Kern-/Info-/Plugin-Widgets, Profilfeldern, Onboarding und Benachrichtigungstexten, ohne neue Schreibaktionen oder Sicherheitstoken in URLs zu erzeugen.

Seit `2.9.733` macht die Admin-Preview auch die gespeicherte Bereichsreihenfolge sichtbar und vermeidet wiederholte Plugin-Widget-Metadatenläufe im Übersichtspfad.

Seit `2.9.734` respektiert die Runtime zusätzlich die persistierte Reihenfolge der eigenen Info-Widgets; die Plugin-Reihenfolge bleibt weiterhin konsistent aus den Admin-Einstellungen ableitbar, während die Admin-UI dieselbe Ordnung progressiv per Drag-&-Drop oder Pfeilbuttons pflegbar macht.

Seit `2.9.735` ergänzt die Admin-Seite `/admin/member-dashboard-onboarding` eine rein aggregierte Onboarding-Analyse mit Abschlussrate. Die öffentliche Runtime bleibt unverändert read-only-konfiguriert; das Admin-Reporting nutzt nur bestehende Signale wie Profil-Vervollständigung, MFA-/Passkey-Status und jüngste Login-Aktivität, ohne einzelne Mitglieder im UI offenzulegen oder neue Tracking-Daten zu schreiben.

Seit `2.9.736` verwendet `/member/subscription` zusätzlich einen zentralen Renewal-/Ablauf-Vertrag aus `SubscriptionManager`: Laufzeitende und nächste Verlängerung werden read-only aus `next_billing_date` bzw. `end_date` sowie den globalen Abo-Einstellungen abgeleitet, statt an einem dekorativen View-Feld vorbeizulaufen.

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

## Dashboard-Konfiguration im Stand 2.9.620

Die Dashboard-Oberfläche unter `/member/dashboard` hängt funktional an zwei Ebenen:

- **Admin-Konfiguration** unter `/admin/member-dashboard*`
- **öffentliche Runtime** über `CMS\MemberArea\MemberController`

Aktueller Vertragsstand:

- gespeicherte `member_*`-Settings werden im Frontend unabhängig von Admin-Read-Capabilities geladen
- die reine Modulaktivierung `member_dashboard` bleibt zusätzliches Laufzeit-Gate
- deaktivierte oder fehlende Plugin-Widgets fallen fail-soft aus dem Dashboard, statt den Member-Bereich zu blockieren
- Profil-Fortschritt, Onboarding und Benachrichtigungscards greifen auf denselben Settings-Stand zu wie die Admin-Konfiguration
- die Admin-Preview unter `/admin/member-dashboard?preview=1` rendert nur gespeicherte Runtime-Settings mit Beispielwerten, sichtbarer Bereichsreihenfolge und fail-soft Verhalten bei unbekannten Widgets oder deaktivierten Plugin-Kacheln
- eigene Info-Widgets folgen der gespeicherten Admin-Reihenfolge; fehlende Slots bleiben unkritisch und werden nicht als Fehler 500 hochgezogen
- die Admin-Onboarding-Analytics auf `/admin/member-dashboard-onboarding` lesen bestehende Profildaten, MFA-/Passkey-Signale und – falls vorhanden – Login-Aktivität nur aggregiert aus und führen keine neue Schreib- oder Tracking-Strecke ein

## Abo-Zuweisung im Stand 2.9.621

Wenn unter `/admin/subscription-settings` ein aktives Standardpaket hinterlegt ist, wird dieses seit `2.9.621` automatisch auf neue Mitgliedskonten angewendet:

- bei öffentlichen Registrierungen
- beim Anlegen neuer Member-Konten im Admin

Der Vertragsstand bleibt dabei fail-soft:

- bestehende aktive oder Trial-Abos werden nicht überschrieben
- nur aktive Paketreferenzen werden automatisch übernommen
- der Member-Bereich kann das zugewiesene Paket anschließend konsistent über `/member/subscription` und die Limit-Logik auswerten, inklusive read-only Laufzeit- und Renewal-Hinweisen für aktive Verträge

## Authentifizierung im Stand 2.9.0

Der Member-Bereich hängt loginseitig nicht mehr am aktiven Frontend-Theme, sondern an der CMS-eigenen Auth-Strecke:

- Login: `/cms-login`
- Registrierung: `/cms-register`
- Passwort-Reset: `/cms-password-forgot`

Wichtige Auswirkungen:

- MFA-/TOTP-Benutzer, Passkey-Logins, Backup-Codes und LDAP-Logins finalisieren jetzt denselben Session-Vertrag wie normale Passwort-Logins.
- Der Schalter **„Angemeldet bleiben“** ist seit `2.9.0` ein echter Persistenzpfad.
- Theme-Wechsel beeinflussen die Member-Anmeldung nicht mehr direkt, weil die Auth-Seiten aus dem Core gerendert werden.

## Member-Medien im Stand 2.9.0

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
- [../admin/users-groups/AUTH-SETTINGS.md](../admin/users-groups/AUTH-SETTINGS.md)
- [../admin/themes-design/CMS-LOGINPAGE.md](../admin/themes-design/CMS-LOGINPAGE.md)
- [../workflow/MEDIA-UPLOAD-WORKFLOW.md](../workflow/MEDIA-UPLOAD-WORKFLOW.md)
- [../core/SERVICES.md](../core/SERVICES.md)

## Verwandte Dokumente

- [../admin/subscription/README.md](../admin/subscription/README.md)
- [../admin/security/README.md](../admin/security/README.md)
- [../admin/plugins/README.md](../admin/plugins/README.md)
