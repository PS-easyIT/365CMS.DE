# 🚀 UPLOAD-ANLEITUNG - Dashboard Fix

## Problem gelöst
**DashboardService.php** hatte noch den **Database::getInstance()** Bug (wurde in Commit e79d15f übersehen).
Das führte zu einem **PHP Fatal Error** bei jedem AJAX-Aufruf → Dashboard blieb leer.

## ✅ Lösung: 2-fach abgesichert

### 1. AJAX-Dashboard (Original) - GEFIXT
**Datei:** `core/services/DashboardService.php`
- **Zeile 20:** `Database::getInstance()` → `Database::instance()` ✓
- AJAX funktioniert jetzt korrekt

### 2. Server-Side Dashboard (NEU) - ROBUST
**Datei:** `admin/dashboard-ssr.php`
- **Kein AJAX:** Daten werden direkt beim Laden geladen
- **Bessere Fehleranzeige:** Zeigt exakte Fehlermeldungen
- **Debug-Infos:** Sieht man sofort WAS nicht funktioniert
- **Schönes Design:** Gradient-Header, Stat-Cards, System-Info

---

## 📦 DATEIEN ZUM HOCHLADEN (11 Dateien)

### KRITISCH (sofort hochladen!)
```
1. core/services/DashboardService.php    ← WICHTIG! Dashboard funktioniert nicht ohne!
2. admin/dashboard-ssr.php               ← NEU! Robuste Alternative
```

### Aus früheren Commits (falls noch nicht hochgeladen)
```
3. core/Database.php                     ← Settings-Schema
4. core/PluginManager.php                ← SQL-Updates
5. core/ThemeManager.php                 ← Property-Names + setActiveTheme
6. includes/functions.php                ← get_option() fix
7. themes/default/home.php               ← getSetting() fix
8. core/services/LandingPageService.php  ← Database::instance() + prefix fix
9. core/services/StatusService.php       ← Database::instance()
10. core/services/UserService.php        ← Database::instance()
11. admin/ajax/landing-get.php           ← Security import
```

### Optional (Cache-Clearing-Feature)
```
12. core/CacheManager.php                ← Cache-Management
13. admin/ajax/clear-cache.php           ← AJAX-Endpoint
14. admin/dashboard.php                  ← Cache-Button
```

---

## 🧪 TESTING-STRATEGIE

### Phase 1: Server-Side Dashboard testen (ZUERST!)
```
1. Hochladen: admin/dashboard-ssr.php
2. Aufrufen: https://365cms.de/admin/dashboard-ssr.php
3. Login falls nötig
```

**✅ Erfolgreich wenn:**
- Dashboard zeigt Statistiken (Benutzer, Seiten, Medien, etc.)
- System-Info sichtbar (PHP-Version, Memory, Disk)
- Debug-Info unten zeigt: "Stats Keys: users, pages, media, security, system"

**❌ Wenn IMMER NOCH leer:**
- Schaue in die **rote Fehler-Box** oben
- DashboardService hat einen Fehler (nicht nur Database-Problem)
- Prüfe Server error_log

### Phase 2: Original-Dashboard testen
```
1. Hochladen: core/services/DashboardService.php
2. Aufrufen: https://365cms.de/admin/dashboard.php
3. F12 → Console öffnen
```

**✅ Erfolgreich wenn:**
- Stat-Cards laden und zeigen Zahlen
- Console: keine Errors
- Network-Tab: dashboard-stats.php → Status 200 → JSON-Response

**❌ Wenn Fehler:**
- Console zeigt welche AJAX-Anfrage fehlschlägt
- Network-Tab: dashboard-stats.php → Status 500 → PHP Error
- Schaue in Server error_log

---

## 🔧 CACHE LEEREN (WICHTIG!)

### Methode 1: Via Dashboard
```
1. Einloggen: https://365cms.de/admin/
2. Klick: "Cache leeren" Button (roter Button oben)
3. Warten bis "Cache erfolgreich geleert"
```

### Methode 2: Via Server (falls Dashboard nicht geht)
```ssh
# SSH einloggen
ssh user@365cms.de

# PHP-Cache leeren
php -r "opcache_reset(); echo 'OPcache cleared';"

# Oder via Script
echo "<?php opcache_reset(); apcu_clear_cache(); echo 'Cache cleared'; ?>" > /tmp/clear.php
php /tmp/clear.php
rm /tmp/clear.php
```

### Methode 3: Via .htaccess (automatisch)
```apache
# In .htaccess einfügen (Optional - verhindert Caching)
<IfModule mod_expires.c>
    ExpiresActive Off
</IfModule>
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires 0
</IfModule>
```

---

## 🎯 QUICK-START (minimale Schritte)

1. **Hochladen:** `core/services/DashboardService.php` + `admin/dashboard-ssr.php`
2. **Cache leeren:** SSH oder Dashboard-Button
3. **Testen:** `https://365cms.de/admin/dashboard-ssr.php`
4. **Funktioniert?** Ja → Super! Nein → Error-Box lesen + error_log checken

---

## 📊 WELCHES DASHBOARD NUTZEN?

| Dashboard | Vorteil | Nachteil |
|-----------|---------|----------|
| **dashboard.php** (Original) | Fancy Loading-Animation, dynamisches Nachladen | AJAX kann fehlschlagen, schwer zu debuggen |
| **dashboard-ssr.php** (NEU) | Sofort sichtbar, bessere Fehler, robust | Keine Animations, Seite muss neu laden für Updates |

**Empfehlung:** Teste **dashboard-ssr.php** ZUERST - wenn das geht, weißt du dass DashboardService funktioniert!

---

## 🐛 DEBUGGING FALLS IMMER NOCH LEER

### 1. Server Error Log checken
```bash
# Via SSH
tail -f /var/log/apache2/error.log
# oder
tail -f /var/log/nginx/error.log
```

### 2. PHP Errors anzeigen (temporär!)
```php
// In config.php GANZ OBEN einfügen (nur zum Testing!)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
```

### 3. DashboardService direkt testen
```php
// test-dashboard.php im Root erstellen
<?php
require_once 'config.php';
require_once ABSPATH . 'core/autoload.php';

use CMS\Services\DashboardService;

try {
    $service = DashboardService::getInstance();
    $stats = $service->getAllStats();
    echo '<pre>';
    print_r($stats);
    echo '</pre>';
} catch (\Exception $e) {
    echo '<h1>ERROR:</h1><pre>';
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}
```

### 4. Datenbank-Verbindung prüfen
```php
// test-db.php im Root erstellen
<?php
require_once 'config.php';
require_once ABSPATH . 'core/autoload.php';

use CMS\Database;

try {
    $db = Database::instance();
    echo "✓ Database connected!<br>";
    echo "Prefix: " . $db->prefix() . "<br>";
    
    $result = $db->query("SHOW TABLES");
    echo "Tables:<br>";
    foreach ($result as $table) {
        echo "- " . current($table) . "<br>";
    }
} catch (\Exception $e) {
    echo "✗ Database ERROR: " . $e->getMessage();
}
```

---

## 📝 COMMITS ÜBERSICHT

| Commit | Datei(en) | Was gefixt |
|--------|-----------|------------|
| 055babd | 5 files | Settings-Tabelle: setting_* → option_* |
| d7ef59f | 2 files | Property access: $setting_value → $option_value |
| e79d15f | 4 files | Database::getInstance() → instance() (3 Services + ThemeManager) |
| 3b9b54e | 1 file | landing-get.php: Security import |
| 8e70a2e | 3 files | Cache-Clearing-Feature |
| **55f64dd** | **2 files** | **DashboardService fix + SSR dashboard** ← DU BIST HIER |

---

## 🎉 ERFOLGS-KRITERIEN

Dashboard funktioniert wenn:
- ✅ dashboard-ssr.php zeigt Statistiken
- ✅ Keine PHP-Errors im error_log
- ✅ Debug-Info unten zeigt: "Stats Keys: users, pages, media..."
- ✅ System-Info zeigt PHP-Version, Memory, Disk Space

Dann kannst du entscheiden:
1. **dashboard-ssr.php** als Standard verwenden (robust, kein AJAX)
2. **dashboard.php** behalten (fancy, aber AJAX-abhängig)
3. **Beide** verfügbar lassen und je nach Bedarf wechseln

---

## ❓ SUPPORT

Falls IMMER NOCH Probleme:
1. **dashboard-ssr.php** aufrufen → Fehlermeldung screenshotten
2. **Server error_log** kopieren (letzte 50 Zeilen)
3. **test-dashboard.php** Output kopieren
4. Alles schicken → dann schauen wir genau was DashboardService macht
