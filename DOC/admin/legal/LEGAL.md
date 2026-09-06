# Rechtstexte & Legal-Sites

Kurzbeschreibung: Dokumentiert die aktuelle Verwaltung von Impressum, Datenschutzerklärung, AGB und Widerrufsbelehrung über `/admin/legal-sites` inklusive Vorlagen-Generator und Seitensynchronisation.

Letzte Aktualisierung: 2026-05-11 · Version 2.9.758

Die Verwaltung der Rechtstexte erfolgt über `/admin/legal-sites`. Der Entry-Point `CMS/admin/legal-sites.php` delegiert alle inhaltlichen und generatorbezogenen Aufgaben an `LegalSitesModule`.

Das Modul deckt aktuell vier zentrale Rechtstexte ab:

- Impressum
- Datenschutzerklärung
- AGB
- Widerrufsbelehrung

Ältere Beschreibungen mit separater interner Versionierungstabelle oder eigener Cookie-Richtlinien-Verwaltung auf dieser Seite entsprechen nicht mehr dem aktuellen Kernstand.

## Gespeicherte Kerninhalte

Rechtstexte werden in Settings mit diesen Schlüsseln abgelegt:

- `legal_imprint`
- `legal_privacy`
- `legal_terms`
- `legal_revocation`

Zusätzlich verwaltet das Modul die zugeordneten CMS-Seiten über:

- `imprint_page_id`
- `privacy_page_id`
- `terms_page_id`
- `revocation_page_id`

## Standardprofil für Vorlagen

Ein wesentlicher Teil der aktuellen Implementierung ist das Legal-Profil. Es sammelt Stammdaten, aus denen standardisierte Rechtstexte generiert werden können. Beispiele für gespeicherte Profilwerte sind:

- Firmenname oder privater Name
- Rechtsform
- Inhaber oder Geschäftsführung
- Anschrift
- E-Mail, Telefon, Website
- Registergericht und Registernummer
- Umsatzsteuer-ID
- Hosting-Anbieter
- Datenschutzkontakt
- Zahlungsdienstleister
- Vertragsart und Geltungsbereich der AGB
- Hinweise zu Cookies, Registrierung, Newsletter, Kommentaren, Analytics, Shop und externen Medien

Damit kann das Modul situationsabhängige Textbausteine erzeugen, statt nur starre Muster abzulegen.

## Versionierte DACH-Vorlagenprofile

Seit Version 2.9.758 verwaltet `/admin/legal-sites` zusätzlich ein speicherbares DACH-Vorlagenprofil. Verfügbar sind derzeit:

- Deutschland · DACH-Basis
- Österreich · DACH-Basis
- Schweiz · DACH-Basis
- DACH allgemein · neutrales Skelett

Die Profile liefern bewusst technische Grundgerüste für Impressum, Datenschutzerklärung, Widerrufsbelehrung und AGB-Skelett. Sie enthalten Hinweise auf notwendige Prüfung und ersetzen keine individuelle Rechtsberatung. Beim Generieren oder Erstellen/Aktualisieren einer Legal-Site speichert das Modul pro Bereich zusätzlich:

- angewendetes Profil (`legal_<type>_template_profile`)
- Vorlagenversion (`legal_<type>_template_version`)
- Anwendungszeitpunkt (`legal_<type>_template_applied_at`)

Dadurch ist im Adminbereich nachvollziehbar, welche Vorlage zuletzt auf welchen Rechtstext angewendet wurde. Die Anwendung bleibt eine CSRF-geschützte POST-Aktion; es gibt keine GET-Mutation und keine Sicherheitstoken in URLs.

## Verfügbare Admin-Aktionen

`CMS/admin/legal-sites.php` verarbeitet derzeit folgende Kernaktionen:

- `save` – speichert Rechtstexte und Seitenzuordnungen
- `save_profile` – speichert die Standardwerte des Legal-Profils
- `generate` – erzeugt einen Rechtstext aus den Profilangaben
- `create_page` – erstellt oder aktualisiert eine einzelne CMS-Seite
- `create_all_pages` – erstellt oder aktualisiert alle unterstützten Rechtstext-Seiten gesammelt

Alle POST-Aktionen verwenden den CSRF-Kontext `admin_legal_sites`.

## Automatische Seitenerstellung

Für jeden der vier Dokumenttypen ist eine feste Seitenkonfiguration hinterlegt. Dazu gehören Titel, Ziel-Slug und Meta-Beschreibung. Typische Slugs sind:

- `/impressum`
- `/datenschutz`
- `/agb`
- `/widerruf`

Wenn bereits eine passende Seite existiert, aktualisiert das Modul deren Inhalt. Andernfalls wird sie neu angelegt. Dadurch bleiben Rechtstexte und öffentlich sichtbare CMS-Seiten synchron.

## Synchronisierte Nebeneinstellungen

Beim Zuordnen oder Erstellen bestimmter Seiten aktualisiert das Modul weitere Settings mit funktionaler Relevanz:

- `cookie_policy_url` wird aus der Datenschutz-Seite abgeleitet
- `terms_page_id` wird mit der AGB-Seite synchronisiert
- `cancellation_page_id` wird mit der Widerrufs-Seite synchronisiert

## Generierte Vorlagen

Das Modul erzeugt Vorlagen nicht nur für juristische Pflichttexte, sondern passt sie an das gespeicherte Profil an. Beispiele:

- Impressum mit Firmierung, Vertretung, Kontakt und Registerdaten
- Datenschutzerklärung mit optionalen Hinweisen zu Formularen, Registrierung, Newsletter, Analytics, externen Medien oder Shop-Funktionen
- AGB je nach Zielgruppe (`b2c`, `b2b`, `mixed`) und Vertragsart (`goods`, `services`, `digital`, `mixed`)
- Widerrufsbelehrung abhängig von Vertragsart, Rücksendekosten und ggf. vorzeitigem Leistungsbeginn

## Sicherheit und Validierung

Die aktuelle Legal-Verwaltung setzt auf:

- Admin-Zugriffsschutz via `Auth::instance()->isAdmin()`
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_legal_sites')`
- serverseitige Sanitierung aller Profil- und Inhaltsfelder
- Validierung notwendiger Pflichtfelder vor dem Generieren oder Speichern
- Audit-Logging über `AuditLogger` für Profil-, Inhalts- und Seitenerzeugungsaktionen

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/legal-sites.php` | Admin-Entry-Point |
| `CMS/admin/modules/legal/LegalSitesModule.php` | Profile, Vorlagen, Speichern und Seitensynchronisation |
| `CMS/admin/views/legal/legal-sites.php` | Admin-Oberfläche für Tabs und Formulare |

## Verwandte Dokumente

- [README.md](README.md)
- [DSGVO.md](DSGVO.md)
- [COOKIES.md](COOKIES.md)
- [PAGES.md](../pages-posts/PAGES.md)
