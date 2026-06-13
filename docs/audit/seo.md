# SEO

**Bereichsscore:** 82/100

## Kurzfazit
SEO-Module, Sitemap-Services und Analytics-/Consent-Integration sind vorhanden. Risiken liegen in Performance-nahen SEO-Faktoren, Alt-Text-/Medien-Metadaten-Abdeckung und möglicher Inkonsistenz zwischen Theme-Templates und zentralen SEO-Services.

## Score-Begründung
- Startwert 100
- SEO-001: -6 (mittel)
- SEO-002: -5 (mittel)
- SEO-003: -4 (niedrig)
- SEO-004: -3 (niedrig)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| SEO-001 | Theme | Startseite | Tagcloud/Sidebar | mittel | -6 | CMS\themes\cms-default\home.php:111-121 | Codefund |
| SEO-002 | Media | Alt-Texte | normalizeAltText/media table | mittel | -5 | CMS\admin\modules\media\MediaModule.php:2775-2805 | Codefund |
| SEO-003 | SEO | Sitemap | SitemapService | niedrig | -4 | CMS\core\Services\SitemapService.php, CMS\core\Services\SEO\SeoSitemapService.php | Codefund |
| SEO-004 | Legal/Analytics | Consent/Tracking IDs | Placeholder-Erkennung | niedrig | -3 | CMS\admin\modules\legal\CookieManagerModule.php:816-828, 916-978 | Codefund |

## Umsetzungsschritte

### step-001
- **Ziel:** Performance-relevante SEO-Signale verbessern.
- **Befund:** Homepage-Tagcloud aggregiert Tags synchron aus Post-Daten.
- **Risiko:** Schlechtere Core-Web-Vitals bei großen Datenbeständen.
- **Technische Ursache:** Keine voraggregierte/cached Tag-Struktur.
- **Lösungsweg:** Tagcloud cachen und Cache bei Post-/Tag-Änderungen invalidieren.
- **Betroffene Dateien:** CMS\themes\cms-default\home.php.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Cache-Service.

### step-002
- **Ziel:** Medien-SEO messbar absichern.
- **Befund:** Alt-Text-Normalisierung ist vorhanden, aber Audit fand keine Pflichtprüfung, die fehlende Alt-Texte als Qualitätsproblem erzwingt.
- **Risiko:** Barrierefreiheits- und Bild-SEO-Lücken bleiben unentdeckt.
- **Technische Ursache:** Metadatenverwaltung ohne ersichtliche Vollständigkeits-Gates.
- **Lösungsweg:** SEO-Audit-Regel für Bilder ohne Alt-Text, Bulk-UI und Warnungen in EditorJS/Media-Library.
- **Betroffene Dateien:** CMS\admin\modules\media\MediaModule.php, CMS\core\Services\Media\*.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Media-Metadatenmodell.

### step-003
- **Ziel:** Sitemap-/Canonical-Konsistenz validieren.
- **Befund:** Mehrere Sitemap-Services/Views existieren.
- **Risiko:** Doppelte oder widersprüchliche Sitemap-Ausgaben bei divergierender Logik.
- **Technische Ursache:** SEO-Funktionalität ist über Core-Service, SEO-Service und Admin-View verteilt.
- **Lösungsweg:** Eine kanonische Sitemap-Quelle definieren und Tests für URLs, Status, Priorität, lastmod ergänzen.
- **Betroffene Dateien:** CMS\core\Services\SitemapService.php, CMS\core\Services\SEO\SeoSitemapService.php, CMS\admin\views\seo\sitemap.php.
- **Priorität:** P3
- **Aufwand:** M
- **Abhängigkeiten:** Routing/Permalinks.

### step-004
- **Ziel:** Tracking-Konfiguration produktionssicher machen.
- **Befund:** Placeholder-Erkennung für Analytics-IDs ist vorhanden.
- **Risiko:** Falsch konfigurierte IDs können unbemerkt produktiv bleiben.
- **Technische Ursache:** Konfigurationsvalidierung wirkt formular-/runtime-nah, nicht als Release-Gate.
- **Lösungsweg:** SEO/Consent-Healthcheck mit Status und Deploy-Warnung ergänzen.
- **Betroffene Dateien:** CMS\admin\modules\legal\CookieManagerModule.php, CMS\admin\views\seo\analytics.php.
- **Priorität:** P3
- **Aufwand:** S
- **Abhängigkeiten:** Admin-Dashboard.
