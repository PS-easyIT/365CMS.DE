# 365CMS CMS-Audit – 23.03.2026

> **Scope:** `365CMS.DE/CMS`  
> **Live-Prüfpfad:** `https://phinit.de/`  
> **Prüfstand:** Code-Review im Workspace + Live-Site-Stichprobe ohne Admin-/Member-Login auf Produktion  
> **Scope-Regel für die erweiterte Vollprüfung:** Die Root-Verzeichnisse `CMS/vendor/` und `CMS/themes/` wurden bewusst **ausgeschlossen**; aus `CMS/assets/` wurden nur `assets/css/` und `assets/js/` berücksichtigt.

---

## Kurzfazit

Der CMS-Kern präsentiert sich am **23.03.2026** weiterhin in einem insgesamt **stabilen und belastbaren Zustand**. Die öffentliche PhinIT-Live-Site bestätigt, dass die zentralen Frontend-, Legal-, Feed-, Login- und Registrierungsrouten produktiv erreichbar sind und die jüngsten CMS-Ausbauwellen – insbesondere bei Routing, Archivlogik, SEO-Features und öffentlicher Formularführung – grundsätzlich sauber auf der Produktionsinstanz ankommen.

Im Vergleich zum Audit vom **13.03.2026** hat sich der Schwerpunkt sichtbar verschoben:

1. **Weniger akute Security-/Runtime-Blocker**, mehr **Release-/Betriebsdisziplin**.
2. Der größte neue formale Befund ist ein **Versions-/Release-Drift** zwischen Changelog/Git-Historie und den weiterhin auf `2.6.0` stehenden Core-Konstanten.
3. Die größten technischen Restlasten sitzen nicht mehr primär in klassischen Sicherheitslücken, sondern in **großen Wartungsblöcken** wie `CMS/includes/functions.php`, `CMS/install.php`, `CMS/core/Services/UpdateService.php` sowie den jüngst stark gewachsenen Importer-/Routing-Pfaden.
4. Die nachgezogene Vollprüfung ohne Root-`assets/vendor` verschiebt den Fokus zusätzlich auf **Betriebsartefakte** wie Logs und Upload-Beispiele, die im Laufzeitbaum liegen und sauber von Release-Artefakten getrennt werden sollten.

**Gesamtbewertung dieses Prüflaufs:**

- **Produktion öffentlich:** funktionsfähig und konsistent
- **Core-Architektur:** solide, aber mit klaren Rest-Hotspots
- **Release-Reife:** technisch gut, organisatorisch durch Versionsdrift derzeit unnötig unscharf
- **Sofortiger Handlungsdruck:** **mittel**, nicht **kritisch**

---

# 365CMS – Fileinventar

## Überblick

Die vollständige, verifizierte Dateiliste wird im Stand **23.03.2026** im separaten Dokument `DOC/audit/FILEINVENTAR.md` geführt. Die im Audit eingebettete Kopie wurde auf einen kompakten, belastbaren Überblick reduziert, damit Scope-Änderungen nicht erneut an drei Stellen auseinanderlaufen.

- **Ausgeschlossen:** `vendor/`, `themes/`
- **Aus `assets/` berücksichtigt:** nur `css/` und `js/`
- **Verifizierter Scope:** `419` Dateien

## Verifizierte Bestandszahlen

| Bereich | Dateien |
|---|---:|
| Root-Entrypoints | 7 |
| `assets/css/` | 8 |
| `assets/js/` | 15 |
| `admin/` | 231 |
| `config/` | 4 |
| `core/` | 117 |
| `includes/` | 2 |
| `lang/` | 2 |
| `logs/` | 2 |
| `member/` | 17 |
| `plugins/` | 11 |
| `uploads/` | 3 |

Wenn sich der Prüfscope ändert, müssen **Zählung**, **Bereichstabelle** und **Dateiliste** gemeinsam aktualisiert werden. Audit- und ToDo-Dokumente sollten diese Liste nur referenzieren oder aus derselben verifizierten Quelle ableiten.

## Einordnung

- `CMS/admin/` und `CMS/core/` bleiben die größten Funktionsblöcke.
- `CMS/logs/` enthält im verifizierten Ist-Zustand **nur** `.gitignore` und `.htaccess` – keine aktuellen Laufzeit-Logs.
- Das Audit referenziert für die vollständige 1:1-Dateiliste bewusst das eigenständige Inventar-Dokument, um Copy-&-Paste-Drift zu vermeiden.
Zusätzlich wurden die jüngsten Commits seit `2026-03-17` auf Schwerpunktverschiebungen geprüft, insbesondere in:

- SEO / IndexNow
- Routing / Archive / Mehrsprachigkeit
- Content-/Slug-/Lokalisierungslogik

Die erweiterte Prüfliste referenziert dafür ausschließlich den verifizierten First-Party-Scope aus `DOC/audit/FILEINVENTAR.md`:

- **Geprüfter Gesamtbestand:** `419` Dateien
- **Scope:** `CMS/` ohne `CMS/vendor/` und `CMS/themes/`; aus `CMS/assets/` nur `assets/css/` und `assets/js/`
- **Auditfokus:** eigener Laufzeitcode, Konfiguration, Views, Member-/Admin-/Core-Pfade sowie Betriebsartefakte (`logs/`, `uploads/`)

Damit bleibt die Dateizählung an einer Stelle kanonisch und Audit-/ToDo-Dokumente ziehen ihre Scope-Angaben aus derselben verifizierten Quelle.

### Live-Site-Stichprobe

Auf `https://phinit.de/` wurden öffentlich erreichbare Kernpfade geprüft:

| Route | Status im Audit | Beobachtung |
|------|------------------|-------------|
| `/` | erreichbar | Homepage mit aktuellen Beiträgen, Sprachwechsel, Footer-/Legal-Links |
| `/en` | erreichbar | EN-Startseite vorhanden, Lokalisierung produktiv sichtbar |
| `/blog?page=1` | erreichbar | Blog-Archiv mit Pagination und Medienausgabe aktiv |
| `/feed` | erreichbar | RSS-Inhalte aktuell bis 23.03.2026 |
| `/forgot-password` | erreichbar | öffentlicher Recovery-Pfad vorhanden |
| `/login` | erreichbar | Login inkl. „Passwort vergessen“ und Register-Link |
| `/register` | erreichbar | Registrierungsformular mit Passwortregeln und Datenschutzerklärung |
| `/contact/kontakt` | erreichbar | Kontaktformular mit Consent-/Spam-Schutz sichtbar |
| `/impressum` | erreichbar | Legal-Seite vorhanden, Update-Hinweis 18.03.2026 |
| `/datenschutz` | erreichbar | Datenschutzerklärung vorhanden, Matomo-/Cookie-Aussagen sichtbar |
| `/2026/03/12/umstellung-von-wordpress-auf-365cms` | erreichbar | Live-Artikel bestätigt CMS-Migration und laufende Feinarbeiten |

**Einschränkung:** Admin-, Diagnose-, Redaktions- und Member-Backoffice-Pfade konnten auf Produktion mangels Zugangsdaten nicht live verifiziert werden. Diese Bewertung stützt sich dort auf Codezustand, Dokumentation und Git-Verlauf.

---

## Positive Befunde

### 1. Öffentliche Kernrouten der Live-Site sind konsistent erreichbar

Die PhinIT-Live-Site zeigt aktuell keinen Hinweis auf einen öffentlichen Routing-Bruch im Standardbetrieb:

- Home, Blog, Feed, Legal, Login, Register und Forgot-Password sind erreichbar.
- Die EN-Version ist produktiv sichtbar.
- Die Seite weist sich konsistent als **Powered by 365CMS.DE** aus.
- Das RSS-Feed bestätigt aktuelle Veröffentlichungen bis **23.03.2026**.

Damit ist die zentrale Frontend-/Auth-Oberfläche aus öffentlicher Sicht belastbar – keine kleine Leistung, und ja: das ist die freundlichere Form von „nichts steht in Flammen“. 

### 2. Öffentliche Publikationslogik wurde in den CMS-Pfaden systematisch vereinheitlicht

Die aktuelle Codebasis zeigt mit `cms_post_publication_where(...)` eine klare Konsolidierung der öffentlichen Sichtbarkeit von Beiträgen. Das ist architektonisch wichtig, weil es gleich mehrere Fehlerklassen zugleich reduziert:

- zukünftige Veröffentlichungen erscheinen nicht versehentlich zu früh,
- Router-, Suche-, Sitemap- und Theme-Abfragen ziehen dieselbe Sichtbarkeitslogik,
- mehrsprachige Archiv- und Listingpfade lassen sich darauf sauber aufbauen.

Gerade im Zusammenspiel mit den jüngsten Routing-/Archiv-Commits ist das ein deutlicher Reifegewinn.

### 3. Cache-/Header-Disziplin an Entry-Points bleibt ein stabiler Kern

Die bekannten Standalone-Einstiege `config.php`, `index.php`, `install.php`, `cron.php` und `orders.php` zeigen weiterhin den erwarteten Fokus auf private/no-store-Semantik für sensible oder zustandsnahe Pfade. Das passt zur früheren Auditlinie und wurde im aktuellen Stand nicht erkennbar zurückgebaut.

### 4. Upload-/Medienauslieferung bleibt balanciert

Die Kombination aus:

- `CMS/uploads/.htaccess` mit `nosniff`,
- standardmäßig konservativer Auslieferung,
- gezielter Inline-Ausnahme für sichere Bildtypen,
- sichtbarer Nutzung von `/media-file?...&disposition=inline` auf der Live-Site

spricht weiterhin für einen bewusst austarierten Delivery-Pfad zwischen Sicherheit und UX.

---

## Zentrale Befunde

### B-01 – Versions- und Release-Drift zwischen Code, Changelog und Git

**Priorität:** hoch  
**Kategorie:** Release-/Betriebsdisziplin

Im Code stehen weiterhin:

- `CMS/config/app.php` → `CMS_VERSION = '2.6.0'`
- `CMS/core/Version.php` → `Version::CURRENT = '2.6.0'`

Gleichzeitig dokumentiert `Changelog.md` bereits:

- `2.6.1` am `2026-03-17`

und die Git-Historie zeigt weitere funktionale Commits vom **20.03. bis 22.03.2026**.

Das ist kein unmittelbarer Laufzeit-Crash, aber ein klarer **Audit-Befund**, weil davon mehrere Folgeflächen abhängen:

- Systeminfo-/Diagnose-Anzeigen
- Update-/Marketplace-Kompatibilitätsanzeigen
- Support-/Backup-Metadaten
- API-/Dashboard-Versionsausgabe
- Release-Kommunikation gegenüber Betrieb und Dokumentation

**Bewertung:**

Der technische Produktstand ist weiter als die formalen Versionskonstanten. Damit entsteht ein unnötiger Graubereich zwischen „released“, „unreleased“ und „im Repo vorhanden“.

**Empfehlung:**

Kurzfristig eine klare Linie festziehen:

1. entweder auf einen echten neuen Release-Stand heben,
2. oder Changelog-Änderungen bis zur Versionierung ausdrücklich unter `Unreleased` führen.

---

### B-02 – Live-Site bestätigt die öffentliche Formular- und Auth-Strecke, aber nicht den Betriebsrand dahinter

**Priorität:** mittel  
**Kategorie:** Live-Betrieb / Verifikation

Die öffentlichen Formulare sind sichtbar vorhanden:

- Login
- Registrierung
- Passwort-Reset
- Kontaktformular

Positiv ist, dass diese Pfade produktiv konsistent angeboten werden. Nicht live verifiziert werden konnten jedoch:

- Cache-Header im echten Browser-/Proxy-Lauf,
- Anti-Spam-/Rate-Limit-Verhalten,
- tatsächliche Recovery-Mail-Zustellung,
- Admin-/Member-seitige Folgeflüsse,
- Matomo-/Consent-/Drittladeverhalten auf echter Netzwerkeebene.

Gerade die Datenschutzerklärung kommuniziert ein reduziertes Setup ohne optionale Cookie-Maske und verweist auf self-hosted Matomo. Diese Aussage wirkt plausibel, sollte aber als **echter Browser-/Netzwerktest** gegen die Live-Instanz regelmäßig gegengeprüft werden – nicht nur textlich, sondern per Request-/Cookie-/Script-Liste.

**Bewertung:**

Kein akuter Produktivfehler, aber ein offener Verifikationsrand zwischen Code-/Dokustand und echtem Produktionsnetzwerk.

---

### B-03 – Hotspots verschieben sich auf große Wartungsblöcke statt auf akute Sicherheitsdefekte

**Priorität:** mittel  
**Kategorie:** Architektur / Wartbarkeit

Die klassischen kritischen Audit-Blöcke sind spürbar kleiner geworden. Dafür verschiebt sich der Druck auf große, schwerer wartbare Datei- und Servicebereiche.

Besonders relevant bleiben aktuell:

- `CMS/includes/functions.php`
- `CMS/install.php`
- `CMS/core/Services/UpdateService.php`
- stark gewachsene Routing-/Admin-Pfade
- der neue CMS-Importer unter `CMS/plugins/cms-importer/...`
- der dokumentationsnahe ZIP-/Sync-Pfad unter `CMS/admin/modules/system/...`

Der Commit vom **21.03.2026** zum Importer bringt funktional viel, erzeugt aber zugleich einen neuen strukturellen Hotspot mit sehr großen Klassenblöcken. Das ist nicht falsch – aber klar auditrelevant.

**Bewertung:**

Der CMS-Kern ist heute weniger „unsicher“ als früher, aber nicht automatisch „billig zu pflegen“. Die nächste sinnvolle Welle ist deshalb keine Panik-Härtung, sondern gezieltes **Monolithen- und Wartbarkeitsmanagement**.

---

### B-05 – Betriebsartefakte im Laufzeitbaum sind klein, aber auditrelevant

**Priorität:** mittel  
**Kategorie:** Betrieb / Informationshygiene

Die nachgezogene Vollprüfung hat sichtbar gemacht, dass die **Dokumentation des Inventar-Scopes** und die tatsächliche Dateistruktur zuletzt auseinanderliefen. Zusätzlich bleibt `CMS/logs/` zwar aktuell sauber, der konfigurierte Debug-Logpfad zeigt aber weiterhin direkt in den Release-Baum (`ABSPATH . 'logs/error.log'`).

Konkret auffällig:

- frühere Audit-/ToDo-Stände arbeiteten noch mit veralteten Bestandszahlen (`~290` bzw. `444` Dateien)
- die frühere Audit-Kopie des Fileinventars enthielt Dateien und Bereiche außerhalb des finalen Prüfscope
- bei aktiviertem Debug-Modus würde Logging weiterhin direkt unter `CMS/logs/` landen
- unter `CMS/uploads/` liegt weiterhin mindestens eine echte Beispiel-/Testdatei

Das ist kein unmittelbarer Produktionsdefekt, aber ein klarer Hinweis darauf, dass Audit-Dokumentation, Release-Hygiene und Betriebsartefakt-Trennung noch nicht sauber genug synchronisiert waren.

**Bewertung:**

Eher Disziplin- als Sicherheitskatastrophe – aber genau solche Ränder machen Releases, Backups und Audits unnötig unordentlich.

**Empfehlung:**

- Logs nur außerhalb des Release-Artefakts schreiben
- Upload-Beispiele klar von produktiven Dateien trennen
- Deploy-/Build-Cleanup verbindlich vorsehen
- `FILEINVENTAR.md`, Audit und ToDo künftig aus derselben verifizierten Scope-Quelle ableiten

---

### B-04 – Routing-/Archivlinie ist funktional stärker, braucht aber disziplinierte Release-Nachführung

**Priorität:** mittel  
**Kategorie:** Routing / SEO / Content

Die Git-Historie seit `2.6.1` zeigt eine klare Ausbauwelle für:

- mehrsprachige Kategorie-/Tag-Archive,
- Ersatzkategorien und Ersatztags beim Löschen,
- verbesserte Routing-Basen,
- Slug-/Lokalisierungslogik,
- IndexNow-Keydatei-Auslieferung.

Das ist inhaltlich stark und passt gut zur Live-Site, die bereits mehrsprachige und archivartige Content-Strukturen sichtbar nutzt.

Der Nachteil: Diese Breite an funktionalen Änderungen erhöht den Druck, Changelog, Versionsstand, Tests und Betriebschecklisten strikt nachzuziehen. Genau dort liegt derzeit der eigentliche organisatorische Restfehler.

---

## Live-Site-Befunde mit Audit-Relevanz

### Migration und Produktivbild

Der Artikel zur Umstellung von WordPress auf 365CMS bestätigt öffentlich:

- die produktive Migration auf 365CMS,
- einen aktuell aktiven Weiterentwicklungsstand,
- verbleibende Feinarbeiten bei Verlinkungen, Ansichten und Tabellen.

Das ist für den Audit wichtig, weil damit die Live-Site nicht als „statischer Showcase“, sondern als aktiv migrierte, weiter polierte Produktionsinstanz einzustufen ist.

### Legal-/Trust-Fläche

`/impressum` und `/datenschutz` sind erreichbar und gepflegt. Die Datenschutzerklärung benennt:

- Hostinger als Hosting,
- self-hosted Matomo,
- technisch notwendige Cookies,
- Login-/Register-/Kommentar-Verarbeitung.

Das ist aus Reife-Sicht positiv. Offen bleibt nur die wiederkehrende technische Gegenprüfung gegen das echte Request-Verhalten.

### Medien- und Content-Ausgabe

Im Blog-Listing wurden Bilder sowohl über direkte Upload-Pfade als auch über `media-file`-Auslieferung beobachtet. Das deckt sich mit der Delivery-Strategie des CMS und ist kein Fehlbild.

---

## Risikobild

| Priorität | Thema | Risiko |
|----------|-------|--------|
| Hoch | Versions-/Release-Drift | falscher Produktstand in Diagnose, Support, API und Update-Kontext |
| Hoch | öffentlicher Installerpfad | unnötig großer Angriffsvektor, solange `install.php` produktiv erreichbar bleibt |
| Hoch | Importer-Remote-Fetch ohne zentrale Härtung | TLS-/SSRF-/Host-Disziplin weicht vom gehärteten Core-HTTP-Pfad ab |
| Mittel | fehlende Proxy-/Netzwerk-Realfallprüfung | Doku-/Code-Aussage und echtes Produktionsverhalten können auseinanderlaufen |
| Mittel | große Wartungsblöcke im Core und in eingebauten Plugins | höhere Regressionswahrscheinlichkeit bei Folgeänderungen |
| Mittel | Login nur sessionbasiert ohne separates Device-Cookie | bestehende Logins sind nicht zusätzlich an einen kurzlebigen Browser-/Geräte-Nachweis gebunden |
| Mittel | Inventar-/Scope-Drift und Debug-Logziel im Release-Baum | unnötige Informations-, Audit- und Release-Unschärfe |
| Niedrig bis mittel | Live-Site-Backend ohne produktive Stichprobe | Admin-/Member-Ränder bleiben auf Code-/Testebene statt Realbetrieb verifiziert |

---

## Empfohlene Nacharbeit

### Kurzfristig

1. **Versionsstand synchronisieren**
   - `CMS_VERSION`, `Version::CURRENT`, Update-Metadaten und Changelog sauber auf eine Linie bringen.

2. **Installer produktiv hart absichern oder deaktivieren**
   - `install.php` nach dem Initial-Setup nicht frei im öffentlichen Laufzeitpfad belassen.

3. **Importer-Fetch auf den zentralen HTTP-Client umziehen**
   - TLS-, Host- und SSRF-Härtung an derselben Stelle erzwingen wie bei anderen Remote-Pfaden.

4. **Proxy-/CDN-/Header-Realfall prüfen**
   - insbesondere öffentliche HTML-Routen, Auth-Routen, Feed und Medienpfade.

5. **Datenschutz-/Tracking-Aussagen technisch gegenmessen**
   - Cookies, Requests, Matomo, externe Ressourcen im realen Browserlauf.

6. **Inventar-Dokumentation konsolidieren**
   - `FILEINVENTAR.md`, Audit und ToDo aus demselben verifizierten Scope speisen.

### Danach

7. **größte Wartungsblöcke priorisieren**
   - `includes/functions.php`
   - `install.php`
   - `UpdateService`
   - große Import-/Routing-Pfade

8. **Log- und Upload-Artefakte aus dem Release-Baum herausziehen**
   - nur Platzhalter, Schutzdateien und definierte Beispielartefakte versionieren.

9. **Release-Nachführung standardisieren**
   - Features erst dann als Releasepunkt dokumentieren, wenn Version, Doku und Runtime-Metadaten gemeinsam nachgezogen wurden.

---

## Abschlussbewertung

Der neue Audit bestätigt kein akutes Produktionsproblem im CMS-Kern. Die Live-Site wirkt öffentlich konsistent, modernisiert und aktiv gepflegt. Die stärksten Risiken liegen aktuell vor allem in **Installer-/Importer-Rändern**, in der **Disziplin zwischen Feature-Tempo, Versionierung, Dokumentation und Betriebsvalidierung** sowie in kleinen, aber unnötigen **Betriebsartefakten im Laufzeitbaum**.

Für den aktuellen Stand gilt daher:

- **öffentlich produktionsfähig:** ja
- **architektonisch kontrolliert:** überwiegend ja
- **formal release-sauber:** derzeit nur eingeschränkt
- **nächster sinnvoller Schwerpunkt:** Versionsabgleich + Realbetriebstests + weiterer Abbau großer Wartungsblöcke

Unterm Strich: technisch stabil, organisatorisch an einer Stelle unscharf – also eher Feinschliff als Feuerwehreinsatz.

## Nachtrag – Umsetzungsstand 24.03.2026

Der Pflicht-Backlog dieses Auditlaufs (`A-01` bis `A-19`) wurde am `24.03.2026` vollständig umgesetzt und jeweils einzeln mit Changelog-Eintrag sowie Git-Commit nachgezogen.

Damit gilt für diesen Auditstand jetzt zusätzlich:

- Die ursprünglichen Maßnahmen aus `DOC/audit/ToDo_Audit_23032026.md` sind vollständig abgearbeitet.
- Der Audit- und ToDo-Stand ist nicht mehr „offen“, sondern dokumentiert einen abgeschlossenen Nacharbeitslauf.
- Die Befund- und Bewertungsabschnitte oberhalb bleiben bewusst als historischer Prüfstand vom `23.03.2026` erhalten.