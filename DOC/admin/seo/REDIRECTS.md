# URL-Weiterleitungen (Redirect Manager)

Kurzbeschreibung: Verwaltung von 301/302-Weiterleitungen, Protokollierung, 404-Monitoring und Aggregatkennzahlen für Dashboard-Trends.

Letzte Aktualisierung: 2026-05-11 · Version 2.9.751

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/redirect-manager` |
| Modul | `CMS/admin/modules/seo/RedirectManagerModule.php` |
| View | `CMS/admin/views/seo/redirects.php` |
| CSRF-Kontext | `admin_redirect_manager` |

---

## Funktionsumfang

### Redirect-Liste

Zeigt alle konfigurierten Weiterleitungen mit Quell-URL, Ziel-URL, Typ (301/302), Status und Trefferanzahl.

### 404-/Redirect-Kennzahlen

- Der Redirect-Manager und der 404-Monitor nutzen gemeinsame Aggregatwerte für:
	- Gesamtzahl der Redirect-Regeln
	- aktive Redirect-Regeln
	- bekannte 404-Pfade
	- kumulierte 404-Hits
- Die Kennzahlen werden serverseitig über SQL-Aggregate berechnet und sind **nicht** mehr von der auf 200 Einträge begrenzten 404-Tabellenansicht abhängig.
- Diese Aggregatwerte speisen zusätzlich die read-only Trendkarten im SEO-Dashboard.

### 404-Monitor-Übernahme

- `/admin/not-found-monitor` nutzt denselben `admin_redirect_manager`-CSRF-Kontext wie der Redirect-Manager.
- Ein Klick auf **Übernehmen** öffnet den gemeinsamen Redirect-Dialog und behandelt ungelöste 404-Logs ausdrücklich als neue Weiterleitung (`redirect_id = 0`). Die interne 404-Log-ID wird nicht als Redirect-ID übernommen.
- Bereits übernommene 404-Zeilen bleiben bearbeitbar, weil sie über die vom Service gesetzte echte `redirect_id` auf die vorhandene Redirect-Regel zeigen.
- Der JavaScript-Dialog fällt fail-soft weiter, wenn Browser-Storage blockiert ist oder die Bootstrap-Modal-API nicht global verfügbar ist; dadurch darf der Übernahmebutton nicht still ins Leere laufen.

### Aktionen

| Aktion | Methode |
|---|---|
| Erstellen/Bearbeiten | `saveRedirect(array $post)` |
| Löschen | `deleteRedirect(int $id)` |
| Aktivieren/Deaktivieren | `toggleRedirect(int $id)` |
| Logs leeren | `clearLogs()` |

### Redirect-Typen

| Typ | HTTP-Status | Zweck |
|---|---|---|
| Permanent | `301` | SEO-wirksame dauerhafte Weiterleitung |
| Temporär | `302` | Vorübergehende Umleitung |

---

## Sicherheit

- Admin-Zugriffsschutz
- CSRF-Prüfung über gemeinsamen SEO-Kontext
- Serverseitige Validierung von Quell- und Ziel-URLs
- 404-Übernahmen bleiben POST-only und erzeugen keine Token- oder Aktionsparameter in URLs.
- Trendkarten und Aggregatkennzahlen bleiben read-only; es gibt keinen neuen GET-Mutationspfad.

## SEO-Hinweis

- Google verarbeitet `301`/`308` als Signal für dauerhafte Ziel-URLs und `302` als temporäre Umleitung.
- `404`-Antworten sind technisch legitim, sollten aber beobachtet werden; das Dashboard hebt deshalb bekannte 404-Pfade und Redirect-Bestand getrennt hervor, statt automatisch jede fehlende URL umzuleiten.

---

## Verwandte Seiten

- [SEO-Übersicht](SEO.md)
- [Technisches SEO](SEO.md#technisches-seo)
