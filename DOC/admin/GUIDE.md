# 365CMS – Admin-Handbuch

Kurzbeschreibung: Operatives Handbuch für die häufigsten Aufgaben im aktuellen Admin-Bereich von 365CMS 2.3.1.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

1. Browser öffnen: `https://eure-domain.de/admin`
2. Zugangsdaten eingeben
3. nach erfolgreichem Login erscheint das Dashboard unter `/admin`

Von dort aus erreicht ihr die zentralen Bereiche über die Sidebar.

---

## Benutzer anlegen oder bearbeiten

**Route:** `/admin/users`

1. Sidebar → **Benutzer & Gruppen** → **Benutzer**
2. neuen Benutzer anlegen oder bestehenden Datensatz öffnen
3. Benutzername, E-Mail, Rolle und Status setzen
4. speichern

Verwandte Bereiche:

- Gruppen: `/admin/groups`
- Rollen & Rechte: `/admin/roles`

---

## Inhalte pflegen

### Neue Seite erstellen

**Route:** `/admin/pages`

1. **Seiten** öffnen
2. Titel und Slug prüfen
3. Inhalt im Editor pflegen
4. SEO-Felder unterhalb des Editors ergänzen
5. veröffentlichen oder als Entwurf speichern

### Neuen Beitrag erstellen

**Route:** `/admin/posts`

1. **Beiträge** öffnen
2. Titel, Inhalt, Auszug und Featured Image pflegen
3. Kategorien, Tags und SEO-Daten ergänzen
4. Beitrag speichern oder veröffentlichen

---

## Theme wechseln und Design anpassen

### Theme aktivieren

**Route:** `/admin/themes`

1. gewünschtes Theme auswählen
2. aktivieren
3. Frontend neu laden und prüfen

### Farben und Design ändern

**Route:** `/admin/theme-editor`

1. Theme-Editor öffnen
2. gewünschte Kategorie wie Farben, Typografie oder Layout wählen
3. Werte anpassen
4. speichern oder bei Bedarf exportieren

Weitere Design-Werkzeuge:

- Menü-Editor: `/admin/menu-editor`
- Landing-Page-Builder: `/admin/landing-page`
- Font-Manager: `/admin/font-manager`

---

## SEO prüfen und optimieren

### SEO-Überblick

**Route:** `/admin/seo-dashboard`

1. SEO-Score und Gesamt-Health prüfen
2. offene Optimierungspunkte sichten
3. in die jeweilige Unterseite navigieren

### Meta-Daten global pflegen

**Route:** `/admin/seo-meta`

1. Title-Templates für Seiten, Beiträge und Sondertypen definieren
2. Meta-Description-Vorlagen pflegen
3. Trennzeichen und Formate anpassen

### Sitemap regenerieren

**Route:** `/admin/seo-sitemap`

1. Sitemap-Einstellungen öffnen
2. Sitemaps bei Bedarf neu generieren
3. robots.txt prüfen und anpassen

Weitere SEO-Werkzeuge:

- Analytics: `/admin/analytics`
- SEO-Audit: `/admin/seo-audit`
- Social Media: `/admin/seo-social`
- Strukturierte Daten: `/admin/seo-schema`
- Technisches SEO: `/admin/seo-technical`
- Redirects: `/admin/redirect-manager`

---

## Performance überwachen

### Gesamtübersicht

**Route:** `/admin/performance`

1. Health-Score und KPIs prüfen
2. Handlungsbedarf erkennen
3. in Unterbereiche navigieren

### Cache leeren

**Route:** `/admin/performance-cache`

1. Cache-Statistiken prüfen
2. Cache bei Bedarf manuell leeren

### WebP-Konvertierung

**Route:** `/admin/performance-media`

1. Medien-Optimierung öffnen
2. geeignete Bilder in WebP konvertieren
3. Referenzen werden automatisch aktualisiert

Weitere Performance-Werkzeuge:

- Datenbank-Wartung: `/admin/performance-database`
- Settings: `/admin/performance-settings`
- Sessions: `/admin/performance-sessions`

---

## Sicherheit und Recht

### Firewall-Regel anlegen

**Route:** `/admin/firewall`

1. Firewall öffnen
2. neue Regel mit IP, Bereich oder Muster anlegen
3. Regel aktivieren und speichern

### AntiSpam konfigurieren

**Route:** `/admin/antispam`

1. Globalen Schalter prüfen
2. Honeypot und Mindestzeit aktivieren
3. Blacklist bei Bedarf ergänzen

### Legal Sites pflegen

**Route:** `/admin/legal-sites`

1. Impressum, Datenschutz, AGB und Widerruf pflegen
2. Seiten automatisch erstellen lassen
3. Cookie-Manager unter `/admin/cookie-manager` ergänzen

---

## System und Diagnose

### Backup erstellen

**Route:** `/admin/backups`

1. gewünschten Backup-Typ wählen (Voll oder nur DB)
2. Backup starten
3. Download oder Löschung über die Liste

### Updates prüfen

**Route:** `/admin/updates`

1. Update-Prüfung starten
2. verfügbare Core-, Theme- und Plugin-Updates einsehen
3. Updates einzeln installieren

### Systemdiagnose

**Route:** `/admin/diagnose`

1. Datenbank-Diagnose prüfen
2. bei Bedarf Monitoring-Seiten aufrufen:
   - Response-Time: `/admin/monitor-response-time`
   - Cron-Status: `/admin/monitor-cron-status`
   - Disk-Usage: `/admin/monitor-disk-usage`
   - Health-Check: `/admin/monitor-health-check`
- Font Manager: `/admin/font-manager`

---

## Plugins verwalten

**Route:** `/admin/plugins`

1. Plugin-Liste öffnen
2. Plugin aktivieren, deaktivieren oder – falls vorgesehen – konfigurieren
3. bei plugin-spezifischen Admin-Seiten in der Plugin-Gruppe der Sidebar weiterarbeiten

Optionaler Marketplace:

- `/admin/plugin-marketplace`

---

## Backup erstellen

**Route:** `/admin/backups`

1. **System** → **Backup & Restore** öffnen
2. vollständiges oder Datenbank-Backup auslösen
3. Ergebnis in der Liste prüfen
4. Backup-Datei außerhalb des Webroots sichern

---

## Cache und Performance prüfen

Wenn Änderungen nicht sichtbar werden oder das System träge reagiert:

1. `/admin/performance` für den Gesamtstatus öffnen
2. `/admin/performance-cache` für Cache-Bereinigung nutzen
3. `/admin/performance-media` bei Bild- und WebP-Themen prüfen
4. `/admin/performance-database` für Wartung und Cleanup verwenden

---

## Pakete und Bestellungen verwalten

Die Aboverwaltung ist heute in drei Seiten aufgeteilt:

| Route | Zweck |
|---|---|
| `/admin/packages` | Pakete und Planparameter |
| `/admin/orders` | Bestellungen und Zuweisungen |
| `/admin/subscription-settings` | globale Abo-Einstellungen |

---

## Datenschutzanfragen bearbeiten

**Route:** `/admin/data-requests`

Hier werden Auskunfts- und Löschanfragen gebündelt bearbeitet. Frühere Einzelseiten für Zugriff und Löschung sind Legacy-Kontext und nicht mehr die führende Oberfläche.

---

## Systeminfo und Diagnose nutzen

Für den technischen Betriebszustand sind heute mehrere Einstiege relevant:

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen |
| `/admin/documentation` | lokale Projektdokumentation |
| `/admin/diagnose` | Diagnoseübersicht |
| `/admin/monitor-health-check` | Health-Checks |
| `/admin/monitor-response-time` | Antwortzeiten |

---

## Wichtige Umstellungen gegenüber älteren Versionen

- nicht mehr `/admin/theme-customizer.php`, sondern `/admin/theme-editor`
- nicht mehr `/admin/backup.php`, sondern `/admin/backups`
- nicht mehr `/admin/subscriptions.php`, sondern Paket-, Order- und Settings-Seiten
- nicht mehr ein einziges `/admin/system.php`, sondern getrennte Info-, System- und Diagnose-Seiten

