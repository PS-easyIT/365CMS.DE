# Cache-Clearing Funktion - Anleitung

## 🔥 **Problem gelöst: Webserver cached alte PHP-Dateien**

### Was macht die Funktion?

Die neue **"Cache leeren"** Funktion löscht **ALLE** serverseitigen Caches mit einem Klick:

#### Geleerte Caches:
1. **OPcache** (Bytecode-Cache) - Wichtigste! Hier werden alte PHP-Dateien gecached
2. **APCu** (User-Cache) - Application Cache
3. **File-Cache** (Seiten-Cache) - Statische HTML-Seiten
4. **Realpath-Cache** - PHP Pfad-Cache
5. **Stat-Cache** - Datei-Status-Cache
6. **LiteSpeed-Cache** - Falls LiteSpeed Webserver aktiv

---

## 📍 **Wo finde ich die Funktion?**

### Dashboard - Quick Actions
1. Gehe zu: **https://365cms.de/admin/dashboard.php**
2. Unter "Schnellzugriff" findest du den **roten Button "Cache leeren"**
3. Klicke drauf → Bestätigung → Fertig!

---

## 🎯 **Wann sollte ich Cache leeren?**

### **IMMER wenn:**
- Du neue PHP-Dateien hochgeladen hast (via FTP/SFTP)
- Dashboard zeigt alte Versionen
- Änderungen im Code nicht sichtbar sind
- Nach Git-Pulls/Updates
- Fehler plötzlich verschwinden sollen (alte Dateien mit Bugs)

### **Beispiel-Szenario:**
```
1. Du lädst 9 neue PHP-Dateien hoch
2. Dashboard zeigt trotzdem nichts an
3. → OPcache liefert noch ALTE Dateien aus
4. → "Cache leeren" klicken
5. → Seite lädt sich neu (2 Sek.)
6. → Dashboard funktioniert! ✅
```

---

## 🔍 **Was passiert nach dem Klick?**

### Ablauf:
1. **Bestätigungsdialog** erscheint mit Liste aller Caches
2. Du bestätigst
3. **Loading-Overlay** wird angezeigt
4. Server löscht alle Caches
5. **Detaillierter Report** erscheint:
   ```
   ✓ 6 Cache-Typ(en) erfolgreich geleert
   
   File-Cache: 42 Dateien gelöscht
   OPcache: Erfolgreich geleert
   APCu: Erfolgreich geleert
   Realpath-Cache: Erfolgreich geleert
   Stat-Cache: Erfolgreich geleert
   LiteSpeed-Cache: Nicht verfügbar
   
   Zeitstempel: 18.02.2026 15:30:45
   ```
6. **Auto-Reload** nach 2 Sekunden (Hard-Reload = erzwingt neue Dateien)

---

## 🛡️ **Sicherheit & Limits**

### Rate-Limiting:
- **Maximal 10x pro Stunde** (Cache-Clearing ist ressourcenintensiv)
- Bei Überschreitung: "Rate limit exceeded" Fehler
- Zähler resettet sich nach 1 Stunde

### Berechtigungen:
- Nur für **Administratoren**
- CSRF-Schutz via Nonce
- Session-Validierung

---

## 🔧 **Technische Details**

### Dateien:
```
core/CacheManager.php
├─ clearAll(): Array         # Löscht alle Caches
├─ getStatus(): Array        # Status aller Cache-Typen
└─ flush(): void             # Nur File-Cache

admin/ajax/clear-cache.php   # AJAX-Endpoint

admin/dashboard.php
├─ Button in Quick Actions
└─ Dashboard.clearCache()    # JavaScript-Methode
```

### PHP-Funktionen:
```php
opcache_reset()              // Bytecode-Cache leeren
apcu_clear_cache()           // User-Cache leeren
clearstatcache(true)         // Realpath + Stat Cache
header('X-LiteSpeed-Purge')  // LiteSpeed Purge
```

---

## 🚨 **Troubleshooting**

### Problem: "Rate limit exceeded"
**Lösung:** Warte 1 Stunde oder erhöhe Limit in `clear-cache.php` Zeile 25:
```php
if (!Security::checkRateLimit('clear_cache', 20, 3600)) { // 20x statt 10x
```

### Problem: "OPcache: Nicht installiert"
**Lösung:** OPcache auf Server aktivieren:
```bash
# In php.ini:
opcache.enable=1
opcache.enable_cli=1
```

### Problem: Caches werden nicht geleert
**Prüfe:**
1. PHP-Fehlerlog: `/home/u185238248/logs/error_log`
2. Browser-Konsole (F12): Netzwerk-Tab → AJAX-Response
3. Server-Berechtigungen: Cache-Verzeichnis muss schreibbar sein (755)

---

## 📦 **Upload-Checkliste nach Code-Änderungen**

1. ✅ Neue Dateien hochladen via SFTP
2. ✅ In Admin einloggen
3. ✅ **"Cache leeren"** klicken
4. ✅ Warten bis Auto-Reload
5. ✅ Testen ob Änderungen sichtbar

---

## 🎉 **WICHTIG für 365cms.de:**

Nach dem Upload der **3 neuen Dateien**:
```
admin/ajax/clear-cache.php    (NEU)
core/CacheManager.php         (GEÄNDERT)
admin/dashboard.php           (GEÄNDERT)
```

**SOFORT "Cache leeren" klicken!** 

Sonst sieht man den Button nicht, weil das Dashboard noch die alte Version lädt! 😅

---

**Commit:** `8e70a2e` - feat: Umfassende Cache-Clearing Funktion implementiert  
**Datum:** 18. Februar 2026
