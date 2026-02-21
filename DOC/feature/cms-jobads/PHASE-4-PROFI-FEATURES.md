# cms-jobads · Phase 4: Profi-Features (60 → 80 %)

**Ziel dieser Phase:** Das Plugin erhält alle Features für den professionellen Produktionseinsatz:  
vollständige Kanal-Anbindung (StepStone, LinkedIn, XING), Bewerbungs-Kanban mit allen  
Management-Funktionen, vollständiges Analytics-Dashboard, Profil-Verwaltung mit  
Versionierung/Import/Export, DSGVO-Automation und Delegations-System.

**Voraussetzung:** Phase 1–3 vollständig abgenommen  
**Zeitschätzung:** ~8–10 Entwicklungswochen

---

## Inhaltsverzeichnis

1. [Bewerbungs-Kanban-Board](#1-bewerbungs-kanban-board)
2. [Vollständige Pipeline-Verwaltung](#2-vollständige-pipeline-verwaltung)
3. [Kanal-Erweiterungen (StepStone, LinkedIn, XING)](#3-kanal-erweiterungen-stepstone-linkedin-xing)
4. [Profil-Versionierung](#4-profil-versionierung)
5. [Profil-Import / Export](#5-profil-import--export)
6. [Profil-Duplikation mit Kontextanpassung](#6-profil-duplikation-mit-kontextanpassung)
7. [Delegations-System (vollständig)](#7-delegations-system-vollständig)
8. [Analytics-Dashboard](#8-analytics-dashboard)
9. [DSGVO-Vollautomatik](#9-dsgvo-vollautomatik)
10. [Absage-Management](#10-absage-management)
11. [Interview-Terminplanung (intern)](#11-interview-terminplanung-intern)
12. [Mehrsprachige Anzeigen (DE/EN)](#12-mehrsprachige-anzeigen-deen)
13. [Externe Website-Widget](#13-externes-website-widget)
14. [REST-API-Feed (intern)](#14-rest-api-feed-intern)
15. [Datenbank-Erweiterungen Phase 4](#15-datenbank-erweiterungen-phase-4)
16. [Klassen & Hooks Phase 4](#16-klassen--hooks-phase-4)
17. [Abnahme-Kriterien Phase 4](#17-abnahme-kriterien-phase-4)

---

## 1. Bewerbungs-Kanban-Board

### 1.1 Board-Layout

Das Kanban-Board ist die zentrale Arbeitsfläche für alle Bewerber einer Stelle.

```
┌──────────┬──────────┬──────────┬──────────┬──────────┬──────────┐
│EINGEGANGEN│GESICHTET │IN PRÜFUNG│VORAUSWAHL│INTERVIEW │ ANGEBOT  │
│   (4)    │   (7)    │   (3)    │   (2)    │   (1)    │   (1)    │
├──────────┼──────────┼──────────┼──────────┼──────────┼──────────┤
│▓▓▓▓▓▓▓▓▓▓│▓▓▓▓▓▓▓▓▓▓│          │          │          │          │
│Max M.    │Anna K.   │          │          │          │          │
│★★★★☆    │★★★☆☆    │          │          │          │          │
│[Ansehen] │[Ansehen] │          │          │          │          │
├──────────┼──────────┤          │          │          │          │
│▓▓▓▓▓▓▓▓▓▓│▓▓▓▓▓▓▓▓▓▓│          │          │          │          │
│Lars B.   │Petra S.  │          │          │          │          │
│...       │...       │          │          │          │          │
└──────────┴──────────┴──────────┴──────────┴──────────┴──────────┘
```

Zusätzliche Spalten (immer am Ende): **Besetzt** | **Absage**

### 1.2 Karten-Inhalt

Jede Bewerbungs-Karte zeigt:
- Name und Vorname
- Stern-Bewertung (1–5)
- Eingangsdatum + Zeit seit Eingang
- Quell-Kanal (CMS-Formular, Indeed, LinkedIn, …)
- Farbige Tags (manuell vergeben)
- Klick öffnet Detailpanel rechts

### 1.3 Drag & Drop

Karten können per Drag & Drop zwischen Pipeline-Spalten verschoben werden.  
Bei Verschiebung wird automatisch:
1. `pipeline_stage` in DB aktualisiert
2. `last_updated` gesetzt
3. Audit-Log-Eintrag geschrieben
4. Optional: Status-E-Mail an Bewerber ausgelöst (konfigurierbar per Spalte)

### 1.4 Bewerbungs-Detailpanel

```
┌──────────────────────────────────────────────────────┐
│ Max Mustermann                    [Kontaktieren]     │
│ ★★★★☆   Eingegangen: 14.02.2026  [In Pipeline ▾]    │
├──────────────────────────────────────────────────────┤
│ 📎 CV herunterladen (.pdf)                            │
│ 📎 Zeugnis herunterladen                              │
├──────────────────────────────────────────────────────┤
│ ANSCHREIBEN                                           │
│ [expandierbar]                                        │
├──────────────────────────────────────────────────────┤
│ BEWERTUNG DURCH TEAM                                  │
│ Anna K.: ★★★★☆ „Starker Background in PHP"          │
│ Lars B.: ★★★☆☆ „Softskills unklar"                  │
├──────────────────────────────────────────────────────┤
│ INTERNE NOTIZ                                         │
│ [Textarea, speichern]                                 │
├──────────────────────────────────────────────────────┤
│ TAGS: [PHP] [Hamburg] [verfügbar sofort] [+]         │
├──────────────────────────────────────────────────────┤
│ DSGVO: Löschen am 15.08.2026  [Frist verlängern]     │
└──────────────────────────────────────────────────────┘
```

---

## 2. Vollständige Pipeline-Verwaltung

### 2.1 Bewertungs-System pro Bewerber

**Mehrdimensionale Bewertung:**

```sql
CREATE TABLE {prefix}jobads_application_ratings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    rated_by        INT UNSIGNED NOT NULL,
    category        ENUM('fachlich','soft_skills','kulturfit','ersteindruck','gesamt') NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    note            VARCHAR(500) DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating (application_id, rated_by, category),
    FOREIGN KEY (application_id) REFERENCES {prefix}jobads_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Durchschnitts-Berechnung:** Automatisch on-the-fly in der PHP-Klasse,  
kein Denormalisierungs-Problem.

### 2.2 Bewerber-Vergleichs-Ansicht

Im Board: Mehrere Bewerber anklicken (max. 3), dann **[Vergleichen]**:

```
┌────────────────┬────────────────┬────────────────┐
│  Max M.        │  Anna K.       │  Peter L.      │
├────────────────┼────────────────┼────────────────┤
│ Fachlich: ★★★★ │ Fachlich: ★★★★★│ Fachlich: ★★★  │
│ Soft:     ★★★  │ Soft:     ★★★★ │ Soft:     ★★★★★│
│ Gesamt:   ★★★  │ Gesamt:   ★★★★ │ Gesamt:   ★★★★ │
├────────────────┼────────────────┼────────────────┤
│ PHP: ✅        │ PHP: ✅        │ PHP: ✅        │
│ Docker: ✅     │ Docker: ❌     │ Docker: ✅     │
│ EN B2: ✅      │ EN C1: ✅      │ EN B1: ⚠️      │
├────────────────┼────────────────┼────────────────┤
│ [Einladen]     │ [Einladen]     │ [Absagen]      │
└────────────────┴────────────────┴────────────────┘
```

### 2.3 Team-Kommentar-System

Mehrere Recruiter / Hiring-Manager können auf eine Bewerbung kommentieren:

```sql
CREATE TABLE {prefix}jobads_application_comments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    content         TEXT NOT NULL,
    parent_id       INT UNSIGNED DEFAULT NULL,
    is_internal     TINYINT(1) DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES {prefix}jobads_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.4 Konfigurierbarer Pipeline-Status pro Firma

Firmen können die Standard-Pipeline-Stufen umbenennen oder eigene ergänzen:

```sql
CREATE TABLE {prefix}jobads_pipeline_stages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED DEFAULT NULL,   -- NULL = globaler Standard
    stage_key     VARCHAR(60) NOT NULL,
    label         VARCHAR(100) NOT NULL,
    color         VARCHAR(7) DEFAULT '#888888',
    send_email    TINYINT(1) DEFAULT 0,
    email_template_id INT UNSIGNED DEFAULT NULL,
    sort_order    SMALLINT UNSIGNED DEFAULT 0,
    is_terminal   TINYINT(1) DEFAULT 0,        -- Endstufe (Besetzt/Absage)
    UNIQUE KEY uq_stage (company_id, stage_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. Kanal-Erweiterungen (StepStone, LinkedIn, XING)

### 3.1 StepStone XML-Feed

StepStone akzeptiert XML nach eigenem Schema. Ähnlich wie Indeed, aber mit  
zusätzlichen Feldern für Gehalt-Bandbreite und Hierarchiestufe.

```php
// class-feed-stepstone.php
class CMS_JobAds_Feed_StepStone {

    public function generate(array $job_ads): string {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><joblist/>');
        foreach ($job_ads as $ad) {
            $job = $xml->addChild('job');
            $job->addChild('jobid',    htmlspecialchars($ad->ref_number));
            $job->addChild('title',    htmlspecialchars($ad->title));
            $job->addChild('company',  htmlspecialchars($ad->company_name));
            // ... weiteres Mapping
        }
        return $xml->asXML();
    }
}
```

### 3.2 LinkedIn Jobs-Posting

LinkedIn bietet eine Job-Posting-API. Anbindung erfolgt über OAuth 2.0.

**Voraussetzungen für LinkedIn-Integration:**
- LinkedIn-App-Credentials (Client-ID + Client-Secret) im Plugin-Einstellungen hinterlegen
- Firma muss LinkedIn Company-Page-ID eintragen

```sql
CREATE TABLE {prefix}jobads_channel_credentials (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED DEFAULT NULL,
    channel      VARCHAR(60) NOT NULL,
    credential_key   VARCHAR(100) NOT NULL,
    credential_value TEXT NOT NULL,    -- verschlüsselt gespeichert (CMS-Encryption)
    expires_at   DATETIME DEFAULT NULL,
    UNIQUE KEY uq_cred (company_id, channel, credential_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Posting-Ablauf:**
1. Bei Veröffentlichung mit Kanal `linkedin` gewählt
2. Plugin prüft OAuth-Token (Refresh wenn nötig)
3. API-Call: `POST /v2/jobPostings` mit Job-Daten
4. Rückgabe: `linkedinJobId` → gespeichert in `{prefix}jobads_publications.channel_ref_id`
5. Bei Ablauf der Anzeige: `DELETE /v2/jobPostings/{id}` (Auto-Rücknahme)

### 3.3 XING Jobs-Posting

Analoges Vorgehen zu LinkedIn, aber XING-API-Endpoints.  
XING erfordert zusätzlich: `company_xing_id` und `employer_xing_profile_url`.

### 3.4 Budget-Tracking für Paid-Portale

```sql
CREATE TABLE {prefix}jobads_channel_budgets (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publication_id INT UNSIGNED NOT NULL,
    channel        VARCHAR(60) NOT NULL,
    planned_cost   DECIMAL(8,2) DEFAULT NULL,
    actual_cost    DECIMAL(8,2) DEFAULT NULL,
    invoice_ref    VARCHAR(100) DEFAULT NULL,
    paid_at        DATE DEFAULT NULL,
    FOREIGN KEY (publication_id) REFERENCES {prefix}jobads_publications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Profil-Versionierung

### 4.1 Warum Versionierung?

Wenn ein Firmen-Admin ein Master-Profil ändert und `forced`-Items propagiert werden,  
muss nachvollziehbar bleiben:
- Welcher Wert war wann gültig?
- Wer hat was geändert?
- Auf welchen Stand kann zurückgekehrt werden?

### 4.2 Versions-Tabelle

```sql
CREATE TABLE {prefix}jobads_profile_versions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_type  ENUM('skill','benefit','condition') NOT NULL,
    profile_id    INT UNSIGNED NOT NULL,
    version_nr    SMALLINT UNSIGNED NOT NULL,
    snapshot_json JSON NOT NULL,          -- vollständiger Profil-Zustand als JSON
    change_summary VARCHAR(500) DEFAULT NULL,
    created_by    INT UNSIGNED NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_profile (profile_type, profile_id, version_nr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3 Version erstellen (automatisch beim Speichern)

```php
// In class-benefit-profiles.php – Methode update()
public function update(int $id, array $data): bool {
    // 1. Aktuelle Version sichern
    $current = $this->get_full_snapshot($id);
    CMS_JobAds_Profile_Versions::save_snapshot('benefit', $id, $current);

    // 2. Update durchführen
    // ...

    // 3. Propagierung bei forced-Items auslösen
    // ...

    return true;
}
```

### 4.4 Versions-Interface im Admin

Im Profil-Editor: Reiter **„Versions-Verlauf"**

```
Version 5  (aktuell)  – 19.02.2026 14:30 – Max Muster
  "Homeoffice von 3 auf 4 Tage erhöht"
  [Vergleich mit v4]

Version 4  – 12.01.2026 09:15 – Anna K.
  "Weiterbildungsbudget auf 2.000 € erhöht"
  [Vergleich mit v3]  [Auf diese Version zurücksetzen]

Version 3  – 01.11.2025 11:00 – (Import)
  ...
```

**Zurücksetzen:**
- Lädt `snapshot_json` der gewählten Version
- Ersetzt alle `profile_items` durch Snapshot-Daten
- Erstellt neue Version mit Hinweis „Rollback von v4 auf v3"
- Propagiert Änderungen entsprechend (nur `forced`-Items sofort)

---

## 5. Profil-Import / Export

### 5.1 Export

Jedes Profil (Skill, Benefit, Kondition) kann als JSON exportiert werden.

**Export-Format:**

```json
{
  "cms_jobads_export": "1.0",
  "profile_type": "benefit",
  "exported_at": "2026-02-19T14:30:00Z",
  "exported_by": "max.muster@firma.de",
  "profile": {
    "name": "IT-Startup-Paket",
    "slug": "it_startup_paket",
    "branch_key": "it_technology",
    "description": "Standardpaket für IT-Startups",
    "items": [
      {
        "benefit_slug": "homeoffice",
        "inherit_mode": "default",
        "value": { "days_per_week": 4 }
      },
      {
        "benefit_slug": "weiterbildungsbudget",
        "inherit_mode": "default",
        "value": { "amount": 2000, "currency": "EUR", "period": "year" }
      }
    ]
  }
}
```

### 5.2 Import

Import über Datei-Upload (JSON) im Admin.  
**Validierungs-Schritte:**
1. JSON-Schema-Validierung gegen `cms_jobads_export`-Format
2. Prüfung: Alle referenzierten `benefit_slug`-Werte im Katalog vorhanden?
3. Falls nicht: Option „Fehlende Benefits anlegen" oder „Überspringen"
4. Kollisions-Check: Profil mit gleichem Slug bereits vorhanden?
   - Option A: Überschreiben (creates new version)
   - Option B: Als neue Version importieren
   - Option C: Unter neuem Namen anlegen

### 5.3 Profil-Marktplatz (Interne Agentur-Bibliothek)

Agenturen können Profile innerhalb ihrer Kunden-Firmen teilen:

```
Agentur hat Profile angelegt:
  - "Standard IT-Dev Agentur-Bundle" (benefit)
  - "Senior Frontend Requirements" (skill)

Firma A kann diese Profile beim Zuweisen auswählen:
  [Eigene Profile (3)] [Agentur-Bibliothek (12)] [System-Profile (7)]
```

---

## 6. Profil-Duplikation mit Kontextanpassung

### 6.1 Duplikations-Assistent

Beim Duplizieren eines Profils kann der Kontext direkt angepasst werden:

```
PROFIL DUPLIZIEREN
──────────────────
Quell-Profil: "Backend-Dev Senior" (Skill-Profil, IT)

Neue Profil-Basis:
  Name:    [Backend-Dev Lead_______]
  Branche: [IT & Technologie ▾]
  Ziel:    ○ System-Profil  ● Firmen-Profil  ○ Abteilungs-Profil
  Firma:   [Firma GmbH ▾]

Unterschiede zum Original vorschlagen:
  ☑ Seniority von "senior" auf "lead" angepasst
  ☑ Requirement "5+ Jahre" → "8+ Jahre" (automatisch erkannt)
  ☐ Weitere Anpassungen jetzt vornehmen  → [Editor öffnen]

[Duplizieren & Bearbeiten]  [Nur duplizieren]
```

---

## 7. Delegations-System (vollständig)

Phase 2 hat Delegation konzeptionell definiert. Phase 4 implementiert es vollständig.

### 7.1 Delegations-Tabelle

```sql
CREATE TABLE {prefix}jobads_delegations (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delegator_user_id  INT UNSIGNED NOT NULL,
    delegate_user_id   INT UNSIGNED NOT NULL,
    entity_type        ENUM('company','department') NOT NULL,
    entity_id          INT UNSIGNED NOT NULL,
    scope              ENUM('full','create_only','specific_ad') DEFAULT 'create_only',
    specific_ad_id     INT UNSIGNED DEFAULT NULL,
    valid_from         DATE NOT NULL,
    valid_until        DATE DEFAULT NULL,    -- NULL = unbegrenzt (selten)
    is_active          TINYINT(1) DEFAULT 1,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at         DATETIME DEFAULT NULL,
    revoked_by         INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (delegator_user_id) REFERENCES {prefix}cms_users(id),
    FOREIGN KEY (delegate_user_id)  REFERENCES {prefix}cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.2 Delegation erstellen (UI)

Im Abteilungs-Bereich des Abteilungsleiters:

```
DELEGATION ERSTELLEN
────────────────────
Bevollmächtigter:  [Suche User...   ▾]
Gültig ab:         [19.02.2026]
Gültig bis:        [14.03.2026]  ← Standard: 30 Tage
Umfang:
  ○ Alle Aktionen dieser Abteilung
  ● Nur: Stellenanzeigen erstellen und bearbeiten
  ○ Nur für Anzeige: [Anzeige wählen...]
Benachrichtigung:  ☑ E-Mail an Bevollmächtigten senden

[Delegation erteilen]
```

### 7.3 Delegations-Prüfung im Berechtigungs-System

```php
// class-permissions.php
public function user_can(int $user_id, string $action, array $context = []): bool {
    // Prüf-Reihenfolge:
    // 1. Hat User direkte Rolle die diese Action erlaubt?
    if ($this->role_allows($user_id, $action, $context)) {
        return true;
    }

    // 2. Hat User eine aktive Delegation die diese Action abdeckt?
    $delegations = $this->get_active_delegations($user_id);
    foreach ($delegations as $del) {
        if ($this->delegation_covers($del, $action, $context)) {
            $this->log_delegation_use($del->id, $action, $context);
            return true;
        }
    }

    return false;
}
```

### 7.4 Delegations-Protokoll

Jede Nutzung einer delegierten Berechtigung wird in `{prefix}jobads_audit_log`  
mit `action = 'delegation_used'` und Referenz auf `delegation_id` gespeichert.

### 7.5 Automatischer Ablauf

Cron (täglich): Delegations mit `valid_until < CURDATE()` werden auf `is_active = 0` gesetzt  
und eine Benachrichtigung an Delegator gesendet: "Delegation für X ist abgelaufen."

---

## 8. Analytics-Dashboard

### 8.1 Tracking-Tabelle

```sql
CREATE TABLE {prefix}jobads_tracking (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id   INT UNSIGNED NOT NULL,
    event_type  ENUM('view','apply_click','apply_submit','bookmark') NOT NULL,
    channel     VARCHAR(60) DEFAULT 'direct',   -- woher kommt der Besuch
    session_id  VARCHAR(64) DEFAULT NULL,        -- pseudonymisiert
    referrer    VARCHAR(500) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ad_event (job_ad_id, event_type, created_at),
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Datenschutz:** IP-Adresse wird NICHT gespeichert. Session-ID ist gehashte,  
> kurzlebige Pseudonymisierung. Nach 90 Tagen automatisch gelöscht.

### 8.2 Stellenanzeigen-Metriken

| Metrik | Berechnung | Anzeige |
|---|---|---|
| Views | COUNT(event_type='view') | Zahl + Tagesgrafik (14 Tage) |
| Unique Views (ca.) | COUNT(DISTINCT session_id WHERE type='view') | Zahl |
| Apply-CTR | apply_clicks / views × 100 | Prozent |
| Conversion | apply_submits / views × 100 | Prozent |
| Bewerbungen gesamt | COUNT(applications) | Zahl |
| Ø Bewerbungen/Tag | / Laufzeit in Tagen | Zahl |
| Top-Kanal | Kanal mit meisten Bewerbungen | Text + Balken |
| Time-to-Fill | filled_at - published_at | Tage |

### 8.3 Dashboard-Widgets

**Übersichts-Dashboard für Firmen-Admin:**

```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Aktive       │ Bewerbungen  │ Ø Besetzungs │ Offene       │
│ Anzeigen: 8  │ diese Woche  │ dauer: 23d   │ Freigaben: 2 │
│ ↑2 vs VW    │ ████ 14      │ ↓3d vs Q3   │ ⚠️ seit >3d  │
└──────────────┴──────────────┴──────────────┴──────────────┘

ANZEIGEN-PERFORMANCE (letzte 30 Tage)
Stelle              Views  CTR   Bewerb.  Top-Kanal
─────────────────────────────────────────────────────
Senior Backend Dev  2.341  8.2%    192    Indeed
Junior Dev          1.120  6.1%     68    Google Jobs
DevOps Engineer       860  4.3%     37    Direct
...
```

### 8.4 Agentur-Reporting

Für Agentur-Admins: Report durch Mandant/Kunde filtern und als PDF-Export:

```
Kunden-Report Q1 2026
Firma: Muster GmbH
─────────────────────────────────────────────
Anzeigen veröffentlicht:    12
Bewerbungen eingegangen:   143
Stellen besetzt:             4
Ø Besetzungsdauer:          18 Tage
Ø Bewerbungen pro Stelle:   11.9
─────────────────────────────────────────────
Kanal-Performance:
  Indeed:     63 Bewerb. (44%)
  Google:     41 Bewerb. (29%)
  LinkedIn:   22 Bewerb. (15%)
  Direkt:     17 Bewerb. (12%)
```

---

## 9. DSGVO-Vollautomatik

### 9.1 Datenlösch-Management

**Erweitert gegenüber Phase 3:**

```sql
CREATE TABLE {prefix}jobads_gdpr_schedules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type     ENUM('application','job_ad') NOT NULL,
    entity_id       INT UNSIGNED NOT NULL,
    delete_at       DATE NOT NULL,
    reason          VARCHAR(255) DEFAULT NULL,
    notified_at     DATE DEFAULT NULL,   -- 14-Tage-Vor-Benachrichtigung
    preserved_by    INT UNSIGNED DEFAULT NULL,   -- Wer hat die Frist verlängert
    preserved_until DATE DEFAULT NULL,
    deleted_at      DATETIME DEFAULT NULL,
    UNIQUE KEY uq_schedule (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**14-Tage-Vor-Warnung:**  
Cron täglich: Prüft `delete_at = TODAY + 14`. Sendet E-Mail an Firmen-Admin:  
„14 Bewerbungen werden in 14 Tagen automatisch gelöscht. [Liste anzeigen] [Fristen verlängern]"

**Anonymisierung statt Löschung (opt-in):**  
Statt hartem Löschen: personenbezogene Felder (Name, E-Mail, Telefon) durch  
Platzhalter ersetzen, Bewertungen und Noten bleiben für Statistiken.

### 9.2 Einwilligungs-Verwaltung

```sql
CREATE TABLE {prefix}jobads_consent_log (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    consent_type   ENUM('processing','newsletter','extended_storage') NOT NULL,
    consented      TINYINT(1) NOT NULL,
    ip_hash        VARCHAR(64) DEFAULT NULL,   -- gehashte IP
    user_agent     VARCHAR(200) DEFAULT NULL,
    consented_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES {prefix}jobads_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 10. Absage-Management

### 10.1 Absage-Vorlagen

```sql
CREATE TABLE {prefix}jobads_rejection_templates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED DEFAULT NULL,
    name         VARCHAR(255) NOT NULL,
    stage_key    VARCHAR(60) DEFAULT NULL,   -- Vorlage für spezifische Pipeline-Stufe
    subject      VARCHAR(255) NOT NULL,
    body_html    TEXT NOT NULL,
    body_text    TEXT NOT NULL,
    is_default   TINYINT(1) DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Vorkonfigurierte Vorlagen:**
- „Nach Eingangs-Sichtung (Standardformulierung)"
- „Nach Telefoninterview"
- „Nach Gespräch – Kulturfit nicht passend"
- „Nach Angebot – Stelle anderweitig besetzt"
- „Stelle auf Eis gelegt"

### 10.2 Massen-Absage

Für alle nicht ausgewählten Bewerber nach Stelle-Besetzung:

```
[Stelle als besetzt markieren]

→ Dialog erscheint:
  "18 Bewerber haben noch keine Absage erhalten."
  Vorlage: [Nach Besetzung – Standard ▾]
  ☑ Absagen jetzt versenden
  ☑ Mich über alle versendeten Absagen informieren

[Bestätigen & Absagen senden]
```

---

## 11. Interview-Terminplanung (intern)

### 11.1 Interview-Termine

```sql
CREATE TABLE {prefix}jobads_interviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    job_ad_id       INT UNSIGNED NOT NULL,
    type            ENUM('telefon','video','vor_ort','assessment') DEFAULT 'telefon',
    scheduled_at    DATETIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED DEFAULT 60,
    location        VARCHAR(255) DEFAULT NULL,
    video_link      VARCHAR(500) DEFAULT NULL,
    interviewer_ids_json JSON DEFAULT NULL,
    status          ENUM('geplant','durchgeführt','abgesagt','no_show') DEFAULT 'geplant',
    notes           TEXT DEFAULT NULL,
    created_by      INT UNSIGNED NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES {prefix}jobads_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 11.2 Einladungs-E-Mail

Beim Anlegen eines Termins wird automatisch eine Einladungs-E-Mail  
mit den Termin-Details an den Bewerber gesendet. Optional: `.ics`-Kalender-Datei  
als E-Mail-Anhang (iCalendar-Format).

---

## 12. Mehrsprachige Anzeigen (DE/EN)

### 12.1 Übersetzungs-Tabelle

```sql
CREATE TABLE {prefix}jobads_ad_translations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_ad_id   INT UNSIGNED NOT NULL,
    locale      CHAR(5) NOT NULL,   -- 'de_DE', 'en_EN', etc.
    title       VARCHAR(255) DEFAULT NULL,
    teaser      TEXT DEFAULT NULL,
    about_company TEXT DEFAULT NULL,
    tasks       TEXT DEFAULT NULL,
    req_must    TEXT DEFAULT NULL,
    req_nice    TEXT DEFAULT NULL,
    apply_info  TEXT DEFAULT NULL,
    is_complete TINYINT(1) DEFAULT 0,   -- Alle Pflichtfelder gefüllt?
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_translation (job_ad_id, locale),
    FOREIGN KEY (job_ad_id) REFERENCES {prefix}jobads_job_ads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 12.2 Paralleler Editor

Im Anzeigen-Editor: Toggle [DE] / [EN]  
Nicht übersetzte Abschnitte: gelb markiert mit „Noch nicht übersetzt".  
Frontend: Sprachumschalter auf Detailseite; fallback auf DE wenn EN nicht vollständig.

---

## 13. Externes Website-Widget

### 13.1 Embed-Code-Generator

Im Admin unter **Einstellungen → Widget**:

```html
<!-- CMS Jobads Widget – einbinden auf beliebiger Website -->
<div id="cms-jobads-widget"
     data-company="firma-slug"
     data-limit="10"
     data-filter-branch="it_technology"
     data-theme="light"
     data-lang="de">
</div>
<script src="https://cms.domain.de/jobs/widget.js" async></script>
```

### 13.2 Widget-Verhalten

- Lädt Stellen via JSONP oder CORS-gesichertem Endpoint
- Responsive (mobile-first)
- Design-Varianten: `light`, `dark`, `minimal`
- Optionaler Filter nach Branche oder Abteilung
- Anklicken öffnet Detailseite auf dem CMS oder im Modal (konfigurierbar)
- Kein Tracking-Cookie, DSGVO-konform

---

## 14. REST-API-Feed (intern)

### 14.1 Endpunkte

```
GET  /api/v1/jobads/ads
GET  /api/v1/jobads/ads/{id}
GET  /api/v1/jobads/companies/{slug}/ads
GET  /api/v1/jobads/departments/{id}/ads
POST /api/v1/jobads/ads/{id}/apply
```

### 14.2 Authentifizierung

- Öffentliche Endpoints (GET): API-Key in Header `X-JobAds-Key`
- Apply-Endpoint: zusätzlich CSRF-Token oder reCAPTCHA-Lösung

### 14.3 Response-Format

```json
{
  "data": [
    {
      "id": 42,
      "title": "Senior Backend Developer (m/w/d)",
      "slug": "senior-backend-developer",
      "company": { "name": "Firma GmbH", "logo": "https://..." },
      "department": "Produktentwicklung",
      "location": "Hamburg",
      "remote_type": "hybrid",
      "employment_degree": "vollzeit",
      "salary_range": { "from": 65000, "to": 85000, "currency": "EUR", "period": "year" },
      "published_at": "2026-02-15T09:00:00Z",
      "expires_at": "2026-03-15T23:59:00Z",
      "apply_url": "https://cms.domain.de/jobs/senior-backend-developer/apply/",
      "detail_url": "https://cms.domain.de/jobs/senior-backend-developer/"
    }
  ],
  "meta": {
    "total": 24,
    "page": 1,
    "per_page": 10
  }
}
```

---

## 15. Datenbank-Erweiterungen Phase 4

**Neue Tabellen:**

```
{prefix}jobads_application_ratings      ← Mehrdimensionale Bewertungen
{prefix}jobads_application_comments     ← Team-Kommentare
{prefix}jobads_pipeline_stages          ← Konfigurierbare Pipeline
{prefix}jobads_channel_credentials      ← API-Keys (LinkedIn, XING)
{prefix}jobads_channel_budgets          ← Kosten-Tracking
{prefix}jobads_profile_versions         ← Profil-Versionierung
{prefix}jobads_delegations              ← Vollständiges Delegations-System
{prefix}jobads_tracking                 ← Anonymes View/Click-Tracking
{prefix}jobads_gdpr_schedules           ← Erweitertes Lösch-Management
{prefix}jobads_consent_log              ← Einwilligungs-Protokoll
{prefix}jobads_rejection_templates      ← Absage-Vorlagen
{prefix}jobads_interviews               ← Interview-Termine
{prefix}jobads_ad_translations          ← DE/EN Übersetzungen
```

**Gesamt Phase 1–4: 43 Tabellen**

---

## 16. Klassen & Hooks Phase 4

**Neue Klassen:**

```
includes/
├── class-kanban.php                ← Kanban-Board-Logik
├── class-application-ratings.php  ← Bewertungs-System
├── class-pipeline.php              ← Pipeline-Konfiguration
├── class-feed-stepstone.php        ← StepStone XML
├── class-feed-linkedin.php         ← LinkedIn API
├── class-feed-xing.php             ← XING API
├── class-profile-versions.php     ← Versionierung + Rollback
├── class-profile-io.php            ← Import / Export
├── class-delegations.php           ← Delegations-Engine
├── class-analytics.php             ← Tracking + Auswertung
├── class-gdpr-manager.php         ← Vollautomatisches DSGVO
├── class-rejection-manager.php    ← Absagen + Massen-Absage
├── class-interviews.php            ← Interview-Planung
├── class-translations.php         ← Mehrsprachigkeit
├── class-widget.php                ← Embed-Widget
└── class-rest-api.php              ← REST-Endpunkte
```

**Neue Hooks Phase 4:**

```php
// Actions
do_action('jobads_application_rated',       $application_id, $user_id, $ratings);
do_action('jobads_interview_scheduled',     $interview_id, $application_id);
do_action('jobads_rejection_sent',          $application_id, $template_id);
do_action('jobads_bulk_rejected',           $job_ad_id, $count);
do_action('jobads_profile_versioned',       $type, $profile_id, $version_nr);
do_action('jobads_delegation_created',      $delegation_id);
do_action('jobads_delegation_revoked',      $delegation_id, $revoker_id);
do_action('jobads_delegation_expired',      $delegation_id);
do_action('jobads_gdpr_deleted',            $entity_type, $entity_id);
do_action('jobads_gdpr_anonymized',         $entity_type, $entity_id);
do_action('jobads_channel_published',       $job_ad_id, $channel, $channel_ref);

// Filters
$stages  = apply_filters('jobads_pipeline_stages',       $stages, $company_id);
$report  = apply_filters('jobads_analytics_report',      $report, $company_id, $period);
$fields  = apply_filters('jobads_rest_api_ad_fields',    $fields, $job_ad);
$expires = apply_filters('jobads_gdpr_default_retention',$days, $entity_type);
```

---

## 17. Abnahme-Kriterien Phase 4

- [ ] Kanban-Board: alle Pipeline-Stufen sichtbar, Drag & Drop funktioniert
- [ ] Bewerber-Karte: Bewertung, Tags, Notizspeicherung
- [ ] Team-Kommentare auf Bewerbung: mehrere User, Threading
- [ ] Bewerber-Vergleich: 2–3 Bewerber nebeneinander korrekt angezeigt
- [ ] StepStone XML-Feed: generiert und strukturell valide
- [ ] LinkedIn: API-Credentials hinterlegbar, Test-Post möglich
- [ ] XING: API-Credentials hinterlegbar, Test-Post möglich
- [ ] Profil-Versionierung: jede Speicherung erstellt neue Version
- [ ] Rollback auf ältere Version: Daten korrekt wiederhergestellt, Propagierung ausgelöst
- [ ] Profil-Export: valides JSON, alle Items enthalten
- [ ] Profil-Import: Import aus Datei, Kollisions-Dialog funktioniert
- [ ] Delegation erstellen (Abteilungsleiter → User), zeitbegrenzt
- [ ] Delegierter kann Anzeige erstellen, kann NICHT freigeben
- [ ] Delegation nach `valid_until` automatisch deaktiviert (Cron-Test)
- [ ] Delegations-Nutzung im Audit-Log protokolliert
- [ ] Analytics: View-Events werden bei Seiten-Aufruf gespeichert
- [ ] Apply-Click-Events werden bei Button-Klick gespeichert
- [ ] Dashboard zeigt korrekte Metriken (Views, CTR, Conversion)
- [ ] Kanal-Attribution: Bewerber von Indeed vs. Direct unterscheidbar
- [ ] DSGVO 14-Tage-Warnung: E-Mail wird gesendet (Cron-Test)
- [ ] DSGVO Anonymisierung: personenbez. Felder nach Prozedur geleert
- [ ] Absage-Vorlagen: 5 Systemvorlagen vorhanden, eigene erstellbar
- [ ] Massen-Absage: versendet E-Mails an alle nicht-besetzten Bewerber
- [ ] Interview anlegen: E-Mail an Bewerber mit .ics-Anhang
- [ ] REST-API GET /ads: gibt korrekte JSON-Response zurück
- [ ] Widget-Embed-Code: aufgerufen auf externer Test-Seite funktionsfähig
- [ ] Mehrsprachige Anzeige: DE und EN parallel pflegbar
- [ ] Frontend: Sprach-Toggle auf Detailseite

---

**→ Zurück zu:** [PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md](PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md)  
**→ Weiter mit:** [PHASE-5-VOLLSTAENDIG.md](PHASE-5-VOLLSTAENDIG.md)

*Stand: 19. Februar 2026 · cms-jobads Phase 4/5*
