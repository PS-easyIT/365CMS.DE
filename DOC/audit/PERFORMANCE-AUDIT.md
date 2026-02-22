# PERFORMANCE AUDIT 2026 – 365CMS (`/CMS`)

## 1) Prüfrahmen (Best Practice 2026)

**Technologie-Basis:** PHP 8.3+, MySQL 8 / MariaDB 10.6+, HTTP/2/3, TLS-only Deployment.  
**Audit-Fokus:** Core-Laufzeit, Datenbankzugriffe, Caching, Admin-I/O, Skalierungsrisiken.  
**Prüfmethode:** statische Code-Analyse der produktiven Pfade in `CMS/` (kein künstlicher Benchmark-Lauf im Repo vorhanden).

Geprüfte Stichproben:
- `CMS/core/Database.php`
- `CMS/core/CacheManager.php`
- `CMS/core/Bootstrap.php`
- `CMS/admin/performance.php`
- `CMS/core/Services/SystemService.php`

---

## 2) Executive Summary

**Gesamtstatus:** 🟡 **Gut für kleine bis mittlere Lasten, mit klaren Skalierungshebeln.**

Stärken:
- Solider PDO-Layer mit Prepared Statements als Standard.
- Mehrstufiges Caching vorhanden (Datei, OPcache, APCu, LiteSpeed-Purge).
- Umfangreiche Indexierung im Schema-Bootstrap.

Haupthebel:
- Query-/Settings-Batching im Admin.
- Robustere Cache-Serialisierung und Key-Strategie.
- Entkopplung von Bootstrapping-Side-Effects.

---

## 3) Positive Befunde

1. **DB-Index-Basis ist breit angelegt** in `CMS/core/Database.php` (viele `idx_*`-Indizes in Kern-Tabellen).  
2. **Ablaufsteuerung über Flag-Datei** (`cache/db_schema_v3.flag`) reduziert schema-intensiven Start-Overhead.  
3. **Performance-Operations im Admin** vorhanden (`CMS/admin/performance.php`: Cache leeren, Feature-Schalter).  
4. **Systemmetriken** sind in Services/Dashboard bereits angelegt (u. a. Speicherwerte).

---

## 4) Findings (nach Priorität)

### P1-HIGH: Emulated Prepares global aktiv
- Evidenz: `CMS/core/Database.php` (`PDO::ATTR_EMULATE_PREPARES => true`).
- Wirkung: Für LIMIT/OFFSET pragmatisch, aber global kann dies Native-Prepare-Vorteile reduzieren.
- 2026-Empfehlung:
  - Native Prepares als Default.
  - Emulation nur gezielt für bekannte Sonderabfragen kapseln.

### P1-HIGH: Cache-Storage nutzt `serialize()/unserialize()`
- Evidenz: `CMS/core/CacheManager.php`.
- Wirkung: Für Performance funktional, aber schwerer auditierbar/portabler als JSON und anfälliger für Objektpayload-Risiken.
- 2026-Empfehlung:
  - JSON-first für skalare/array-basierte Werte.
  - Falls notwendig: harte Typ-Verträge und sichere Deserialisierung.

### P1-MEDIUM: N+1-artige Settings-Operationen auf Admin-Performance-Seite
- Evidenz: `CMS/admin/performance.php` (Settings read/write pro Key in Schleife).
- Wirkung: Mehr DB-Roundtrips als nötig.
- 2026-Empfehlung:
  - Bulk-Read via `IN (...)` und Upsert-Batching.

### P2-MEDIUM: Bootstrap hat spürbare Start-Side-Effects
- Evidenz: `CMS/core/Bootstrap.php` lädt Plugins/Themes früh und ruft Core-Initialisierungen zentral auf.
- Wirkung: Weniger flexible Startprofile (Web/Admin/Worker/CLI), höheres Cold-Start-Risiko.
- 2026-Empfehlung:
  - Startmodi definieren und lazy initialisieren.

### P2-LOW: Inline-Styles auf Admin-Seiten
- Evidenz: u. a. `CMS/admin/index.php`, `CMS/admin/performance.php`.
- Wirkung: Wartbarkeit/Cachebarkeit der Styles reduziert.
- 2026-Empfehlung:
  - CSS-Klassen zentral in `admin.css`, weniger Inline-Renderkosten.

---

## 5) DB-Tuning (MySQL/MariaDB Best Practice 2026)

1. **Engine/Charset/Collation explizit vereinheitlichen** (utf8mb4 + definierte Collation je Umgebung).  
2. **EXPLAIN-basierte Query-Audits** für häufige Admin- und Dashboard-Abfragen.  
3. **Composite-Index Review** für typische Filterkombinationen (Status+Datum, User+Zeit etc.).  
4. **TTL-lastige Tabellen** (`cache`, `sessions`) mit Maintenance-Job + Telemetrie (Rows pruned/min).

---

## 6) KPI-Zielbild 2026 (empfohlen)

- P95 Admin-Seitenaufbau: **< 500 ms** (ohne externe APIs)
- DB-Queries je Standard-Adminseite: **< 30**
- Cache-Hit-Rate (Core-Read-Pfade): **> 80%**
- P95 Cache-Clear Operation: **< 2 s**

---

## 7) Maßnahmenplan

### 0–30 Tage
- Emulated prepare nur dort aktivieren, wo zwingend nötig.
- Bulk-Settings-Read/Write für Performance-Settings.
- Cache-Hit/Miss-Zähler technisch erfassen.

### 31–90 Tage
- Startprofile im Bootstrap (admin/frontend/cli) trennen.
- Query-Observability (Langläufer, Top-N Abfragen) ergänzen.

### >90 Tage
- Optionales externes Cache-Backend (Redis/Memcached) abstrahieren.
- Performance-Budget als Release-Gate definieren.

---

## 8) Abschlussbewertung

**Performance-Reifegrad:** **B**  
**Urteil:** Sehr brauchbare Basis, aber mit messbarem Nutzen durch gezielte 2026-Hardening-Schritte bei DB-Strategie, Bootprofilen und Cache-Transparenz.
