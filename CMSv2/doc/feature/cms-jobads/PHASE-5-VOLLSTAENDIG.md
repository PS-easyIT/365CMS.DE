# cms-jobads · Phase 5: 100 % Vollständiger Ausbau (80 → 100 %)

**Ziel dieser Phase:** Das Plugin wird zur vollständig produktionsreifen, enterprise-tauglichen  
Plattform: Multisite-Mandanten, vollständiges Agentur-Kundenportal, REST-API mit OpenAPI-Spezifikation,  
Webhook-System, externe Daten-Import-Konnektoren, umfassendes Reporting und vollständige Test-Abdeckung.  
_Keine KI-Funktionen in diesem oder einem anderen Ausbaustand._

**Voraussetzung:** Phase 1–4 vollständig abgenommen  
**Zeitschätzung:** ~10–14 Entwicklungswochen

---

## Inhaltsverzeichnis

1. [Multisite / Mandanten-Architektur](#1-multisite--mandanten-architektur)
2. [Vollständiges Agentur-Kundenportal](#2-vollständiges-agentur-kundenportal)
3. [White-Label-Konfiguration](#3-white-label-konfiguration)
4. [Vollständige REST-API + OpenAPI-Spezifikation](#4-vollständige-rest-api--openapi-spezifikation)
5. [Webhook-System (ausgehend)](#5-webhook-system-ausgehend)
6. [Personio-Import-Konnektor](#6-personio-import-konnektor)
7. [softgarden-Import-Konnektor](#7-softgarden-import-konnektor)
8. [SEO pro Stellenanzeige](#8-seo-pro-stellenanzeige)
9. [RSS-Feed](#9-rss-feed)
10. [PDF-Reporting (geplant + ad-hoc)](#10-pdf-reporting-geplant--ad-hoc)
11. [Performance-Optimierung & DB-Index-Audit](#11-performance-optimierung--db-index-audit)
12. [Vollständige Test-Abdeckung](#12-vollständige-test-abdeckung)
13. [Inline-Hilfe und Benutzer-Dokumentation](#13-inline-hilfe-und-benutzer-dokumentation)
14. [Datenbank-Abschluss und Migrations-System](#14-datenbank-abschluss-und-migrations-system)
15. [Finaler Klassen- und Hook-Überblick](#15-finaler-klassen--und-hook-überblick)
16. [Rollout-Checkliste](#16-rollout-checkliste)
17. [Abnahme-Kriterien Phase 5](#17-abnahme-kriterien-phase-5)

---

## 1. Multisite / Mandanten-Architektur

### 1.1 Mandanten-Konzept

Im 365CMS läuft cms-jobads normalerweise in einem einzigen CMS-Kontext.  
Mit Phase 5 wird das Plugin mandantenfähig:

- Eine CMS-Instanz kann mehrere **Mandanten** (Firmen oder Holding-Gruppen) getrennt betreiben
- Jeder Mandant hat eigenen Admin-Bereich, keine Daten-Überschneidung
- Agentur kann beliebig viele Mandanten/Kunden anlegen

### 1.2 Mandanten-Tabelle

```sql
CREATE TABLE {prefix}jobads_tenants (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    slug              VARCHAR(100) NOT NULL UNIQUE,
    owner_company_id  INT UNSIGNED NOT NULL,
    billing_email     VARCHAR(255) DEFAULT NULL,
    plan              ENUM('basic','professional','enterprise') DEFAULT 'basic',
    is_active         TINYINT(1) DEFAULT 1,
    custom_domain     VARCHAR(255) DEFAULT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_company_id) REFERENCES {prefix}jobads_companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1.3 Tenant-Context-System

Alle DB-Abfragen laufen durch einen Kontext-Resolver:

```php
// class-tenant-context.php
class CMS_JobAds_TenantContext {

    private static int $active_tenant_id = 0;

    public static function set(int $tenant_id): void {
        self::$active_tenant_id = $tenant_id;
    }

    public static function get(): int {
        return self::$active_tenant_id;
    }

    /**
     * Reicherte WP_Query um Mandanten-Filter an.
     */
    public static function scope(array $where_parts): array {
        if (self::$active_tenant_id > 0) {
            $where_parts[] = 'tenant_id = ' . self::$active_tenant_id;
        }
        return $where_parts;
    }
}
```

### 1.4 Mandanten-Trennung auf Dateiebene

- Uploads je Mandant in eigenem Verzeichnis: `uploads/cms-jobads/tenant-{id}/`
- E-Mail-Absender je Mandant konfigurierbar
- Branding (Logo, Primärfarbe) je Mandant

---

## 2. Vollständiges Agentur-Kundenportal

### 2.1 Standalone-Portal

Das Kundenportal ist eine eigene Frontend-Route ohne CMS-Backend-Login:

```
https://cms.domain.de/jobads-portal/
```

Kunden-Authentifizierung: eigene Tabelle (nicht CMS-User-System):

```sql
CREATE TABLE {prefix}jobads_portal_users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    company_id     INT UNSIGNED NOT NULL,
    display_name   VARCHAR(255) DEFAULT NULL,
    role           ENUM('portal_admin','portal_viewer') DEFAULT 'portal_admin',
    is_active      TINYINT(1) DEFAULT 1,
    last_login_at  DATETIME DEFAULT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES {prefix}jobads_companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.2 Portal-Funktionen für Kunden

| Funktion | portal_admin | portal_viewer |
|---|:---:|:---:|
| Stellenanzeigen einsehen | ✅ | ✅ |
| Neue Anzeige anlegen | ✅ | ❌ |
| Anzeigen freigeben/ablehnen | ✅ | ❌ |
| Bewerbungs-Eingang sehen | ✅ | ✅ |
| Bewerber bewerten | ✅ | ❌ |
| Analytics anzeigen | ✅ | ✅ |
| Berichte herunterladen | ✅ | ✅ |
| Team-Mitglieder verwalten | ✅ | ❌ |

### 2.3 Portal-Session-Management

```sql
CREATE TABLE {prefix}jobads_portal_sessions (
    token       CHAR(64) PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NOT NULL,
    ip_hash     CHAR(64) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES {prefix}jobads_portal_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Session-Lebensdauer: 8 Stunden (konfigurierbar), sliding renewal bei Aktivität.

---

## 3. White-Label-Konfiguration

### 3.1 Branding pro Mandant / Firma

```sql
CREATE TABLE {prefix}jobads_branding (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type      ENUM('tenant','company') NOT NULL,
    entity_id        INT UNSIGNED NOT NULL,
    logo_path        VARCHAR(500) DEFAULT NULL,
    favicon_path     VARCHAR(500) DEFAULT NULL,
    primary_color    CHAR(7) DEFAULT '#0055A5',
    secondary_color  CHAR(7) DEFAULT '#F2F6FC',
    font_family      VARCHAR(100) DEFAULT NULL,
    custom_css       TEXT DEFAULT NULL,     -- zusätzliches CSS (bereinigt)
    portal_title     VARCHAR(255) DEFAULT NULL,
    portal_tagline   VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_branding (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 Branding-Anwendung

- Alle Frontend-Seiten (Jobboard, Widget, Portal) laden Branding per Entity-Kontext
- E-Mail-Templates verwenden `{branding_logo}` und `{primary_color}` als Platzhalter
- Admin: „Vorschau als Kandidat" zeigt exakt das White-Label-Erscheinungsbild

---

## 4. Vollständige REST-API + OpenAPI-Spezifikation

### 4.1 Endpunkte Phase 5 (Erweiterung)

```
# Stellenanzeigen
GET    /api/v1/jobads/ads
GET    /api/v1/jobads/ads/{id}
POST   /api/v1/jobads/ads                   (AUTH)
PUT    /api/v1/jobads/ads/{id}              (AUTH)
DELETE /api/v1/jobads/ads/{id}              (AUTH)
POST   /api/v1/jobads/ads/{id}/publish      (AUTH)
POST   /api/v1/jobads/ads/{id}/unpublish    (AUTH)

# Firmen
GET    /api/v1/jobads/companies/{slug}/ads

# Bewerbungen
GET    /api/v1/jobads/ads/{id}/applications (AUTH)
POST   /api/v1/jobads/ads/{id}/apply
GET    /api/v1/jobads/applications/{id}     (AUTH)
PATCH  /api/v1/jobads/applications/{id}/stage (AUTH)

# Profile
GET    /api/v1/jobads/profiles/skills
GET    /api/v1/jobads/profiles/benefits
GET    /api/v1/jobads/profiles/conditions

# Webhook-Registrierung
GET    /api/v1/jobads/webhooks              (AUTH)
POST   /api/v1/jobads/webhooks              (AUTH)
DELETE /api/v1/jobads/webhooks/{id}         (AUTH)

# Analytics
GET    /api/v1/jobads/analytics/summary     (AUTH)
GET    /api/v1/jobads/analytics/ads/{id}    (AUTH)
```

### 4.2 OpenAPI-Spezifikation

Die vollständige OpenAPI-3.0-YAML-Datei wird als `openapi.yaml` im Plugin-Verzeichnis  
`docs/api/` abgelegt und ist auch über einen Admin-Endpunkt abrufbar:

```
GET /api/v1/jobads/openapi.yaml
```

**Minimal-Beispiel für einen Endpunkt:**

```yaml
/ads:
  get:
    summary: Liste aller aktiven Stellenanzeigen
    operationId: listJobAds
    parameters:
      - in: query
        name: page
        schema: { type: integer, default: 1 }
      - in: query
        name: per_page
        schema: { type: integer, default: 10, maximum: 100 }
      - in: query
        name: branch
        schema: { type: string }
    responses:
      '200':
        description: Erfolgreiche Antwort
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/JobAdListResponse'
```

### 4.3 API-Key-Verwaltung im Admin

```sql
CREATE TABLE {prefix}jobads_api_keys (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    name         VARCHAR(100) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    scopes       JSON NOT NULL,           -- z.B. ["read","write","apply_read"]
    last_used_at DATETIME DEFAULT NULL,
    expires_at   DATE DEFAULT NULL,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES {prefix}jobads_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Webhook-System (ausgehend)

### 5.1 Webhook-Konfiguration

```sql
CREATE TABLE {prefix}jobads_webhooks (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id     INT UNSIGNED DEFAULT NULL,
    name           VARCHAR(255) NOT NULL,
    url            VARCHAR(500) NOT NULL,
    secret         VARCHAR(100) DEFAULT NULL,   -- HMAC-Signatur-Secret
    events         JSON NOT NULL,               -- abonnierte Events
    is_active      TINYINT(1) DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5.2 Verfügbare Webhook-Events

```
jobads.application.new              → neue Bewerbung eingegangen
jobads.application.stage_changed    → Pipeline-Stufe geändert
jobads.application.rated            → Bewerbung bewertet
jobads.ad.published                 → Anzeige live geschaltet
jobads.ad.expired                   → Anzeige abgelaufen
jobads.ad.filled                    → Stelle als besetzt markiert
jobads.approval.requested           → Freigabe-Anfrage eingegangen
jobads.approval.approved            → Freigegeben
jobads.approval.rejected            → Abgelehnt
```

### 5.3 Delivery-Mechanismus

```php
// class-webhook-dispatcher.php
class CMS_JobAds_WebhookDispatcher {

    public function dispatch(string $event, array $payload): void {
        $webhooks = $this->get_active_webhooks_for($event);

        foreach ($webhooks as $webhook) {
            // Sofortiger Versuch (inline oder Short-Queue)
            $this->deliver($webhook, $event, $payload);
        }
    }

    private function deliver(object $webhook, string $event, array $payload): void {
        $body = json_encode([
            'event'   => $event,
            'ts'      => time(),
            'data'    => $payload,
        ]);

        $signature = hash_hmac('sha256', $body, $webhook->secret ?: '');

        $response = $this->http_post($webhook->url, $body, [
            'Content-Type'       => 'application/json',
            'X-JobAds-Event'     => $event,
            'X-JobAds-Signature' => 'sha256=' . $signature,
        ]);

        $this->log_delivery($webhook->id, $event, $response);
    }
}
```

### 5.4 Webhook-Delivery-Log

```sql
CREATE TABLE {prefix}jobads_webhook_deliveries (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id     INT UNSIGNED NOT NULL,
    event          VARCHAR(100) NOT NULL,
    payload_json   TEXT NOT NULL,
    response_code  SMALLINT UNSIGNED DEFAULT NULL,
    response_body  TEXT DEFAULT NULL,
    is_success     TINYINT(1) DEFAULT NULL,
    attempt        TINYINT UNSIGNED DEFAULT 1,
    delivered_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES {prefix}jobads_webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Retry-Logik:** Bei HTTP 5xx oder Timeout: 3 Wiederholungen (nach 5 min, 30 min, 2 h).

---

## 6. Personio-Import-Konnektor

### 6.1 Wie Personio-Import funktioniert

Personio exportiert Jobs als XML-Feed über eine öffentliche URL:  
`https://mycompany.jobs.personio.de/xml`

Der Import-Konnektor fetcht diesen Feed in konfigurierbarem Intervall (Standard: täglich):

```php
// class-importer-personio.php
class CMS_JobAds_Importer_Personio {

    public function import(string $feed_url, int $company_id): ImportResult {
        $xml = $this->fetch_xml($feed_url);
        $result = new ImportResult();

        foreach ($xml->position as $pos) {
            $mapped = $this->map_to_jobad($pos, $company_id);
            $existing = $this->find_by_external_ref($mapped['external_ref']);

            if ($existing) {
                $this->update($existing['id'], $mapped);
                $result->updated++;
            } else {
                $this->create($mapped);
                $result->created++;
            }
        }
        return $result;
    }

    private function map_to_jobad(SimpleXMLElement $pos, int $company_id): array {
        return [
            'title'          => (string) $pos->name,
            'company_id'     => $company_id,
            'external_source'=> 'personio',
            'external_ref'   => (string) $pos->id,
            'tasks'          => (string) $pos->jobDescriptions->jobDescription[0]->value,
            'location'       => (string) $pos->office,
            // ... weiteres Mapping
        ];
    }
}
```

### 6.2 Import-Konfiguration

```sql
ALTER TABLE {prefix}jobads_job_ads
    ADD COLUMN external_source  VARCHAR(60) DEFAULT NULL,
    ADD COLUMN external_ref     VARCHAR(255) DEFAULT NULL,
    ADD COLUMN external_sync_at DATETIME DEFAULT NULL,
    ADD UNIQUE KEY uq_external_ref (external_source, external_ref);
```

**Verhalten bei Import:**
- Importierte Anzeigen starten im Status `intern_review` (müssen intern freigegeben werden)
- Bei Update (re-fetch): Inhalt aktualisiert, Status beibehalten
- Manuelle Änderungen am Inhalt: überschreiben bei nächstem Import (konfigurierbar: „manual overrides protected")

---

## 7. softgarden-Import-Konnektor

Analoges Vorgehen zu Personio. softgarden bietet eine REST-API statt XML.

```php
// class-importer-softgarden.php
class CMS_JobAds_Importer_softgarden {

    public function import(string $api_token, int $channel_id, int $company_id): ImportResult {
        $jobs = $this->fetch_jobs($api_token, $channel_id);
        // gleiches Mapping-Prinzip wie Personio
    }

    private function fetch_jobs(string $token, int $channel_id): array {
        $url = 'https://api.softgarden.io/api/rest/3/jobs';
        // GET-Request mit Bearer-Token
        // Response: JSON-Array mit Job-Objekten
    }
}
```

**Konfigurierbar im Admin:**  
- Feed-URL / API-Token hinterlegen  
- Sync-Intervall: täglich / stündlich / manuell  
- Mapping-Review-Modus: importierte Felder vor Speichern prüfen  

---

## 8. SEO pro Stellenanzeige

### 8.1 Meta-Felder

```sql
CREATE TABLE {prefix}jobads_ad_seo (
    job_ad_id       INT UNSIGNED PRIMARY KEY,
    meta_title      VARCHAR(70) DEFAULT NULL,
    meta_description VARCHAR(160) DEFAULT NULL,
    og_title        VARCHAR(100) DEFAULT NULL,
    og_description  VARCHAR(200) DEFAULT NULL,
    og_image_path   VARCHAR(500) DEFAULT NULL,
    canonical_url   VARCHAR(500) DEFAULT NULL,
    robots          ENUM('index,follow','noindex,nofollow','index,nofollow','noindex,follow')
                    DEFAULT 'index,follow',
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 8.2 Auto-Generierung (kein KI)

Falls keine manuellen SEO-Felder gesetzt: Template-basierte Auto-Generierung:

```
Meta-Title:   "{Titel} (m/w/d) – {Firmenname} – {Ort}"
Meta-Desc:    "{Firmenname} sucht {Titel} in {Ort}. {Ersatztext-Teaser, 80 Zeichen}"
OG-Titel:     gleich wie Meta-Title
OG-Image:     Firmen-Logo als fallback
```

### 8.3 Sitemap-Integration

Alle aktiven Stellenanzeigen erscheinen automatisch in der CMS-Sitemap  
(wenn CMS-Sitemap-Modul vorhanden), mit:
- `<lastmod>` = Datum der letzten Änderung
- `<changefreq>` = `weekly`
- `<priority>` = `0.8`

---

## 9. RSS-Feed

Stellenanzeigen sind auch als RSS 2.0-Feed verfügbar:

```
https://cms.domain.de/jobs/feed/rss
https://cms.domain.de/jobs/feed/rss?branch=it_technology
https://cms.domain.de/jobs/feed/rss?company=firma-slug
```

**RSS-Item-Format:**

```xml
<item>
  <title>Senior Backend Developer (m/w/d) – Firma GmbH</title>
  <link>https://cms.domain.de/jobs/senior-backend-developer/</link>
  <guid isPermaLink="true">https://cms.domain.de/jobs/senior-backend-developer/</guid>
  <pubDate>Sat, 15 Feb 2026 09:00:00 +0100</pubDate>
  <description><![CDATA[Kurzbeschreibung der Stelle...]]></description>
  <category>IT & Technologie</category>
</item>
```

---

## 10. PDF-Reporting (geplant + ad-hoc)

### 10.1 Report-Typen

| Report | Auslöser | Empfänger |
|---|---|---|
| Wochen-Zusammenfassung | Cron: jeden Montag | Firmen-Admin |
| Monats-Report | Cron: 1. d. Monats | Firmen-Admin + Agentur |
| Stellen-Abschluss-Report | Stelle besetzt/archiviert | Personalleiter |
| Kunden-Quartals-Report | Cron: quartalsweise | Agentur-Admin |
| Ad-hoc (manuell) | Admin-Button | sofort als Download |

### 10.2 PDF-Generierung

Bibliothek: `mPDF` oder `Dompdf` (CMS-intern verfügbar).  
Template-System: HTML-Templates in `templates/reports/` — komplett per CSS gestylbar.

```
templates/reports/
├── monthly-report.html          ← Monats-Report-Template
├── job-ad-closing-report.html   ← Stellen-Abschluss
├── agency-quarterly.html        ← Agentur-Quartals-Report
└── partials/
    ├── header.html
    ├── footer.html
    └── chart-placeholder.html   ← Tabellen-basierte Charts (kein JS)
```

### 10.3 Report-Scheduler-Tabelle

```sql
CREATE TABLE {prefix}jobads_report_schedules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    report_type     VARCHAR(60) NOT NULL,
    schedule        ENUM('weekly','monthly','quarterly') NOT NULL,
    recipients_json JSON NOT NULL,
    last_sent_at    DATETIME DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    FOREIGN KEY (company_id) REFERENCES {prefix}jobads_companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 11. Performance-Optimierung & DB-Index-Audit

### 11.1 DB-Index-Audit

Vollständige Index-Überprüfung aller 50+ Tabellen. Ziel:  
- Alle WHERE-Spalten mit häufiger Filterung haben Index
- Keine überflüssigen/doppelten Indizes
- Composite Indizes wo sinnvoll (z. B. `(job_ad_id, event_type, created_at)`)

**Kritische Indizes die Phase 5 sicherstellt:**

```sql
-- Tracking: Zeitbasierte Abfragen
ALTER TABLE {prefix}jobads_tracking
    ADD INDEX idx_created (created_at),
    ADD INDEX idx_ad_channel (job_ad_id, channel);

-- Applications: Pipeline-Filter
ALTER TABLE {prefix}jobads_applications
    ADD INDEX idx_pipeline (job_ad_id, pipeline_stage);

-- Audit Log: Zeitbasiertes Archiv
ALTER TABLE {prefix}jobads_audit_log
    ADD INDEX idx_created (created_at)
    ADD INDEX idx_entity (entity_type, entity_id);
```

### 11.2 Query-Cache-Schicht

Für teure, häufige Abfragen (Analytics-Aggregationen, Profile-Catalogs):

```php
// class-cache.php
class CMS_JobAds_Cache {

    public function get_analytics_summary(int $company_id, string $period): array {
        $cache_key = "jobads_analytics_{$company_id}_{$period}";
        $cached    = cms_cache_get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->compute_analytics($company_id, $period);
        cms_cache_set($cache_key, $data, 3600);   // 1 Stunde
        return $data;
    }

    public function flush_for_company(int $company_id): void {
        // Alle Cache-Keys für diese Firma ungültig machen
        cms_cache_delete_group("jobads_{$company_id}");
    }
}
```

### 11.3 Feed-Caching

Indeed/BA/StepStone XML-Feeds: 15-Minuten-Cache.  
JSON-REST-API: 5-Minuten-Cache für GET-Endpunkte.  
Widget-Embed-Script: 1-Stunden-Cache mit ETag/Last-Modified-Header.

---

## 12. Vollständige Test-Abdeckung

### 12.1 Test-Struktur

```
tests/
├── unit/
│   ├── InheritanceEngineTest.php        ← Phase 2: resolve(), propagate()
│   ├── WorkflowStatusTest.php           ← Phase 3: Status-Transitions
│   ├── FeedGeneratorTest.php            ← Phase 3/4: XML-Validierung
│   ├── GdprSchedulerTest.php            ← Phase 4/5: Löschlogik
│   ├── WebhookDispatcherTest.php        ← Phase 5: Signatur, Retry
│   ├── ImporterPersonioTest.php         ← Phase 5: Mapping
│   └── SeoAutoGeneratorTest.php         ← Phase 5: Template-Ausgabe
├── integration/
│   ├── ApplicationPipelineTest.php      ← Vollständiger Bewerbungs-Fluss
│   ├── PublicationFlowTest.php          ← Anzeige erstellen → live
│   ├── ApprovalWorkflowTest.php         ← Genehmigungs-Zyklus
│   └── MultiTenantIsolationTest.php     ← Mandant A sieht NICHT Mandant B
└── fixtures/
    ├── personio-sample-feed.xml
    ├── softgarden-sample-response.json
    ├── sample-company.sql
    └── sample-job-ads.sql
```

### 12.2 Coverage-Ziele

| Bereich | Ziel |
|---|---|
| Inheritance Engine | ≥ 95 % |
| Workflow Status Machine | ≥ 95 % |
| Feed-Generatoren | ≥ 90 % |
| REST-API | ≥ 85 % |
| Webhook Dispatcher | ≥ 90 % |
| Importeure | ≥ 90 % |
| **Gesamt** | **≥ 85 %** |

### 12.3 Kritische Test-Cases

```php
// InheritanceEngineTest.php

/** @test */
public function forced_item_cannot_be_overridden_by_child(): void {
    $profile = ProfileFactory::skill(inherit_mode: 'forced', item: 'php_5_years');
    $override = EntityOverrideFactory::department(item: 'php_3_years');

    $resolved = (new CMS_JobAds_Inheritance())->resolve(
        'skill', $profile->id, entity_type: 'department', entity_id: $override->dept_id
    );

    $this->assertSame('php_5_years', $resolved['php_level']);
}

/** @test */
public function default_item_propagates_update_notice_on_change(): void {
    $profile = ProfileFactory::benefit(inherit_mode: 'default');
    $profile->items()->updateItem('homeoffice_days', 3);
    
    // Abteilung hat überschrieben
    EntityOverrideFactory::department(['homeoffice_days' => 2]);

    // Master auf 4 erhöhen
    $profile->items()->updateItem('homeoffice_days', 4);

    $notice = UpdateNotice::findForDepartment($profile->id);
    $this->assertNotNull($notice);
    $this->assertEquals('homeoffice_days', $notice->field_key);
}
```

---

## 13. Inline-Hilfe und Benutzer-Dokumentation

### 13.1 Kontext-Hilfe-System

Jeder Admin-Bereich hat ein ausklappbares Hilfe-Panel (oben rechts: **?**):

```sql
CREATE TABLE {prefix}jobads_help_texts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    context_key VARCHAR(100) NOT NULL UNIQUE,   -- z.B. 'profile_editor_inherit_mode'
    title       VARCHAR(255) NOT NULL,
    content     TEXT NOT NULL,
    locale      CHAR(5) DEFAULT 'de_DE',
    updated_at  DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 13.2 Tooltips

Besonders für das Vererbungs-System: jedes Icon (🔒 / 🔓 / ⬜) hat einen  
Tooltip mit Erklärung in einfacher Sprache:

```
🔒 „Dieser Wert ist gesperrt. Untergeordnete Abteilungen können ihn 
    nicht ändern. Änderungen hier werden sofort weitergegeben."

🔓 „Dies ist ein Standardwert. Abteilungen dürfen ihn für sich 
    anpassen. Wenn Sie den Wert hier ändern, sehen Abteilungen 
    die ihn überschrieben haben eine Meldung."

⬜ „Optionales Element. Abteilungen können es aktivieren, müssen 
    es aber nicht."
```

### 13.3 Dokumentations-Index (Admin)

Unter **Hilfe → Dokumentation**: vollständige, durchsuchbare Admin-Doku  
direkt im CMS-Backend, ohne externe Links.

Struktur:
```
Erste Schritte
  └─ Plugin installieren & konfigurieren
  └─ Erstanzeige erstellen

Profil-System
  └─ Skill-Profile anlegen
  └─ Vererbung verstehen (mit Beispielen)
  └─ Abteilungen anpassen lassen (Delegation)

Stellenanzeigen
  └─ Anzeige von Null erstellen
  └─ Genehmigungsworkflow einrichten
  └─ Auf Portalen veröffentlichen

Bewerbungs-Management
  └─ Das Kanban-Board
  └─ Bewerber bewerten und vergleichen
  └─ Absagen versenden

Reporting & Datenschutz
  └─ Analytics verstehen
  └─ DSGVO-Pflichten erfüllen
  └─ PDF-Berichte einrichten
```

---

## 14. Datenbank-Abschluss und Migrations-System

### 14.1 Vollständige Tabellen-Liste (Phase 1–5)

**Phase 1 (8 Tabellen):**
```
{prefix}jobads_companies
{prefix}jobads_departments
{prefix}jobads_positions
{prefix}jobads_benefits_catalog
{prefix}jobads_conditions
{prefix}jobads_job_ads
{prefix}jobads_benefit_assignments
{prefix}jobads_audit_log
```

**Phase 2 (+14 = 22 Tabellen):**
```
{prefix}jobads_skills_catalog
{prefix}jobads_skill_profiles
{prefix}jobads_skill_profile_items
{prefix}jobads_entity_skill_overrides
{prefix}jobads_benefit_profiles
{prefix}jobads_benefit_profile_items
{prefix}jobads_entity_benefit_overrides
{prefix}jobads_condition_profiles
{prefix}jobads_condition_profile_items
{prefix}jobads_entity_condition_overrides
{prefix}jobads_update_notices
{prefix}jobads_branches
{prefix}jobads_branch_field_configs
{prefix}jobads_rbac_assignments
```

**Phase 3 (+8 = 30 Tabellen):**
```
{prefix}jobads_workflow_configs
{prefix}jobads_approval_steps
{prefix}jobads_approval_log
{prefix}jobads_email_templates
{prefix}jobads_publications
{prefix}jobads_feed_configs
{prefix}jobads_applications
{prefix}jobads_application_uploads
```

**Phase 4 (+13 = 43 Tabellen):**
```
{prefix}jobads_application_ratings
{prefix}jobads_application_comments
{prefix}jobads_pipeline_stages
{prefix}jobads_channel_credentials
{prefix}jobads_channel_budgets
{prefix}jobads_profile_versions
{prefix}jobads_delegations
{prefix}jobads_tracking
{prefix}jobads_gdpr_schedules
{prefix}jobads_consent_log
{prefix}jobads_rejection_templates
{prefix}jobads_interviews
{prefix}jobads_ad_translations
```

**Phase 5 (+12 = 55 Tabellen):**
```
{prefix}jobads_tenants
{prefix}jobads_portal_users
{prefix}jobads_portal_sessions
{prefix}jobads_branding
{prefix}jobads_api_keys
{prefix}jobads_webhooks
{prefix}jobads_webhook_deliveries
{prefix}jobads_ad_seo
{prefix}jobads_report_schedules
{prefix}jobads_help_texts
{prefix}jobads_importer_configs
{prefix}jobads_importer_log
```

**GESAMT: 55 Tabellen**

### 14.2 Migrations-System

```php
// class-migrations.php
class CMS_JobAds_Migrations {

    /** Alle Migrations in Reihenfolge */
    private static array $migrations = [
        '1.0.0' => 'Migration_1_0_0_Initial',
        '2.0.0' => 'Migration_2_0_0_Profiles',
        '3.0.0' => 'Migration_3_0_0_Workflow',
        '4.0.0' => 'Migration_4_0_0_ProfiFeatures',
        '5.0.0' => 'Migration_5_0_0_Enterprise',
    ];

    public function run(): void {
        $current = cms_option('cms_jobads_db_version', '0.0.0');

        foreach (self::$migrations as $version => $class) {
            if (version_compare($current, $version, '<')) {
                (new $class())->up();
                cms_option_set('cms_jobads_db_version', $version);
                $current = $version;
            }
        }
    }
}
```

---

## 15. Finaler Klassen- und Hook-Überblick

### 15.1 Komplette Klassen-Übersicht

```
cms-jobads/
├── cms-jobads.php                          ← Singleton-Bootstrap
├── uninstall.php
├── includes/
│   ├── class-activator.php
│   ├── class-migrations.php
│   ├── class-post-types.php                ← CPTs (Phase 1)
│   ├── class-companies.php
│   ├── class-departments.php
│   ├── class-positions.php
│   ├── class-inheritance.php               ← Kern Phase 2
│   ├── class-skill-profiles.php
│   ├── class-benefit-profiles.php
│   ├── class-condition-profiles.php
│   ├── class-profile-versions.php
│   ├── class-profile-io.php
│   ├── class-rbac.php
│   ├── class-permissions.php
│   ├── class-delegations.php
│   ├── class-workflow.php                  ← Kern Phase 3
│   ├── class-approval.php
│   ├── class-mailer.php
│   ├── class-publisher.php
│   ├── class-compliance.php
│   ├── class-applications.php
│   ├── class-feed-indeed.php
│   ├── class-feed-ba.php
│   ├── class-feed-stepstone.php
│   ├── class-feed-linkedin.php
│   ├── class-feed-xing.php
│   ├── class-kanban.php                    ← Phase 4
│   ├── class-application-ratings.php
│   ├── class-pipeline.php
│   ├── class-analytics.php
│   ├── class-gdpr-manager.php
│   ├── class-rejection-manager.php
│   ├── class-interviews.php
│   ├── class-translations.php
│   ├── class-widget.php
│   ├── class-rest-api.php
│   ├── class-tenant-context.php            ← Phase 5
│   ├── class-portal.php
│   ├── class-branding.php
│   ├── class-webhook-dispatcher.php
│   ├── class-importer-personio.php
│   ├── class-importer-softgarden.php
│   ├── class-seo.php
│   ├── class-rss-feed.php
│   ├── class-pdf-reports.php
│   ├── class-cache.php
│   └── class-help-system.php
├── templates/
│   ├── frontend/
│   ├── admin/
│   ├── emails/
│   ├── reports/
│   └── widget/
├── assets/
│   ├── css/
│   └── js/
└── docs/
    └── api/
        └── openapi.yaml
```

### 15.2 Alle Hook-Kategorien (Summary)

```
Phase 1:  jobads_after_ad_created, jobads_after_company_created
Phase 2:  jobads_inheritance_resolved, jobads_profile_propagated, jobads_update_notice_created
Phase 3:  jobads_status_changed, jobads_ad_published, jobads_ad_expired, jobads_application_received
Phase 4:  jobads_application_rated, jobads_interview_scheduled, jobads_delegation_created
Phase 5:  jobads_webhook_dispatched, jobads_importer_run, jobads_report_generated
```

---

## 16. Rollout-Checkliste

**Vor dem Go-live:**

- [ ] Alle 5 Phasen installiert und Migrations durchgelaufen
- [ ] DB-Version: `5.0.0` in `cms_options`
- [ ] Index-Audit abgeschlossen, kein `FULL TABLE SCAN` bei Haupt-Queries
- [ ] Feed-URLs (Indeed, BA) mit Aggregatoren verifiziert
- [ ] LinkedIn/XING OAuth-Credentials aktiv und getestet
- [ ] Webhook-Test-Endpoint eingerichtet und Delivery-Log grün
- [ ] DSGVO-Lösch-Cron läuft (tägliche Ausführung bestätigt)
- [ ] PDF-Reporting: mPDF/Dompdf korrekt installiert + erzeugt valides PDF
- [ ] OpenAPI-Spec unter `/api/v1/jobads/openapi.yaml` abrufbar
- [ ] Portal-Login funktioniert (Portal-User anlegen + einloggen)
- [ ] Mandanten-Trennung: Test-Mandant A kann Daten von Mandant B nicht sehen
- [ ] SEO-Meta-Tags auf Anzeigen-Detailseiten vorhanden
- [ ] Sitemap enthält aktive Stellenanzeigen
- [ ] RSS-Feed valide (W3C Feed Validator)
- [ ] Test-Suite: `composer test` → ≥ 85 % Gesamtabdeckung, 0 Fehler
- [ ] Inline-Hilfe: alle Tooltips angezeigt
- [ ] DSGVO-Einstellungsseite vollständig: Löschfristen, Einwilligungstext
- [ ] Muster-Absage-Templates für alle Branchen angelegt
- [ ] Staging-Abnahme durch PO unterschrieben

---

## 17. Abnahme-Kriterien Phase 5

- [ ] Mandant B anlegen: alle CRUD-Operationen korrekt isoliert
- [ ] Portal-Login: Kunde A kann sich einloggen, sieht nur eigene Anzeigen
- [ ] Portal-Viewer: Klick auf „Anzeige erstellen" → Zugriff verweigert
- [ ] White-Label: Firmen-Logo und Primärfarbe erscheinen im Portal
- [ ] REST-API GET /ads: korrekte JSON-Response, Paginierung
- [ ] REST-API POST /ads/{id}/apply: Bewerbung in DB, Bestätigungs-E-Mail
- [ ] REST-API: Ungültiger API-Key → 401
- [ ] Webhook: Event `jobads.application.new` ausgelöst, Delivery-Log grün
- [ ] Webhook: HMAC-Signatur korrekt berechenbar mit Client-Secret
- [ ] Webhook: Retry nach simuliertem 500-Fehler funktioniert
- [ ] Personio-Import: Sample-Feed importiert, 5 Anzeigen korrekt gemappt
- [ ] softgarden-Import: Sample-Response importiert, korrekt gemappt
- [ ] SEO: Meta-Tags auf Detailseite vorhanden und korrekt befüllt
- [ ] Sitemap: enthält frisch erstellte Testanzeige
- [ ] RSS: Feed parsed sauber im Feed-Reader
- [ ] PDF-Monats-Report: generiert, korrekte Zahlen, Firmenlogo sichtbar
- [ ] Statistik-Cache: zweiter Aufruf nutzt Cache (Logging zeigt HIT)
- [ ] Unit-Tests: alle grün, Coverage ≥ 85 %
- [ ] Integration-Test Mandanten-Isolation: Test-Assert bestanden
- [ ] Inline-Hilfe: Hilfe-Panel auf Profil-Editor öffnet sich
- [ ] Tooltip 🔒-Icon: korrekte Erklärung angezeigt

---

**← Zurück zu:** [PHASE-4-PROFI-FEATURES.md](PHASE-4-PROFI-FEATURES.md)  
**→ Zurück zum Index:** [../INDEX.md](../INDEX.md)

---

```
┌─────────────────────────────────────────────────────────────┐
│  cms-jobads: 5-Phasen-Implementierungsplan  · VOLLSTÄNDIG  │
│                                                              │
│  Phase 1 (20%)  ✔ Fundament & Grundstruktur                │
│  Phase 2 (40%)  ✔ Profil-System & Vererbung                │
│  Phase 3 (60%)  ✔ Workflow, Veröffentlichung, Frontend     │
│  Phase 4 (80%)  ✔ Profi-Features & Analytics               │
│  Phase 5 (100%) ✔ Enterprise, API, Tests, Dokumentation    │
└─────────────────────────────────────────────────────────────┘
```

*Stand: 19. Februar 2026 · cms-jobads Phase 5/5*
