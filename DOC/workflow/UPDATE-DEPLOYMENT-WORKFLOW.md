# Update & Deployment Workflow – 365CMS

> **Stand:** 2026-03-11 | **Version:** 2.5.30 | **Status:** Aktuell
>
> **Bereich:** Updates & Deployments · **Version:** 2.5.30  
> **CMS-Update-System:** `core/Services/UpdateService.php`, `admin/updates.php`  
> **GitHub:** PS-easyIT/365CMS.DE

---
<!-- UPDATED: 2026-03-11 -->

## Übersicht: Update-Typen

| Typ | Auslöser | Risiko | Backup nötig? |
|---|---|---|---|
| **CMS-Core-Update** | `update.json` neue Version | ⚠️ Hoch | ✅ Pflicht |
| **Plugin-Update** | Plugin `update.json` | 🟡 Mittel | ✅ Empfohlen |
| **Theme-Update** | Theme Marketplace | 🟢 Gering | ✅ Empfohlen |
| **Security-Patch** | Sicherheitsmeldung | ⚠️ Sofort anwenden | ✅ Pflicht |
| **Konfigurationsänderung** | Admin-Einstellungen | 🟢 Gering | Export |

---

## Workflow 1: CMS-Core-Update

### Phase 1: Vorbereitung
```
1. Changelog lesen → Was ändert sich in der neuen Version?
2. Breaking Changes identifizieren → PHP-Version? DB-Schema-Änderungen?
3. Staging-System testen (wenn vorhanden)
4. VOLLBACKUP erstellen (DB + Dateien)
   → Admin → Backup → Vollbackup jetzt erstellen
5. Backup lokal heruntergeladen und gespeichert?
```

### Phase 2: Update herunterladen und verifizieren

```php
// UpdateService prüft update.json vom GitHub-Releases-Endpoint:
$updater = \CMS\Services\UpdateService::instance();
$info    = $updater->checkForUpdate();

if ($info && version_compare($info['version'], CMS_VERSION, '>')) {
    // SHA-256 verifizieren VOR dem Entpacken!
    $zipPath = $updater->downloadUpdate($info['download_url']);
    $hash    = hash_file('sha256', $zipPath);

    if (!hash_equals($info['sha256'], $hash)) {
        // Manipulierte Datei! Nicht entpacken!
        unlink($zipPath);
        throw new \RuntimeException('SHA-256 Verifikation fehlgeschlagen!');
    }
}
```

**Manuell via PowerShell:**
```powershell
# Datei herunterladen und prüfen:
Invoke-WebRequest -Uri "https://github.com/PS-easyIT/365CMS.DE/releases/download/v1.6.15/365cms.zip" -OutFile "365cms_new.zip"
(Get-FileHash "365cms_new.zip" -Algorithm SHA256).Hash
# → Mit SHA-256 aus update.json vergleichen!
```

### Phase 3: Wartungsmodus aktivieren

```php
// maintenance.php im Webroot erstellen:
// (Router leitet alle Requests um)
file_put_contents(ABSPATH . 'maintenance.html',
    '<h1>Wartungsarbeiten</h1><p>Wir sind gleich zurück.</p>'
);
```

### Phase 4: Update anwenden

```bash
# Auf Server (SSH):
cd /var/www/html

# Backup der aktuellen CMS-Dateien:
zip -r cms_backup_before_update.zip cms/core cms/includes cms/admin

# Update entpacken (überschreibt nur Core-Dateien):
unzip -o 365cms_new.zip -d cms/

# Dateirechte setzen:
chown -R www-data:www-data cms/
find cms/ -type f -name "*.php" -exec chmod 644 {} \;
find cms/ -type d -exec chmod 755 {} \;
```

### Phase 5: DB-Migrationen ausführen

```php
// Nach dem Update automatisch ausgeführt (wenn in update.json migration: true):
$updater->runMigrations(from: '1.6.14', to: '1.6.15');

// Oder manuell:
// Admin → admin/updates.php → "Datenbankmigrationen ausführen"
```

### Phase 6: Verifikation

```
[ ] Startseite lädt ohne Fehler
[ ] Admin-Login funktioniert
[ ] Fehlerlog prüfen: cms/logs/error.log
[ ] Plugins testen (kritische Features)
[ ] CMS-Version in Admin-Sidebar: neue Version sichtbar?
```

### Phase 6a: Beta-Smoke nach Deployment

Die Code-Ursachen früherer Produktiv-/Beta-Blocker sind inzwischen behoben – **der Pflicht-Retest nach jedem Release bleibt trotzdem fester Bestandteil der Abnahme**.

1. Zuerst die Repo-Disziplin prüfen:

```bash
php tests/release-smoke/run.php
```

2. Danach die reale Beta-Instanz manuell in dieser Reihenfolge prüfen:

```
Öffentliche Pfade
[ ] /
[ ] /contact
[ ] /search
[ ] /blog

Auth-Pfade
[ ] /login
[ ] /register

Member-Retest
[ ] /member/dashboard
[ ] /member/privacy
[ ] /member/media

Admin-Retest
[ ] /admin
[ ] /admin/diagnose
[ ] /admin/comments
[ ] /admin/toc
[ ] /admin/hub-sites
[ ] /admin/site-tables
[ ] /admin/users/new
[ ] /admin/groups

Fehlpfad
[ ] /this-route-should-404
```

3. Zusätzliche Pflichtbeobachtungen:

```
[ ] Kommentar-POST im Blog einmal real absenden oder sichtbar gegenprüfen
[ ] Featured-Image-/Member-Media-Upload einmal durchspielen
[ ] Browser-Konsole und Netzwerk-Tab auf neue Fehler prüfen
[ ] Fehlerlog und Audit-/Debug-Log nach dem Smoke-Test prüfen
```

**Regel:** Ein Release gilt erst dann als sauber abgenommen, wenn sowohl `php tests/release-smoke/run.php` grün ist als auch die obige Browser-Stichprobe auf der Beta ohne neue Blocker durchläuft.

### Phase 7: Wartungsmodus deaktivieren

```php
unlink(ABSPATH . 'maintenance.html');
```

---

## Workflow 2: Plugin-Update

```
1. Admin → admin/plugins.php → Update-Badge sichtbar?
2. Plugin-Changelog lesen (GitHub-Link)
3. DB-Backup (falls Plugin DB-Tabellen hat)
4. Update-Button → SHA-256-Check automatisch
5. Deaktivierung + Reaktivierung testen
6. Features des Plugins testen
```

**Sicherheits-Validierung (intern):**
```php
// In PluginManager::updatePlugin():
$hash = hash_file('sha256', $downloadedZip);
if (!hash_equals($expectedSha256, $hash)) {
    throw new \RuntimeException("Plugin-Update $slug: SHA-256-Verifikation fehlgeschlagen");
}
// → Nie entpacken ohne Verifikation!
```

---

## Workflow 3: Deployment (GitHub → Server)

### Automatisiertes Deployment via GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production
on:
  release:
    types: [published]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/html/cms
            git pull origin main
            php index.php --action=run_migrations
            php index.php --action=clear_cache
```

### Manuelles Deployment (Fallback)
```powershell
# Dateien via SFTP oder rsync übertragen:
# rsync -avz --exclude='uploads/' --exclude='cache/' --exclude='config.php' ./CMS/ server:/var/www/html/cms/
```

---

## Rollback-Prozesse

### Schnell-Rollback (< 5 Minuten)
```
1. Aktuelle DB sichern (auch nach Update)
2. Altes ZIP-Backup entpacken über aktuellem Stand
3. DB-Backup vor Update wiederherstellen
4. Wartungsmodus deaktivieren
5. Testen
```

### Git-basierter Rollback
```bash
git log --oneline -10
git checkout <commit-hash>
# oder:
git revert HEAD
git push
```

---

## Checkliste: Vor jedem Update

```
VOR DEM UPDATE:
[ ] Vollbackup erstellt und lokal gespeichert
[ ] Changelog gelesen, Breaking Changes bekannt
[ ] SHA-256 des Downloads verifiziert
[ ] Wartungsmodus aktiviert

NACH DEM UPDATE:
[ ] Fehlerlog sauber (keine neuen Fehler)
[ ] Admin-Login getestet
[ ] Kritische Features getestet
[ ] Beta-Smoke-Stichprobe auf `/`, `/login`, `/member/dashboard`, `/admin/diagnose` und `/this-route-should-404` durchgeführt
[ ] Wartungsmodus deaktiviert
[ ] CMS-Version in Admin prüfen
```

---

## Referenzen

- [admin/updates.php](../../CMS/admin/updates.php) – Update-UI
- [core/Services/UpdateService.php](../../CMS/core/Services/UpdateService.php) – Update-Service
- [BACKUP-RESTORE-WORKFLOW.md](BACKUP-RESTORE-WORKFLOW.md) – Backup vor Updates
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – H-15: Update-SHA256-Verfikation
