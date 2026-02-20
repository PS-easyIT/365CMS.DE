# Analytics Dashboard

**Datei:** `admin/analytics.php`

Das Analytics-Dashboard bietet Echtzeit-Einblicke in die Besucherstatistiken und System-Performance.

## Funktionen

### 1. Besucher-Statistiken (Real-time)
- **Total Visitors:** Gesamtzahl der Besucher (basierend auf Session-Tracking).
- **Unique Visitors:** Anzahl eindeutiger Besucher (IP-basiert).
- **Active Now:** Besucher, die in den letzten 5 Minuten aktiv waren.
- **Bounce Rate:** Prozentsatz der Besucher mit nur einem Seitenaufruf.

### 2. System Health
Anzeige der aktuellen Server-Auslastung:
- **CPU:** Aktuelle CPU-Last.
- **Memory:** RAM-Nutzung.
- **Disk:** Belegter Speicherplatz.

### 3. Traffic-Quellen
- Übersicht der Referrer (Woher kommen die Besucher?).
- Direkte Zugriffe vs. externe Links.

### 4. Top Seiten
- Liste der am häufigsten aufgerufenen Seiten.
- Anzahl der Aufrufe pro Seite.

## Technische Details
- **Speicherung:** Daten werden in der Tabelle `cms_page_views` (evtl. auch `cms_analytics`) gespeichert.
- **Tracking:** Erfolgt über den `TrackingService`.
- **Datenschutz:** IP-Adressen werden anonymisiert gespeichert (sofern konfiguriert).
