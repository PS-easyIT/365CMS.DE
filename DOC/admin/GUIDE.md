# 365CMS – Admin-Handbuch

Kurzbeschreibung: Operatives Handbuch für die häufigsten Aufgaben im aktuellen Admin-Bereich von 365CMS 2.3.1.

Letzte Aktualisierung: 2026-03-07

---

## Erster Login

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

