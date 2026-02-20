# 🚀 UPLOAD-CHECKLISTE - Neuer Admin-Bereich

## ✅ Dateien zum Hochladen

### KRITISCH (unbedingt hochladen!)
```
1. admin/index.php                      ← Neues Dashboard
2. admin/layout/header.php              ← Neues Layout
3. admin/layout/footer.php              ← Footer mit Debug
4. admin/assets/css/admin.css           ← Komplettes Design-System
5. admin/assets/js/debug.js             ← Debug-Konsole
6. admin/ajax/dashboard-stats.php       ← Stats-Endpoint
7. core/Debug.php                       ← Server-Side Debug
8. core/services/DashboardService.php   ← (Prüfen ob vorhanden!)
```

### Optional (Dokumentation)
```
9. admin/README.md                      ← Ausführliche Doku
10. doc/UPLOAD-ANLEITUNG.md             ← Upload-Guide
11. doc/CACHE-CLEARING-GUIDE.md         ← Cache-Guide
```

---

## 📦 Upload-Reihenfolge

### SCHRITT 1: Core-Klassen
```bash
# Via FTP/SFTP hochladen:
core/Debug.php

# Via SSH (optional - wenn Core-Update nötig):
cd /var/www/html/365cms.de
cp /backup/core/services/DashboardService.php core/services/
```

### SCHRITT 2: Admin-Assets
```bash
# Assets hochladen (CSS + JS):
admin/assets/css/admin.css
admin/assets/js/debug.js
```

### SCHRITT 3: Admin-Layout
```bash
# Layout-Dateien hochladen:
admin/layout/header.php
admin/layout/footer.php
```

### SCHRITT 4: Admin-Seiten
```bash
# Dashboard + AJAX hochladen:
admin/index.php
admin/ajax/dashboard-stats.php
```

---

## 🧪 TESTING

### 1. Cache leeren
```bash
# Via SSH:
php -r "opcache_reset(); echo 'OPcache cleared';"

# Oder via Browser:
# (Falls Cache-Button noch vorhanden)
```

### 2. Dashboard aufrufen
```
URL: https://365cms.de/admin/
```

**✅ Erfolgreich wenn:**
- Sidebar links mit Logo "365CMS" sichtbar
- Welcome-Sektion mit Begrüßung (Guten Morgen/Tag/Abend)
- 4 Skeleton-Cards laden
- Nach 1-2 Sekunden: Stats werden angezeigt (Benutzer, Seiten, Medien, Sicherheit)
- Debug-Button rechts unten sichtbar

**❌ Fehler wenn:**
- Weiße Seite → PHP Fehler (schaue error_log)
- Redirect zu Login → Auth funktioniert nicht
- "Fehler beim Laden" → AJAX-Call fehlgeschlagen (siehe nächster Schritt)

### 3. Debug-Panel testen
```
Tastatur: Ctrl + Shift + D
```

**Panel sollte zeigen:**
- Server-Logs (grün = success, rot = error)
- AJAX-Requests und Responses
- Elapsed Time, Memory Usage
- Detaillierte Stack-Traces bei Fehlern

### 4. Browser-Console prüfen
```
F12 → Console Tab
```

**✅ Sollte sichtbar sein:**
```
🐛 CMS Debug-System aktiv! Drücke Ctrl+Shift+D für Debug-Panel
[CMS Debug SUCCESS] Debug-System initialisiert
[CMS Debug SUCCESS] Dashboard initialisiert
[CMS Debug AJAX] AJAX GET → /admin/ajax/dashboard-stats.php
[CMS Debug SUCCESS] AJAX Response ← /admin/ajax/dashboard-stats.php
```

**❌ Fehler wenn:**
- Rote Errors → JavaScript-Problem (schaue Fehlermeldung)
- 404 bei AJAX-Call → dashboard-stats.php nicht hochgeladen
- 500 bei AJAX-Call → PHP-Fehler in dashboard-stats.php (schaue error_log)

---

## 🐛 FEHLER-BEHEBUNG

### Problem: Weiße Seite
```bash
# Server error_log checken:
tail -f /var/log/apache2/error.log
# oder
tail -f /var/log/nginx/error.log
```

**Häufige Ursachen:**
- `require_once` findet Datei nicht → Pfad prüfen
- `use CMS\Debug` aber Debug.php nicht hochgeladen
- PHP-Syntax-Fehler → Zeile in error_log prüfen

### Problem: "Fehler beim Laden der Dashboard-Daten"
```bash
# dashboard-stats.php direkt aufrufen:
curl -i https://365cms.de/admin/ajax/dashboard-stats.php

# Oder im Browser:
https://365cms.de/admin/ajax/dashboard-stats.php
```

**Erwartete Response:**
```json
{
    "success": true,
    "data": {
        "users": {...},
        "pages": {...},
        "media": {...},
        "security": {...}
    },
    "_debug": {...}
}
```

**Bei Fehler:**
- Status 403 → Nicht eingeloggt oder kein Admin
- Status 500 → PHP-Fehler (schaue error_log)
- Status 429 → Rate-Limit (warten oder Rate-Limit erhöhen)

### Problem: DashboardService nicht gefunden
```bash
# Prüfe ob Datei existiert:
ls -la core/services/DashboardService.php

# Prüfe ob Database::instance() verwendet wird:
grep -n "Database::" core/services/DashboardService.php
```

**Sollte zeigen:**
```php
$this->db = Database::instance();  // ✅ RICHTIG
```

**NICHT:**
```php
$this->db = Database::getInstance();  // ❌ FALSCH
```

---

## 🎨 DESIGN-CHECK

### 1. Farben prüfen
- **Gradient-Sidebar:** Dunkelgrau → Schwarz
- **Primary Blau:** #3b82f6 (Buttons, Active Nav-Item)
- **Success Grün:** #10b981 (Success-Badges)
- **Background:** #f8fafc (Hell-Grau)

### 2. Typografie prüfen
- **Font:** Inter (sollte von Google Fonts laden)
- **Fallback:** -apple-system, Segoe UI (falls Inter nicht lädt)

### 3. Responsiveness prüfen
```
Browser-Größe ändern:
- Desktop (>1024px): Sidebar fixiert, Stats in 4 Spalten
- Tablet (768-1024px): Sidebar schmaler, Stats in 2 Spalten
- Mobile (<768px): Sidebar versteckt, Stats in 1 Spalte
```

---

## 📊 PERFORMANCE-CHECK

### Load-Times messen
```bash
# Via curl:
time curl -o /dev/null -s https://365cms.de/admin/

# Via Chrome DevTools:
F12 → Network Tab → Reload → Check "Finish" Zeit
```

**Ziel-Werte:**
- **Initial Load:** < 500ms (ohne Cache)
- **AJAX-Stats:** < 200ms (mit Cache)
- **Total Size:** ~100KB (mit Fonts & Icons)

### Cache-Status prüfen
```bash
# APCu-Status:
php -r "var_dump(apcu_cache_info());"

# OPcache-Status:
php -r "var_dump(opcache_get_status());"
```

---

## ✅ FINAL-CHECK

**Dashboard funktioniert vollständig wenn:**
- ✅ Sidebar mit Logo & User-Profil sichtbar
- ✅ Welcome-Section mit Begrüßung + Zeit
- ✅ 4 Stat-Cards mit echten Zahlen
- ✅ Debug-Button rechts unten
- ✅ Ctrl+Shift+D öffnet Debug-Panel
- ✅ Console zeigt grüne Success-Logs
- ✅ Keine roten Errors
- ✅ Hover-Effekte funktionieren
- ✅ Fonts laden (Inter, nicht Fallback)

**Wenn alles funktioniert:**
🎉 **FERTIG!** Admin-Bereich ist live und funktioniert!

**Nächste Schritte:**
1. Weitere Seiten hinzufügen (Benutzer, Einstellungen, etc.)
2. Dark-Mode implementieren
3. Mobile-Optimierung verbessern
4. Accessibility optimieren

---

## 🆘 SUPPORT

**Bei Problemen:**
1. Screenshots machen (Dashboard + Debug-Panel + Console)
2. Server error_log kopieren (letzte 50 Zeilen)
3. Browser-Console Output kopieren
4. Issue erstellen mit allen Infos

**Kontakt:**
- GitHub Issues: https://github.com/PS-easyIT/WordPress-365network/issues
- E-Mail: support@365cms.de
