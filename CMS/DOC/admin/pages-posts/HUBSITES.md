# Hub-Sites

Kurzbeschreibung: Verwaltung thematischer Hub-Sites und Landing-Hubs über `/admin/hub-sites`.

Letzte Aktualisierung: 2026-05-24 · Version 3.0.31

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/hub-sites` |
| Entry Point | `CMS/admin/hub-sites.php` |
| Modul | `CMS/admin/modules/hub/HubSitesModule.php` |
| Public Runtime | `CMS/core/Services/SiteTable/SiteTableHubRenderer.php` |
| Repository | `CMS/core/Services/SiteTable/SiteTableRepository.php` |
| Datenbank | `cms_site_tables` mit `content_mode = hub` |
| CSRF-Kontext | `admin_hub_sites` |

---

## Zweck und Datenmodell

Hub-Sites nutzen denselben Basisspeicher wie Site Tables, unterscheiden sich aber über den Inhaltsmodus. Ein Hub besitzt typischerweise:

- einen internen Namen
- einen öffentlichen Hub-Slug
- optional eine eigene Domain oder Zusatzdomain
- Layout-/Template-Einstellungen
- Karten- und Inhaltsdaten
- optionale Quicklinks und Inhaltsverzeichnis-Daten

Die Public-Ausgabe wird nicht als einfache Tabelle gerendert, sondern als Hub-Seite mit Hero, Kartenlisten, Quicklinks und optionalem Inhaltsverzeichnis.

### Template-Profil `services`

Seit `3.0.29` gibt es das HubSite-Template-Profil `services` für Dienstleistungs- und Landing-Hubs. Es liefert:

- service-orientierte Meta-Labels (`Zielgruppe`, `Ansprechpartner`, `Aktualität`, `Leistungsschwerpunkt`, `Ergebnis`)
- Quicklinks für Beratung, Microsoft 365, CMS/Web, Betrieb, Workshops und Kontakt
- drei Einstiegsektionen für Roadmap, Umsetzung/Betrieb und Enablement
- Starter-Kacheln für IT-Strategie, Microsoft 365 & Security sowie 365CMS/Web-Plattformen

Seit `3.0.30` ist das Profil im HubSite-Admin zusätzlich sichtbar in den Template-Varianten beschrieben. Services-basierte Templates unterstützen im Card-Editor Feature-Kacheln in Vollbreite und zeigen in der Template-Vorschau eigene Dienstleistungs-/Delivery-Beschriftungen.

Seit `3.0.31` kann die Card-Rundung direkt im HubSite-Template per Zahlfeld und Slider angepasst werden. Der Live-Preview übernimmt den Radius sofort, neue Templates ziehen den Radius-Default des gewählten Basis-Layouts nach, und einzelne HubSites können den Template-Wert optional mit einem eigenen Radius überschreiben.

Themes können das Profil über die vorhandenen HubSite-CSS-Klassen, insbesondere `.cms-hub-site--services`, visuell weiter zuschneiden.

---

## Aktueller Vertragsstand

### Slug-Schutz

Hub-Slugs werden serverseitig gegen reservierte Systempfade geprüft. Dazu gehören nicht nur offensichtliche Admin-Pfade, sondern auch reale Public-Routen und Archivbasen, zum Beispiel:

- `contact` / `kontakt`
- `feed`
- `authors` / `author` / `autoren`
- `category` / `tag`
- `sitemap`, `sitemap.xml`, `robots.txt`, `security.txt`
- lokalisierte Archivbasen aus dem aktuellen Routing

Damit werden Hub-Sites nicht mehr mit Slugs gespeichert, die später mit existierenden Public-Routen kollidieren und dadurch praktisch unerreichbar wären.

### Legacy- und Zusatzdomain-Fälle

Ältere Datensätze enthalten teilweise nur `table_slug`, aber kein `settings.hub_slug`. Die Runtime behandelt diese Fälle jetzt wieder konsistent:

- Repository-Hydration übernimmt `table_slug` als Fallback in die Settings
- Domain-Auflösung kann Legacy-Hubs wieder finden
- bestehende Hubs bleiben damit auch ohne nachträgliche manuelle Datenmigration erreichbar

### Lokalisierung im Frontend

Quicklinks, Inhaltsverzeichnis-Labels und Standard-CTA-Texte folgen im Public-Rendering jetzt dem aktuellen Request-Locale. Englische Hub-Aufrufe bleiben damit nicht mehr an deutschen Fallback-Strings hängen.

---

## Sicherheit und Validierung

- Admin-Zugriffsschutz
- CSRF-Prüfung für Schreibaktionen
- serverseitige Slug-Sanitierung
- Reserved-Slug-Prüfung vor dem Speichern
- Redirect- und Rückmeldungsfluss über den konsolidierten Admin-Flow

---

## Verwandte Seiten

- [TABLES.md](TABLES.md)
- [README.md](README.md)
- [../landing-page/LANDING-PAGE.md](../landing-page/LANDING-PAGE.md)
