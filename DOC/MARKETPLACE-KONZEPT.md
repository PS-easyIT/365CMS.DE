# 365CMS Marketplace – Konzept & Umsetzung

> **Status:** Planung / Konzeptphase  
> **Letzte Aktualisierung:** 21. Februar 2026  
> **Zuständig:** PS-easyIT / Andreas Hepp

---

## Inhaltsverzeichnis

1. [Übersicht & Ziele](#1-übersicht--ziele)
2. [Produkt-Typen & Preismodell](#2-produkt-typen--preismodell)
3. [Option A: GitHub-basierter Marketplace](#3-option-a-github-basierter-marketplace)
4. [Option B: Eigener Webspace / Self-Hosted](#4-option-b-eigener-webspace--self-hosted)
5. [Metadaten-Format (marketplace.json)](#5-metadaten-format-marketplacejson)
6. [Update-Mechanismus im CMS](#6-update-mechanismus-im-cms)
7. [Lizenz-System für Premium-Produkte](#7-lizenz-system-für-premium-produkte)
8. [Empfehlung & Roadmap](#8-empfehlung--roadmap)

---

## 1. Übersicht & Ziele

Der 365CMS Marketplace ist ein integrierter Vertriebskanal für:

- **Plugins** – Erweiterungen für CMS-Funktionen
- **Themes** – Design-Templates für die Frontend-Ausgabe

### Ziele

| Ziel | Beschreibung |
|------|-------------|
| **Entdeckbarkeit** | Nutzer sollen Erweiterungen direkt im Admin finden |
| **1-Klick-Installation** | Kein manueller Upload, direkt aus dem Marketplace |
| **Auto-Updates** | Automatische Aktualisierungen für installierte Produkte |
| **Monetarisierung** | Free- und Premium-Produkte mit Lizenzschutz |

---

## 2. Produkt-Typen & Preismodell

### Lizenzmodelle

| Tier | Preis | Lizenz-Typ | Update-Zeitraum |
|------|-------|-----------|----------------|
| **Free** | 0,00 € | Open Source (MIT/GPL) | unbegrenzt |
| **Starter** | 49,95 € | Einmalige Lizenz, 1 Domain | 12 Monate |
| **Professional** | 149 – 499 € | Einmalige Lizenz, 3 Domains | 12 Monate |
| **Business** | 499 – 999 € | Einmalige Lizenz, 10 Domains | 24 Monate |
| **Enterprise** | 999 – 1.499 € | Unbegrenzte Domains | Lifetime Updates |

### Nach Ablauf des Update-Zeitraums

Das Produkt läuft weiter, aber:
- Keine automatischen Sicherheitsupdates
- Optional: **Update-Schutz verlängern** (20–30 % des Originalpreises / Jahr)

---

## 3. Option A: GitHub-basierter Marketplace

### Konzept

Jedes Plugin / Theme liegt als **eigenes GitHub-Repository** vor. Die Marketplace-Indexdatei (`marketplace.json`) liegt in einem **zentralen Meta-Repository**.

```
PS-easyIT/
├── 365cms-marketplace/           ← Zentrales Index-Repo
│   ├── marketplace.json          ← Alle Produkte (öffentlich)
│   ├── plugins/
│   │   └── {plugin-slug}.json   ← Metadaten je Plugin
│   └── themes/
│       └── {theme-slug}.json    ← Metadaten je Theme
│
├── 365cms-plugin-seo-pro/        ← Einzelnes Plugin-Repo
│   ├── plugin.json               ← Plugin-Metadaten + Version
│   └── releases/
│       └── seo-pro-v1.2.0.zip
│
└── 365cms-theme-technexus/       ← Einzelnes Theme-Repo
    ├── theme.json
    └── releases/
        └── technexus-v2.1.0.zip
```

### Vorteile

- ✅ Kostenlos (GitHub Free reicht aus)
- ✅ Versionierung über Git Tags / Releases
- ✅ Automatische ZIP-Downloads über GitHub Releases API
- ✅ Community-Beiträge über Pull Requests möglich
- ✅ GitHub Actions für automatisierte Releases

### Nachteile

- ❌ Kein nativer Bezahlprozess (Drittanbieter nötig)
- ❌ Premium-Code ohne separates Hosting schwer schützbar
- ❌ Abhängigkeit von GitHub-Infrastruktur

### Technische Umsetzung

#### 3.1 Releases erstellen (GitHub Actions)

```yaml
# .github/workflows/release.yml
name: Release Plugin
on:
  push:
    tags: ['v*']
jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Create ZIP
        run: zip -r plugin.zip . -x "*.git*" -x ".github/*" -x "tests/*"
      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          files: plugin.zip
```

#### 3.2 CMS ruft GitHub API ab

```php
// core/class-marketplace-client.php

class MarketplaceClient
{
    private const INDEX_URL = 'https://raw.githubusercontent.com/PS-easyIT/365cms-marketplace/main/marketplace.json';
    private const GITHUB_API = 'https://api.github.com/repos/PS-easyIT/';

    public function getProductList(): array
    {
        $cacheKey = 'marketplace_index_' . date('Ymd');
        $cached   = get_transient($cacheKey);
        if ($cached !== false) return $cached;

        $response = $this->httpGet(self::INDEX_URL);
        $data     = json_decode($response, true) ?? [];

        set_transient($cacheKey, $data, 3600); // 1h Cache
        return $data;
    }

    public function getLatestRelease(string $repo): array
    {
        $url  = self::GITHUB_API . $repo . '/releases/latest';
        $data = json_decode($this->httpGet($url), true) ?? [];
        return [
            'version'      => ltrim($data['tag_name'] ?? '0.0.0', 'v'),
            'download_url' => $data['assets'][0]['browser_download_url'] ?? '',
            'changelog'    => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? '',
        ];
    }

    private function httpGet(string $url): string
    {
        $ctx = stream_context_create(['http' => [
            'header' => "User-Agent: 365CMS-Marketplace/1.0\r\n",
            'timeout' => 10,
        ]]);
        return (string) file_get_contents($url, false, $ctx);
    }
}
```

#### 3.3 Premium-Produkte auf GitHub

Für Premium-Plugins / -Themes gibt es zwei Ansätze:

**a) Private GitHub Repos**
```
- Repo ist privat (nur zahlende Nutzer erhalten einen temporären Download-Token)
- Token-Ausgabe über eigenen Lizenz-Server (benötigt Webspace!)
- GitHub Fine-grained PAT mit Ablaufdatum
```

**b) "Freemium"-Modell via Public Repo**
```
- Basis-Version ist öffentlich (kostenlos)
- Premium-Features als separate .zip (nur für Lizenzkäufer verfügbar)
- Download-Link wird nach Lizenzkauf per E-Mail versandt
```

---

## 4. Option B: Eigener Webspace / Self-Hosted

### Konzept

Ein dediziertes **Marketplace-Backend** läuft auf einem eigenen Server und liefert:

- **REST API** für Produktlisten, Suche, Updates
- **Zahlungsintegration** (Stripe, PayPal oder Digistore24)
- **Lizenz-Verwaltung** mit Aktivierungsschlüsseln
- **Download-Endpunkte** (token-geschützt)

### Architektur

```
Eigener Server (z. B. marketplace.365cms.de)
│
├── /api/v1/
│   ├── GET  /products              → Alle Produkte (Listing)
│   ├── GET  /products/{slug}       → Einzelnes Produkt
│   ├── GET  /products/{slug}/check-update → Neue Version verfügbar?
│   ├── POST /licenses/activate     → Lizenz aktivieren
│   ├── POST /licenses/validate     → Lizenz prüfen (im CMS)
│   └── GET  /download/{token}      → Geschützter Download
│
├── /admin/                         → Vendor-Backend (Produkte verwalten)
└── /checkout/                      → Bezahlprozess
```

### Empfohlener Tech-Stack

| Komponente | Empfehlung |
|-----------|------------|
| **Framework** | PHP 8.3 + eigenes CMS (365CMS v2 selbst!) |
| **Datenbank** | MySQL / MariaDB |
| **Zahlungsanbieter** | Stripe (Kreditkarte) + PayPal |
| **EU-Compliance** | Digistore24 als Reseller (MwSt./VAT automatisch) |
| **E-Mail** | Transaktions-E-Mails via Postmark / Brevo |

### 4.1 Produkt-Upload-Flow

```
Vendor lädt ZIP hoch
    → Validierung (plugin.json / theme.json vorhanden?)
    → Versionsprüfung (semver)
    → Virus-Scan (optional: ClamAV)
    → Speicherung in /uploads/products/{slug}/v{version}.zip
    → Eintrag in marketplace_products DB
    → Cache-Invalidierung
```

### 4.2 Kauf-Flow (Premium)

```
Nutzer klickt "Kaufen" im 365CMS Admin
    → Weiterleitung zu marketplace.365cms.de/checkout/{slug}
    → Stripe Checkout / PayPal
    → Zahlung erfolgreich → Webhook empfangen
    → Lizenzschlüssel generieren (UUID v4)
    → E-Mail mit Lizenzschlüssel + Download-Link
    → Aktivierung im CMS (Einstellungen → Lizenzschlüssel eingeben)
    → CMS validiert Lizenz via API
```

### 4.3 Lizenz-Validierung

```php
// Im CMS: Lizenz aktivieren
POST /api/v1/licenses/activate
{
    "license_key": "XXXX-YYYY-ZZZZ-AAAA",
    "product_slug": "seo-pro",
    "domain": "meine-seite.de",
    "cms_version": "2.1.0"
}

// Antwort bei Erfolg
{
    "status": "active",
    "product": "SEO Pro",
    "expires": null,           // null = Lifetime
    "update_until": "2027-02-21",
    "domains_allowed": 3,
    "domains_used": 1
}
```

---

## 5. Metadaten-Format (marketplace.json)

### Zentrale Index-Datei

```json
{
  "version": "1.0",
  "generated": "2026-02-21T12:00:00Z",
  "api_version": "v1",
  "plugins": [
    {
      "slug": "seo-pro",
      "name": "SEO Pro",
      "short_description": "Vollständige SEO-Optimierung für 365CMS",
      "type": "plugin",
      "license": "premium",
      "price": 149.00,
      "currency": "EUR",
      "version": "2.3.1",
      "min_cms_version": "2.0.0",
      "author": "PS-easyIT",
      "category": "seo",
      "tags": ["seo", "meta", "sitemap", "schema"],
      "icon": "https://assets.365cms.de/plugins/seo-pro/icon.png",
      "screenshots": [],
      "download_url": "https://api.365cms.de/v1/download/seo-pro?token={token}",
      "info_url": "https://marketplace.365cms.de/plugins/seo-pro",
      "rating": 4.8,
      "installs": 1240,
      "updated": "2026-01-15"
    }
  ],
  "themes": [
    {
      "slug": "technexus",
      "name": "TechNexus",
      "short_description": "Modernes Tech-Theme für IT-Unternehmen",
      "type": "theme",
      "license": "free",
      "price": 0,
      "currency": "EUR",
      "version": "3.0.2",
      "min_cms_version": "2.0.0",
      "author": "PS-easyIT",
      "category": "business",
      "tags": ["tech", "it", "corporate"],
      "icon": "https://assets.365cms.de/themes/technexus/preview.jpg",
      "download_url": "https://github.com/PS-easyIT/365cms-theme-technexus/releases/latest/download/technexus.zip",
      "info_url": "https://marketplace.365cms.de/themes/technexus",
      "rating": 4.6,
      "installs": 3820,
      "updated": "2026-02-01"
    }
  ]
}
```

### Plugin-Metadaten (`plugin.json` im Plugin-ZIP)

```json
{
  "name": "SEO Pro",
  "slug": "seo-pro",
  "version": "2.3.1",
  "description": "Vollständige SEO-Optimierung ...",
  "author": "PS-easyIT",
  "author_url": "https://ps-easyit.de",
  "license": "premium",
  "license_url": "https://365cms.de/lizenzen",
  "min_cms_version": "2.0.0",
  "tested_up_to": "2.1.0",
  "php_version": "8.1",
  "main_file": "seo-pro.php",
  "namespace": "CMS\\Plugins\\SeoPro",
  "hooks": ["cms_head", "cms_save_post", "cms_sitemap"],
  "requires_plugins": [],
  "changelog": {
    "2.3.1": "Bugfix: Sitemap-Generierung bei leeren Posts",
    "2.3.0": "Feature: Schema.org JSON-LD Support"
  }
}
```

---

## 6. Update-Mechanismus im CMS

```php
// core/class-update-checker.php

class UpdateChecker
{
    private string $apiUrl;

    public function checkUpdates(array $installedProducts): array
    {
        $slugs    = array_keys($installedProducts);
        $response = $this->httpPost($this->apiUrl . '/check-updates', [
            'products' => array_map(fn($slug) => [
                'slug'    => $slug,
                'version' => $installedProducts[$slug]['version'],
            ], $slugs),
            'cms_version' => CMS_VERSION,
        ]);

        return json_decode($response, true)['updates'] ?? [];
    }

    public function installUpdate(string $slug, string $downloadUrl, ?string $licenseKey = null): bool
    {
        $zipPath = TEMP_PATH . $slug . '_update.zip';

        // Download
        $headers = $licenseKey ? ["Authorization: Bearer {$licenseKey}"] : [];
        $this->downloadFile($downloadUrl, $zipPath, $headers);

        // Backup aktuelle Version
        $this->backupCurrentVersion($slug);

        // Entpacken und installieren
        return $this->extractAndInstall($zipPath, $slug);
    }
}
```

### Update-Benachrichtigungen im Admin

Das Dashboard-Widget zeigt ausstehende Updates:

```
┌─────────────────────────────────────────────┐
│ 🔄 Updates verfügbar (3)                    │
├─────────────────────────────────────────────┤
│ SEO Pro          v2.3.1 → v2.4.0   [Update] │
│ TechNexus Theme  v3.0.2 → v3.1.0   [Update] │
│ Forum Plugin     v1.2.0 → v1.3.2   [Update] │
└─────────────────────────────────────────────┘
```

---

## 7. Lizenz-System für Premium-Produkte

### Lizenzschlüssel-Format

```
XXXX-YYYY-ZZZZ-AAAA
│     │    │    └─── Prüfsumme (CRC16)
│     │    └──────── Produkt-ID (Base36)
│     └───────────── Zufalls-Token
└─────────────────── Kunden-ID (Base36)
```

### Lizenz-Engine (einfaches Modell)

```php
class LicenseManager
{
    public function activate(string $key, string $slug, string $domain): array
    {
        // 1. Schlüssel validieren (Format)
        if (!$this->isValidFormat($key)) {
            return ['status' => 'invalid', 'message' => 'Ungültiges Key-Format'];
        }

        // 2. API-Abfrage an Marketplace-Server
        $response = $this->apiPost('/licenses/activate', [
            'key'     => $key,
            'slug'    => $slug,
            'domain'  => $domain,
        ]);

        if ($response['status'] === 'active') {
            // Lokal speichern (verschlüsselt)
            $this->storeLicense($key, $slug, $response);
        }

        return $response;
    }

    public function isActive(string $slug): bool
    {
        $license = $this->getLicenseData($slug);
        if (!$license) return false;

        // Tägliche Online-Prüfung (mit Grace-Period bei Offline)
        if ($license['last_check'] < time() - 86400) {
            return $this->onlineValidate($slug, $license['key']);
        }

        return $license['status'] === 'active';
    }
}
```

---

## 8. Empfehlung & Roadmap

### Empfehlung: Hybrides Modell

| Phase | Maßnahme | Kosten |
|-------|---------|--------|
| **Phase 1** (jetzt) | Free-Themes + Free-Plugins via GitHub Releases | 0 € |
| **Phase 2** (Q3/2026) | Eigener Marketplace-Server (Shared Hosting reicht: ~5–15 €/Monat) | ~10 €/Monat |
| **Phase 3** (Q4/2026) | Zahlungsintegration (Stripe + Digistore24) | Provision: ~3–5 % |
| **Phase 4** (2027) | Vollautomatisierter Vendor-Bereich (Dritte können eigene Plugins einreichen) | Entwicklungszeit |

### Nächste Schritte (konkreter Aktionsplan)

- [ ] **GitHub-Repo anlegen:** `PS-easyIT/365cms-marketplace` als zentrales Index-Repo
- [ ] **Erste `marketplace.json`** mit bereits fertiggestellten Free-Themes befüllen
- [ ] **CMS-Modul `MarketplaceClient`** in `core/` implementieren
- [ ] **Admin-Seiten** `plugin-marketplace.php` + `theme-marketplace.php` mit Live-Daten verbinden
- [ ] **Domain reservieren:** `marketplace.365cms.de`
- [ ] **Stripe-Konto** anlegen (für DE: früh anmelden, Verifizierung dauert)
- [ ] **Lizenzsystem** implementieren (`LicenseManager` + DB-Schema)

### Verwandte Dateien

| Datei | Beschreibung |
|-------|-------------|
| [CMSv2/admin/plugin-marketplace.php](../CMSv2/admin/plugin-marketplace.php) | Plugin-Marketplace Landing (Coming Soon) |
| [CMSv2/admin/theme-marketplace.php](../CMSv2/admin/theme-marketplace.php) | Theme-Marketplace Landing (Coming Soon) |
| [CMSv2/admin/theme-settings.php](../CMSv2/admin/theme-settings.php) | Theme-Einstellungen inkl. Marketplace-Config |
| [CMSv2/admin/plugins.php](../CMSv2/admin/plugins.php) | Bestehende Plugin-Verwaltung |
| [CMSv2/admin/themes.php](../CMSv2/admin/themes.php) | Bestehende Theme-Verwaltung |

---

*Erstellt: 21.02.2026 – PS-easyIT / GitHub Copilot*
