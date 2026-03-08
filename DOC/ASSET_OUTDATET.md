# Asset-Cleanup / Outdated-Tracking

> Stand: 2026-03-08 | Status: nach Stage-A sowie freigegebener Löschung von `schema-org` und `tabler/libs`

## Bereits bereinigt / erledigt

- `CMS/assets/autoload.php` enthält **keine** veralteten Loader mehr für nicht vorhandene Pfade `image/` und `rate-limiter/`.
- `CMS/assets/Monolog/`  im aktuellen Runtime-Baum bereits **nicht mehr vorhanden**.
- `CMS/assets/rate-limiter/`  im aktuellen Runtime-Baum bereits **nicht mehr vorhanden**.
- `CMS/assets/translation/Test/`  im aktuellen Runtime-Baum **nicht vorhanden**.
- Die Detaildoku wurde von `symfony-mailer/` auf `mailer/` umgestellt.
- `CMS/assets/schema-org/` wurde nach Freigabe vollständig aus dem Runtime-Baum entfernt.
- `CMS/assets/tabler/libs/` wurde nach Freigabe vollständig aus dem Runtime-Baum entfernt.
- Die FilePond-Locales `de-de.js` und `en-en.js` wurden **bewusst behalten**.

## Offene Cleanup-Kandidaten

### 1. `msgraph/`

- Pfad: `CMS/assets/msgraph/`
- Status: keine produktive Runtime-Nutzung nachgewiesen
- Nachweis: `CMS/assets/msgraph/README.md` dokumentiert den aktuellen cURL-basierten `GraphApiService`; SDK bleibt nur Referenz/Notiz
- Empfehlung: vor Löschung fachlich prüfen, ob die lokale Referenzablage bewusst behalten werden soll

### 2. FilePond-Locales

Aktuell vorhanden unter `CMS/assets/filepond/locale/`:

- `de-de.js`
- `en-en.js`

Direkte Runtime-Referenzen wurden im Scan nicht gefunden.

Status:
- **bewusst beibehalten** auf ausdrückliche Freigabevorgabe

Hinweis:
- Die im Prompt genannten Namen `de_DE.js` / `en_en.js` entsprechen **nicht** der aktuellen Dateibenennung im Baum.
- Vor einer Löschung sollte geprüft werden, ob die Dateien bewusst als manuelle Reserve für spätere Mehrsprachigkeit liegen bleiben sollen.

### 3. `melbahja-seo/`

- Erwartung aus älteren Bestandslisten: `CMS/assets/melbahja-seo/`
- Ist-Zustand: Pfad fehlt im aktuellen Runtime-Baum vollständig
- Bewertung: kein Löschkandidat mehr, sondern Dokumentations-/Migrationshinweis
- Empfehlung: offen dokumentieren, falls künftig ein echter Ersatz für manuelle SEO-/Schema-Bausteine vorgesehen ist

## Referenzberichte

- `TEST/asset-scan-report.txt`
- `TEST/tabler-libs-report.txt`
- `TEST/filepond-locales-report.txt`
