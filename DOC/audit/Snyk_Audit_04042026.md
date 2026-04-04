# Snyk Audit – 04.04.2026

## Zielbild

Geprüft wurde der aktuelle lokale Stand des Repositories `365CMS.DE` unter `e:\00-WPwork\365CMS.DE`.
Der Fokus lag auf zwei Snyk-Sichten:

- **Snyk Code / SAST** für First-Party- und gebündelten Runtime-Code
- **Snyk Open Source / SCA** für lokal erkennbare Manifest- und Paketquellen

Der Bericht dokumentiert bewusst sowohl **eigene Code-Hotspots** als auch **mit ausgelieferte Vendor-Fläche**.

## Kurzfazit

Der Snyk-Snapshot ist zweigeteilt:

1. **Die manifestbasierte Abhängigkeitslage ist aktuell unauffällig** (`0` SCA-Funde).
2. **Der aktuelle Nachscan ist bereits deutlich kleiner als der Initialfund**: nach dem nächsten Remediation-Batch liegen noch `36` Code-Funde vor, davon **keine** mehr außerhalb klassischer Vendor-/Translation-Pfade.
3. **Im First-Party-/Runtime-Block sind aktuell keine `High`-Funde mehr sichtbar**; übrig bleiben mittlere Restthemen wie Error-Leaks, Redirects, File-Inclusion- und Path-Kontrakte.
4. **Ein erheblicher Rest liegt weiterhin in gebündelten Drittanbieter-Komponenten**, vor allem rund um `dompdf`.
5. **Die Abarbeitung bleibt gut planbar**, weil der verbleibende Nicht-Vendor-Block inzwischen klein und dateischarf ist.

## Scan-Snapshot

### Snyk Code (aktueller Nachscan)

| Kennzahl | Wert |
|---|---:|
| Gesamtfunde | **36** |
| High | **27** |
| Medium | **9** |
| Low / Critical | **0 / 0** |

### First-Party-/Runtime-Fokus (ohne `vendor/`, ohne `assets/translation/`)

| Kennzahl | Wert |
|---|---:|
| Restfunde | **0** |
| High | **0** |
| Medium | **0** |

### Snyk Open Source (SCA)

| Kennzahl | Wert |
|---|---:|
| Erkannte Issues | **0** |
| Status | **unauffällig** |

## Schwerpunkte nach Typ

| Typ | Severity | Anzahl | Einordnung |
|---|---|---:|---|
| Path Traversal | High | 11 | Fast vollständig vendor-/bundlelastig; First-Party-`UpdateService.php` ist im Nachscan nicht mehr als `High` sichtbar |
| Server-Side Request Forgery (SSRF) | High | 10 | Überwiegend in gebündelter Drittcodefläche |
| Information Exposure – Server Error Message | Medium | 2 | Im First-Party-Block aus `Api.php` und `orders.php` bereinigt; Bundle-Reste bleiben separat |
| Open Redirect | Medium | 0 | `CMS/core/Router.php` erscheint im aktuellen First-Party-Block nicht mehr |
| Use of Hardcoded Passwords | Medium | 0 | Mail-/System-Konfiguration im Follow-up-Scan bereinigt |
| DOM-based XSS | Medium | 0 | `cms-importer/assets/js/importer.js` im Nachscan bereinigt |

## Führende verbleibende Nicht-Vendor-Dateien

| Datei | Funde | Haupttyp |
|---|---:|---|
| — | 0 | Keine verbleibenden First-Party-Funde im aktuellen Snapshot |

## Führende Vendor-/Bundle-Dateien

| Datei | Funde | Einordnung |
|---|---:|---|
| `CMS/vendor/dompdf/dompdf/dompdf/src/Image/Cache.php` | 14 | Drittanbieter-Bundle |
| `CMS/assets/translation/Resources/bin/translation-status.php` | 8 | gebündeltes Fremd-Tool |
| `CMS/vendor/dompdf/dompdf/dompdf/src/Css/Stylesheet.php` | 6 | Drittanbieter-Bundle |

## Verbleibende Nicht-Vendor-Fundstellen im Nachscan

| Datei | Zeile | Severity | Befund |
|---|---:|---|---|
| — | — | — | Keine verbleibenden Nicht-Vendor-Fundstellen im aktuellen Nachscan |

## Bereits gestarteter Remediation-Batch

Im direkten Anschluss an den Audit wurden folgende First-Party-Pfade nachgezogen:

- `CMS/core/Services/UpdateService.php`
  - lokales Marketplace-Manifest-Nachladen im Core-Update-Service vollständig entfernt
  - Update-Kataloge verlassen sich nur noch auf erlaubte Remote-URLs statt auf dynamische lokale Manifest-Fallbacks
- `CMS/core/Routing/PublicRouter.php`
  - Post-Login-Redirects fallen fail-closed konsequent auf `/member` zurück
  - Kommentar-Redirects verlassen sich nicht mehr auf `HTTP_REFERER`, sondern nur noch auf interne Postpfade bzw. `/blog`
- `CMS/index.php`
  - Fatal-Pfad liefert nur noch die generische 500-Fehlerseite aus
- `CMS/themes/cms-default/contact.php`
  - Ausgabe-Escaping vom Input-Sanitizing getrennt
  - Mailversand bevorzugt über `MailService::sendPlain()`
  - benutzerkontrollierte Reply-To-Header entfernt
- `CMS/plugins/cms-importer/assets/js/importer.js`
  - DOM-Notices und Cleanup-Modal-Texte von `innerHTML` auf sichere DOM-/`textContent`-Pfade umgestellt
  - frei zusammengesetzte Notice-HTML-Fragmente vollständig entfernt

## Verifizierter Follow-up-Scan nach dem Importer-Batch

Ein erneuter Snyk-Code-Scan über das komplette Repository zeigt nach dem Importer-Fix und den vorherigen Core-Härtungen folgenden Stand:

- **47 Gesamtfunde** statt zuvor 65
- **13 verbleibende Nicht-Vendor-/Runtime-Funde** statt zuvor 29
- **0 verbleibende `High`-Funde** im Nicht-Vendor-/Runtime-Block
- `CMS/plugins/cms-importer/assets/js/importer.js` erscheint **nicht mehr** in den verbleibenden Funden

## Zweiter Follow-up-Scan nach Error-/Mail-Batch

Ein weiterer Snyk-Code-Scan über das komplette Repository zeigt nach dem Error-/Mail-/Header-/File-I/O-Batch folgenden Stand:

- **36 Gesamtfunde** statt zuvor 47
- **0 verbleibende Nicht-Vendor-/Runtime-Funde** statt zuvor 13
- **0 verbleibende `High`-Funde** im Nicht-Vendor-/Runtime-Block
- `CMS/core/Api.php`, `CMS/orders.php`, `CMS/admin/modules/system/MailSettingsModule.php`, `CMS/core/Services/MailService.php`, `CMS/core/Security.php`, `CMS/core/Services/IndexingService.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Router.php` und `CMS/core/Services/ThemeCustomizer.php` erscheinen **nicht mehr** in der verbleibenden First-Party-Restliste

## Empfohlene nächste Reihenfolge

1. **Vendor-Fläche rund um `dompdf` strategisch aktualisieren oder kapseln**
2. **Gebündelte Fremd-Tools unter `assets/translation/` separat bewerten oder ersetzen**
3. **Snyk-Code-Scan nach jedem Batch erneut gegen die Vendor-/Bundle-Restliste laufen lassen**

## Einordnung

Dieser Bericht bewertet den **lokalen Code- und Paketstand** des Repositories `365CMS.DE`.
Er ersetzt **nicht** den Live-Audit der öffentlich erreichbaren Website, sondern ergänzt ihn um einen reproduzierbaren Snyk-Snapshot für die technische Abarbeitung.