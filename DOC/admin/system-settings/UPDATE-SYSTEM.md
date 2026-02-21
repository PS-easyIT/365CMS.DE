# 365CMS Update-System – Konzept & Implementierung

> **Repo:** [github.com/PS-easyIT/365CMS.DE](https://github.com/PS-easyIT/365CMS.DE)  
> **Branch:** `main`  
> **Gültig ab:** CMS v2.0  
> **Stand:** 21. Februar 2026

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Repository-Struktur](#2-repository-struktur)
3. [Versions-Manifest (`update.json`)](#3-versions-manifest-updatejson)
4. [Update-Check-Mechanismus (PHP)](#4-update-check-mechanismus-php)
5. [Update-Download & Installation](#5-update-download--installation)
6. [Admin-Seite `updates.php`](#6-admin-seite-updatesphp)
7. [GitHub Actions – automatische Release-Pipeline](#7-github-actions--automatische-release-pipeline)
8. [Sicherheit & Best Practices](#8-sicherheit--best-practices)
9. [Schritt-für-Schritt: Neues Update veröffentlichen](#9-schritt-für-schritt-neues-update-veröffentlichen)
10. [Roadmap / Offene Punkte](#10-roadmap--offene-punkte)

---

## 1. Übersicht

Das Update-System erkennt und installiert **drei Typen** von Updates:

| Typ | Quelle | Pfad im Repo | Premium? |
|-----|--------|-------------|---------|
| **CMS Core** | GitHub Releases | `/CMS/` | ✗ immer free |
| **Free Plugins** | GitHub Releases | `/PLUGINS/` (Unterordner je Plugin) | ✗ nur free |
| **Free Themes** | GitHub Releases | `/THEMES/` (Unterordner je Theme) | ✗ nur free |

**Premium-Plugins und Premium-Themes** werden bewusst **nicht** vom GitHub-Repo aktualisiert – diese laufen über den separaten Marketplace-Lizenz-Kanal.

### Funktionsprinzip

```
CMS Admin → "Updates" anklicken
    ↓
PHP ruft update.json vom GitHub-Repo ab
    ↓
Vergleich: github_version vs. installed_version (Semver)
    ↓
Update verfügbar → Admin sieht Download-Button
    ↓
Admin klickt "Installieren" → ZIP wird heruntergeladen, entpackt
    ↓
Backup der alten Version → Neue Dateien eingespielen
    ↓
Versionsstand in DB / config aktualisieren
```

---

## 2. Repository-Struktur

### Aktuell (noch hochzuladen)

```
PS-easyIT/365CMS.DE (GitHub)
├── CMS/                            ← CMS Core
│   ├── update.json                 ← Versions-Manifest für den Core
│   └── releases/
│       └── 365cms-v2.1.0.zip
│
├── PLUGINS/                        ← Free Plugins
│   ├── index.json                  ← Liste aller verfügbaren Plugins
│   ├── cms-experts/
│   │   ├── update.json
│   │   └── releases/
│   │       └── cms-experts-v1.2.0.zip
│   ├── cms-companies/
│   │   └── update.json
│   ├── cms-events/
│   │   └── update.json
│   ├── cms-jobads/
│   │   └── update.json
│   └── cms-speakers/
│       └── update.json
│
├── THEMES/                         ← Free Themes
│   ├── index.json                  ← Liste aller verfügbaren Themes
│   ├── technexus/
│   │   ├── update.json
│   │   └── releases/
│   │       └── technexus-v3.1.0.zip
│   ├── academy365/
│   │   └── update.json
│   └── ...
│
└── DOC/                            ← Dokumentation (von Support-Seite geladen)
    ├── README.md
    ├── UPDATE-SYSTEM.md            ← Diese Datei
    ├── MARKETPLACE-KONZEPT.md
    └── ...
```

### Raw-URLs für GitHub-Zugriff

```
# Versions-Manifest CMS Core
https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/CMS/update.json

# Plugin-Index
https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/PLUGINS/index.json

# Plugin-Manifest (je Plugin)
https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/PLUGINS/{slug}/update.json

# Theme-Index
https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/THEMES/index.json

# Theme-Manifest (je Theme)
https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/THEMES/{slug}/update.json
```

---

## 3. Versions-Manifest (`update.json`)

### CMS Core – `/CMS/update.json`

```json
{
  "slug": "365cms-core",
  "name": "365CMS",
  "type": "core",
  "version": "2.1.0",
  "min_php": "8.1",
  "released": "2026-02-21",
  "download_url": "https://github.com/PS-easyIT/365CMS.DE/releases/download/v2.1.0/365cms-v2.1.0.zip",
  "changelog_url": "https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/DOC/CHANGELOG.md",
  "checksum_sha256": "abc123...",
  "notes": "Sicherheitsupdate: XSS-Fix in Template-Engine",
  "critical": true
}
```

### Plugin-Index – `/PLUGINS/index.json`

```json
{
  "version": "1",
  "updated": "2026-02-21",
  "plugins": [
    { "slug": "cms-experts",   "name": "CMS Experts",   "manifest": "PLUGINS/cms-experts/update.json" },
    { "slug": "cms-companies", "name": "CMS Companies", "manifest": "PLUGINS/cms-companies/update.json" },
    { "slug": "cms-events",    "name": "CMS Events",    "manifest": "PLUGINS/cms-events/update.json" },
    { "slug": "cms-jobads",    "name": "CMS Job Ads",   "manifest": "PLUGINS/cms-jobads/update.json" },
    { "slug": "cms-speakers",  "name": "CMS Speakers",  "manifest": "PLUGINS/cms-speakers/update.json" }
  ]
}
```

### Plugin-Manifest – `/PLUGINS/{slug}/update.json`

```json
{
  "slug": "cms-experts",
  "name": "CMS Experts",
  "type": "plugin",
  "license": "free",
  "version": "1.2.0",
  "min_cms_version": "2.0.0",
  "min_php": "8.1",
  "released": "2026-02-15",
  "download_url": "https://github.com/PS-easyIT/365CMS.DE/releases/download/plugin-cms-experts-v1.2.0/cms-experts-v1.2.0.zip",
  "checksum_sha256": "def456...",
  "changelog": {
    "1.2.0": "Neue Filter-Optionen, Performance-Verbesserungen",
    "1.1.0": "Bugfix: Suchindex-Aktualisierung",
    "1.0.0": "Erstveröffentlichung"
  }
}
```

### Theme-Manifest – `/THEMES/{slug}/update.json`

```json
{
  "slug": "technexus",
  "name": "TechNexus",
  "type": "theme",
  "license": "free",
  "version": "3.1.0",
  "min_cms_version": "2.0.0",
  "released": "2026-02-01",
  "download_url": "https://github.com/PS-easyIT/365CMS.DE/releases/download/theme-technexus-v3.1.0/technexus-v3.1.0.zip",
  "checksum_sha256": "ghi789...",
  "changelog": {
    "3.1.0": "Neues Dark-Mode-Feature",
    "3.0.2": "Bugfix: Mobile Navigation"
  }
}
```

---

## 4. Update-Check-Mechanismus (PHP)

### Klasse: `UpdateService` – `core/Services/UpdateService.php`

```php
<?php
declare(strict_types=1);

namespace CMS\Services;

class UpdateService
{
    private static ?self $instance = null;

    // GitHub Raw-URL Basis
    private const RAW = 'https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/';

    // Cache-Dauer in Sekunden (1 Stunde)
    private const CACHE_TTL = 3600;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    // ─── Öffentliche API ──────────────────────────────────────────────────

    /**
     * Prüft, ob ein CMS-Core-Update verfügbar ist.
     */
    public function checkCoreUpdates(): array
    {
        $remote = $this->fetchManifest('CMS/update.json');
        if (!$remote) {
            return ['available' => false, 'error' => 'GitHub nicht erreichbar'];
        }

        $current = CMS_VERSION; // Konstante aus config.php
        $remote_version = $remote['version'] ?? '0.0.0';

        return [
            'available'    => version_compare($remote_version, $current, '>'),
            'installed'    => $current,
            'latest'       => $remote_version,
            'download_url' => $remote['download_url'] ?? '',
            'notes'        => $remote['notes'] ?? '',
            'critical'     => $remote['critical'] ?? false,
            'released'     => $remote['released'] ?? '',
            'checksum'     => $remote['checksum_sha256'] ?? '',
        ];
    }

    /**
     * Prüft alle Free Plugins auf Updates.
     * Vergleicht nur Plugins, die lokal installiert sind.
     */
    public function checkPluginUpdates(): array
    {
        $index = $this->fetchManifest('PLUGINS/index.json');
        if (!$index || empty($index['plugins'])) {
            return [];
        }

        $installedPlugins = $this->getInstalledPlugins(); // aus PluginManager
        $updates = [];

        foreach ($index['plugins'] as $entry) {
            $slug = $entry['slug'] ?? '';
            if (!isset($installedPlugins[$slug])) continue; // nur installierte prüfen

            $manifest = $this->fetchManifest('PLUGINS/' . $slug . '/update.json');
            if (!$manifest) continue;

            $installed = $installedPlugins[$slug]['version'] ?? '0.0.0';
            $latest    = $manifest['version'] ?? '0.0.0';

            if (version_compare($latest, $installed, '>')) {
                $updates[$slug] = [
                    'name'         => $manifest['name'] ?? $slug,
                    'installed'    => $installed,
                    'latest'       => $latest,
                    'download_url' => $manifest['download_url'] ?? '',
                    'checksum'     => $manifest['checksum_sha256'] ?? '',
                    'released'     => $manifest['released'] ?? '',
                    'changelog'    => $manifest['changelog'] ?? [],
                ];
            }
        }

        return $updates;
    }

    /**
     * Prüft alle Free Themes auf Updates.
     */
    public function checkThemeUpdates(): array
    {
        $index = $this->fetchManifest('THEMES/index.json');
        if (!$index || empty($index['themes'])) {
            return [];
        }

        $installedThemes = $this->getInstalledThemes(); // aus ThemeManager
        $updates = [];

        foreach ($index['themes'] as $entry) {
            $slug = $entry['slug'] ?? '';
            if (!isset($installedThemes[$slug])) continue;

            $manifest = $this->fetchManifest('THEMES/' . $slug . '/update.json');
            if (!$manifest) continue;

            $installed = $installedThemes[$slug]['version'] ?? '0.0.0';
            $latest    = $manifest['version'] ?? '0.0.0';

            if (version_compare($latest, $installed, '>')) {
                $updates[$slug] = [
                    'name'         => $manifest['name'] ?? $slug,
                    'installed'    => $installed,
                    'latest'       => $latest,
                    'download_url' => $manifest['download_url'] ?? '',
                    'checksum'     => $manifest['checksum_sha256'] ?? '',
                    'released'     => $manifest['released'] ?? '',
                    'changelog'    => $manifest['changelog'] ?? [],
                ];
            }
        }

        return $updates;
    }

    // ─── Interne Methoden ─────────────────────────────────────────────────

    /**
     * Holt ein JSON-Manifest von GitHub (mit File-Cache).
     */
    private function fetchManifest(string $path): ?array
    {
        $cacheKey  = preg_replace('/[^a-z0-9]/i', '_', $path);
        $cacheFile = sys_get_temp_dir() . '/365cms_update_' . $cacheKey . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            return json_decode((string) file_get_contents($cacheFile), true) ?: null;
        }

        $url = self::RAW . $path;
        $ctx = stream_context_create([
            'http' => [
                'header'        => "User-Agent: 365CMS-UpdateChecker/2.0\r\n",
                'timeout'       => 8,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;

        $data = json_decode($body, true);
        if (!is_array($data)) return null;

        file_put_contents($cacheFile, $body);
        return $data;
    }

    /**
     * Cache für alle Update-Prüfungen leeren.
     */
    public function clearCache(): void
    {
        $pattern = sys_get_temp_dir() . '/365cms_update_*.json';
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }

    private function getInstalledPlugins(): array
    {
        // Aus PluginManager: [slug => ['version' => '1.0.0', 'active' => true]]
        return \CMS\PluginManager::instance()->getInstalledPluginVersions();
    }

    private function getInstalledThemes(): array
    {
        // Aus ThemeManager: [slug => ['version' => '1.0.0']]
        return \CMS\ThemeManager::instance()->getInstalledThemeVersions();
    }
}
```

---

## 5. Update-Download & Installation

### Implementierung: `installUpdate()`

```php
/**
 * Lädt ein Update-ZIP von GitHub herunter und installiert es.
 *
 * @param string $type    'core' | 'plugin' | 'theme'
 * @param string $slug    Plugin/Theme-Slug (leer für Core)
 * @param string $url     Download-URL aus update.json
 * @param string $sha256  Erwartete SHA256-Prüfsumme
 */
public function installUpdate(string $type, string $slug, string $url, string $sha256): bool|string
{
    // 1. Temporäre Datei
    $tmpFile = tempnam(sys_get_temp_dir(), '365cms_update_');

    // 2. Download
    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: 365CMS-Updater/2.0\r\n",
        'timeout' => 60,
    ]]);
    $data = file_get_contents($url, false, $ctx);
    if ($data === false) return 'Download fehlgeschlagen';
    file_put_contents($tmpFile, $data);

    // 3. Checksumme prüfen (SHA256)
    if ($sha256 !== '' && hash_file('sha256', $tmpFile) !== $sha256) {
        @unlink($tmpFile);
        return 'Prüfsumme ungültig – Update abgebrochen';
    }

    // 4. Backup der aktuellen Version anlegen
    $backupPath = $this->backupCurrentVersion($type, $slug);

    // 5. ZIP entpacken
    $targetPath = $this->getTargetPath($type, $slug);
    $zip = new \ZipArchive();
    if ($zip->open($tmpFile) !== true) {
        @unlink($tmpFile);
        return 'ZIP konnte nicht geöffnet werden';
    }

    // Zieldirectory leeren (nur eigene Dateien, nicht Nutzerdaten)
    $this->clearDirectory($targetPath, preserve: ['uploads', 'config', 'cache']);

    $zip->extractTo($targetPath);
    $zip->close();
    @unlink($tmpFile);

    // 6. Versionsstand in DB aktualisieren
    $this->saveInstalledVersion($type, $slug, $this->getLatestVersion($type, $slug));

    // 7. Cache leeren
    $this->clearCache();

    return true;
}

private function getTargetPath(string $type, string $slug): string
{
    return match ($type) {
        'core'   => BASE_PATH,
        'plugin' => PLUGINS_PATH . $slug . '/',
        'theme'  => THEMES_PATH  . $slug . '/',
        default  => throw new \InvalidArgumentException("Unbekannter Typ: {$type}"),
    };
}
```

### SHA256-Checksumme generieren (lokal vor Release)

```bash
# Linux/macOS
sha256sum releases/365cms-v2.1.0.zip

# Windows (PowerShell)
Get-FileHash releases\365cms-v2.1.0.zip -Algorithm SHA256
```

---

## 6. Admin-Seite `updates.php`

Die vorhandene Datei `CMSv2/admin/updates.php` nutzt den `UpdateService`.  
Sie ist unter `System & Einstellungen → Updates` erreichbar.

### Wichtige Verhaltensregeln

1. **Kein Auto-Update** – Jedes Update benötigt manuelle Admin-Bestätigung
2. **Backup vor Installation** – Automatisch, liegt in `CMSv2/storage/backups/`
3. **Rollback möglich** – Letztes Backup kann wiederhergestellt werden
4. **Nur Free-Produkte** – Premium-Plugins/-Themes erscheinen NICHT in dieser Liste
5. **Cache-Ablauf** – Update-Prüfungen werden 1 Stunde gecacht

### Update-Check manuell auslösen (Cache leeren)

```
/admin/updates?refresh=1
```

---

## 7. GitHub Actions – automatische Release-Pipeline

Jedes Mal, wenn ein neues Git-Tag `v*` gepusht wird, erzeugt GitHub Actions automatisch das Release-ZIP und aktualisiert das `update.json`.

### `.github/workflows/release-core.yml`

```yaml
name: Release CMS Core

on:
  push:
    tags:
      - 'v[0-9]+.[0-9]+.[0-9]+'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Versionsnummer extrahieren
        id: vars
        run: echo "VERSION=${GITHUB_REF_NAME#v}" >> $GITHUB_OUTPUT

      - name: CMS-ZIP erstellen
        run: |
          mkdir -p dist
          zip -r dist/365cms-v${{ steps.vars.outputs.VERSION }}.zip CMSv2/ \
            -x "*.git*" \
            -x ".github/*" \
            -x "CMSv2/cache/*" \
            -x "CMSv2/logs/*" \
            -x "CMSv2/uploads/*"

      - name: SHA256 berechnen
        id: sha
        run: echo "SHA=$(sha256sum dist/365cms-v${{ steps.vars.outputs.VERSION }}.zip | cut -d' ' -f1)" >> $GITHUB_OUTPUT

      - name: update.json aktualisieren
        run: |
          cat > CMS/update.json << EOF
          {
            "slug": "365cms-core",
            "name": "365CMS",
            "type": "core",
            "version": "${{ steps.vars.outputs.VERSION }}",
            "released": "$(date -u +%Y-%m-%d)",
            "download_url": "https://github.com/PS-easyIT/365CMS.DE/releases/download/v${{ steps.vars.outputs.VERSION }}/365cms-v${{ steps.vars.outputs.VERSION }}.zip",
            "checksum_sha256": "${{ steps.sha.outputs.SHA }}",
            "notes": "",
            "critical": false
          }
          EOF

      - name: update.json committen
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          git add CMS/update.json
          git commit -m "chore: update CMS/update.json to v${{ steps.vars.outputs.VERSION }}" || true
          git push

      - name: GitHub Release erstellen
        uses: softprops/action-gh-release@v1
        with:
          files: dist/365cms-v${{ steps.vars.outputs.VERSION }}.zip
          name: "365CMS v${{ steps.vars.outputs.VERSION }}"
          generate_release_notes: true
```

### Plugin-Release-Tag-Schema

```
# Core
git tag v2.1.0 && git push origin v2.1.0

# Plugin
git tag plugin-cms-experts-v1.2.0 && git push origin plugin-cms-experts-v1.2.0

# Theme
git tag theme-technexus-v3.1.0 && git push origin theme-technexus-v3.1.0
```

---

## 8. Sicherheit & Best Practices

| Maßnahme | Warum |
|---------|-------|
| **SHA256-Checksumme** je ZIP | Manipulierte Dateien erkennen |
| **HTTPS only** für alle Downloads | Man-in-the-Middle verhindern |
| **Backup vor Überschreiben** | Rollback bei kaputtem Update |
| **Keine automatischen Updates** | Admin-Kontrolle bleibt erhalten |
| **Nur eigenes Repo** als Quelle | Keine Drittanbieter-Zugriffe |
| **CSRF-Token bei Installation** | Verhindert Cross-Site-Installationen |
| **PHP-Syntax-Check nach Entpacken** | Verhindert kaputte PHP-Dateien |
| **Nur mit Admin-Session** | Keine öffentlich zugänglichen Update-Endpunkte |

### PHP-Syntax-Check nach Installation

```php
// Alle PHP-Dateien im neuen Plugin prüfen
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetPath)
);
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    exec('php -l ' . escapeshellarg($file->getPathname()), $out, $code);
    if ($code !== 0) {
        // Rollback!
        $this->rollback($type, $slug, $backupPath);
        return 'PHP-Syntaxfehler in ' . $file->getFilename() . ' – Rollback durchgeführt';
    }
}
```

---

## 9. Schritt-für-Schritt: Neues Update veröffentlichen

### CMS Core-Update

```bash
# 1. Version in config.php anpassen
define('CMS_VERSION', '2.1.0');

# 2. CHANGELOG.md aktualisieren
nano DOC/CHANGELOG.md

# 3. Committen
git add .
git commit -m "release: CMS v2.1.0"

# 4. Tag pushern → GitHub Actions läuft automatisch
git tag v2.1.0
git push origin main --tags
```

### Plugin-Update

```bash
# 1. Version in Plugin-plugin.json anpassen
# 2. Changelog in PLUGINS/cms-experts/update.json eintragen
# 3. Committen & Tag setzen
git tag plugin-cms-experts-v1.2.0
git push origin main --tags
```

### Manuelle update.json-Pflege (ohne Actions)

Wenn GitHub Actions noch nicht eingerichtet ist:

1. Release-ZIP manuell in GitHub Releases hochladen
2. `update.json` Datei bearbeiten:
   - `version` auf neue Version setzen
   - `download_url` auf GitHub-Release-Download-Link setzen
   - `checksum_sha256` aus lokalem `sha256sum` eintragen
   - `released` auf heutiges Datum setzen
3. Committen & pushen

---

## 10. Roadmap / Offene Punkte

- [ ] `UpdateService::getInstalledPluginVersions()` im `PluginManager` implementieren
- [ ] `UpdateService::getInstalledThemeVersions()` im `ThemeManager` implementieren
- [ ] `/PLUGINS/index.json` + `/THEMES/index.json` im Repo anlegen
- [ ] Jeweils `update.json` für alle vorhandenen Free-Plugins anlegen
- [ ] GitHub Actions Workflow für Core-Releases einrichten
- [ ] Backup-Mechanismus in `UpdateService` fertigstellen
- [ ] Rollback-Funktion in `admin/updates.php` einbauen
- [ ] SHA256-Checksumme-Prüfung aktivieren (erst wenn ZIPs vorhanden)
- [ ] Admin-Benachrichtigung bei kritischen Updates (E-Mail)

---

## Verwandte Dateien

| Datei | Beschreibung |
|-------|-------------|
| [CMSv2/admin/updates.php](../CMSv2/admin/updates.php) | Admin-UI für Updates |
| [CMSv2/admin/support.php](../CMSv2/admin/support.php) | Docs-Viewer (lädt /DOC aus GitHub) |
| [DOC/MARKETPLACE-KONZEPT.md](MARKETPLACE-KONZEPT.md) | Marketplace-Konzept (Premium) |
| [DOC/CHANGELOG.md](CHANGELOG.md) | CMS-Changelog |

---

*Erstellt: 21.02.2026 – PS-easyIT / GitHub Copilot*
