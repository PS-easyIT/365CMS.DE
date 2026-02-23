# 365CMS – Manueller Test: Performance & Caching

> **Scope:** File-Cache, APCu-Cache, HTTP-Cache-Header, Antwortzeiten  
> **Werkzeuge:** curl, Browser-DevTools (Network), Apache Bench (optional)  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. Vorbereitung

| Aufgabe | Erledigt |
|---|---|
| `CMS_DEBUG=false` in `config.php` (Production-Mode) | ☐ |
| APCu aktiviert: `php -r "echo apcu_enabled() ? 'ja' : 'nein';"` | ☐ |
| Cache-Verzeichnis beschreibbar: `CMS/cache/` | ☐ |
| curl oder Browser-DevTools bereit | ☐ |

---

## 2. Cache-Status

### TC-PERF-01 · Cache-Status-Seite (Admin)

**Vorgehen:**
1. Admin → System → Cache-Status aufrufen
2. CacheManager::getStatus() Ausgabe prüfen

**Erwartetes Ergebnis:**
- Anzeige: Aktiver Treiber (APCu oder File)
- APCu-Info (falls aktiv): Memory-Nutzung, Hit-Rate

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Status-Seite lädt ohne Fehler | ☐ | ☐ |
| Treiber korrekt angezeigt | ☐ | ☐ |

---

## 3. HTTP-Cache-Header

### TC-PERF-02 🟠 · Statische Assets haben Cache-Header

**Prüfung:**
```bash
curl -I https://your-test-instance/assets/css/main.css
```

**Erwartetes Ergebnis:**
```
Cache-Control: public, max-age=31536000
ETag: "..."
```

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| `Cache-Control` vorhanden | ☐ | ☐ |
| `ETag` oder `Last-Modified` vorhanden | ☐ | ☐ |

---

### TC-PERF-03 · 304 Not Modified für gecachte Ressourcen

**Vorgehen:**
```bash
# Erstes Request – ETag notieren
curl -I https://your-test-instance/assets/css/main.css

# Second Request mit If-None-Match
curl -I https://your-test-instance/assets/css/main.css \
  -H "If-None-Match: \"ETAG_AUS_ERSTEM_REQUEST\""
```

**Erwartetes Ergebnis:**
- HTTP 304 beim zweiten Request
- Kein Body übertragen

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| HTTP 304 korrekt | ☐ | ☐ |

---

### TC-PERF-04 🟠 · Member-/Admin-Seiten nicht gecacht

**Prüfung:**
```bash
curl -I https://your-test-instance/member/dashboard \
  -b "PHPSESSID=valid_session"
```

**Erwartetes Ergebnis:**
```
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
```

- Keine `Expires`-Zukunftsdaten
- Kein `public` Cache-Control

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| `no-store` Header vorhanden | ☐ | ☐ |
| Kein `public` Cache-Control | ☐ | ☐ |

---

## 4. CacheManager – Funktionstest

### TC-PERF-05 · Cache schreiben und lesen (Admin-Debug)

**Vorgehen:**
1. Admin → System → Cache-Diagnose (falls vorhanden)
2. Oder: Temporären Debug-Endpunkt aufrufen, der Cache-Hit/Miss loggt

**Erwartetes Ergebnis:**
- Erster Aufruf: Cache MISS (Daten aus DB geladen)
- Zweiter Aufruf: Cache HIT (Daten aus Cache)
- Antwortzeit beim HIT deutlich kürzer

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Cache-Miss beim ersten Aufruf | ☐ | ☐ |
| Cache-Hit beim zweiten Aufruf | ☐ | ☐ |

---

### TC-PERF-06 · Cache-Flush funktioniert

**Vorgehen:**
1. Admin → System → Cache leeren (aller oder spezifischer Cache)
2. Seite neu aufrufen

**Erwartetes Ergebnis:**
- Kein Fehler beim Leeren
- Nächster Seitenaufruf lädt Daten aus DB (Cache MISS)
- Audit-Log oder System-Log-Eintrag

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Cache-Flush ohne Fehler | ☐ | ☐ |
| Seite noch erreichbar danach | ☐ | ☐ |

---

## 5. Antwortzeiten (Baseline)

### TC-PERF-07 · Startseite – Antwortzeit < 300 ms

**Prüfung:**
```bash
# 10 Requests, 2 parallele Verbindungen
ab -n 10 -c 2 https://your-test-instance/
```
Oder via Browser-DevTools → Network → „DOMContentLoaded" Zeit.

**Zielwert:** ≤ 300 ms (TTFB), ≤ 1000 ms (vollständig geladen)

| Messung | Zeit (ms) | Bestanden |
|---|---|---|
| TTFB (first byte) | | ☐ (≤ 300 ms) |
| Vollständig geladen | | ☐ (≤ 1000 ms) |

---

### TC-PERF-08 · Admin-Dashboard – Antwortzeit < 500 ms

**Prüfung:** Browser-DevTools, eingeloggt als Admin.

| Messung | Zeit (ms) | Bestanden |
|---|---|---|
| TTFB | | ☐ (≤ 500 ms) |

---

### TC-PERF-09 🟡 · Datenbankabfragen (keine N+1-Queries)

**Vorgehen** (mit `CMS_DEBUG=true` und Query-Logging):
1. `QUERY_LOG=true` aktivieren (falls in config.php vorhanden)
2. Plugin-Liste aufrufen
3. Anzahl SQL-Queries prüfen

**Zielwert:** ≤ 10 SQL-Queries pro Seitenaufruf (nicht-datenbankintensive Seiten)

| Seite | Queries | Bestanden |
|---|---|---|
| Startseite | | ☐ (≤ 10) |
| Plugin-Liste | | ☐ (≤ 15) |
| Admin-Dashboard | | ☐ (≤ 20) |

---

## 6. Cache-Invalidierung

### TC-PERF-10 🟡 · Cache bei Theme-Wechsel invalidiert

**Vorgehen:**
1. Seite aufrufen (Cache befüllt)
2. Theme wechseln
3. Seite ohne Cache aufrufen

**Erwartetes Ergebnis:**
- Neue Theme-Assets geladen (kein alter CSS-Cache)
- Kein Mixed-Content aus altem und neuem Theme

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Cache nach Theme-Wechsel aktuell | ☐ | ☐ |

---

### TC-PERF-11 🟡 · Cache bei Plugin-Aktivierung invalidiert

**Vorgehen:**
1. Plugin aktivieren
2. Betroffene Seite aufrufen

**Erwartetes Ergebnis:**
- Neue Plugin-Funktionen ohne manuellen Cache-Flush aktiv

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein veralteter Cache nach Plugin-Aktivierung | ☐ | ☐ |

---

## 7. Lasttest (optional / Staging only)

### TC-PERF-12 🟢 · Gleichzeitige Benutzer (10 concurrent)

```bash
ab -n 100 -c 10 https://your-test-instance/
```

**Zielwerte:**
- Fehlerrate: 0 %
- Alle Requests: HTTP 200
- `Time per request` (mean): < 500 ms

| Metrik | Wert | Bestanden |
|---|---|---|
| Fehlerrate | | ☐ (0 %) |
| Mean response time | | ☐ (< 500 ms) |

---

## 8. Testprotokoll

| Datum | Tester | Umgebung | PHP-Version | Cache-Treiber | Ergebnis |
|---|---|---|---|---|---|
| | | | | | |

**Offene Punkte:**

<!-- Hier gefundene Problems dokumentieren, z. B. N+1-Queries, fehlende Cache-Header -->
