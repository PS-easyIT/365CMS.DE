# 365CMS – Installation
> **Stand:** 2026-05-14 | **Version:** 2.9.785 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Überblick](#überblick)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Zielstruktur beim Deployment](#zielstruktur-beim-deployment)
- [Datenbank anlegen](#datenbank-anlegen)
- [Konfiguration in `CMS/config/app.php`](#konfiguration-in-cmsconfigappphp)
- [Dateirechte](#dateirechte)
- [Webserver konfigurieren](#webserver-konfigurieren)
- [Erster Start](#erster-start)
- [Produktions-Checkliste](#produktions-checkliste)
- [Troubleshooting](#troubleshooting)

---
<!-- UPDATED: 2026-05-14 -->

## Überblick

Für neue Installationen gibt es aktuell zwei praktikable Wege:

1. **Web-Installer über `CMS/install.php`**  
   Der Installer prüft PHP/MySQL-Schreibfähigkeit, fragt Datenbank-, Site- und Admin-Daten ab, schreibt `CMS/config/app.php`, erstellt das zentrale Schema über `CMS\SchemaManager` und setzt sichere Default-Settings.
2. **Repository-Checkout / manuelle Einrichtung**  
   Alternativ kann `CMS/config/app.php` direkt bearbeitet werden. Alle Platzhalterwerte und Security-Keys müssen dann manuell ersetzt werden.

Der bevorzugte Einstieg für frische Deployments ist der Installer. Nach erfolgreicher Installation sollte `install.php` aus öffentlichen Deployments entfernt oder mindestens nur für angemeldete Administratoren erreichbar gehalten werden.

Wichtig für Betrieb und Doku: Das Repository soll den tatsächlich per FTP bzw. Deployment ausgerollten Runtime-Zustand widerspiegeln. Produktiv maßgeblich ist also immer die installierte Struktur unter `CMS/`.

---

## Systemvoraussetzungen

| Komponente | Minimum | Empfohlen |
|---|---:|---:|
| PHP | 8.4 | 8.4+ |
| MySQL | 8.0 | 8.0+ |
| MariaDB | 10.6 | 10.11+ |
| Webserver | Apache 2.4 / Nginx 1.18 | aktuelle stabile Version |
| PHP-Erweiterungen | `pdo_mysql`, `mbstring`, `json`, `openssl` | zusätzlich `curl`, `gd`, `zip`, `intl` |
| Arbeitsspeicher | 128 MB | 256 MB+ |

Empfohlen für Admin- und Update-Funktionen:

- `curl` für externe HTTP-Anfragen
- `zip` für Paket- und Doku-Synchronisation
- `gd` oder `imagick` für Bildverarbeitung

Wichtig: 365CMS prüft die Mindestplattform inzwischen bereits sehr früh. Wenn die aktive Runtime unter PHP 8.4 liegt, blockieren `CMS/config.php`, Installer, Status-/Update-Prüfungen und der zentrale `Bootstrap` den regulären Start bzw. markieren die Umgebung als nicht kompatibel.

---

## Zielstruktur beim Deployment

In produktiven Installationen ist in der Regel der Inhalt des Ordners `CMS/` das eigentliche Webroot.

| Pfad | Zweck |
|---|---|
| `CMS/index.php` | Frontend-Einstiegspunkt |
| `CMS/config.php` | Stub, lädt `config/app.php` |
| `CMS/config/app.php` | echte Konfiguration |
| `CMS/core/` | Bootstrap, Router, Datenbank, Services |
| `CMS/admin/` | Admin-Einstiegspunkte und Module |
| `CMS/themes/` | aktive Themes |
| `CMS/plugins/` | installierte Plugins |
| `CMS/backups/` | lokale Backups |
| `CMS/logs/` | Log-Dateien |
| `CMS/cache/` | Laufzeit- und Schema-Flags |

---

## Datenbank anlegen

```sql
CREATE DATABASE cms365 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms365user'@'localhost' IDENTIFIED BY 'SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON cms365.* TO 'cms365user'@'localhost';
FLUSH PRIVILEGES;
```

Die Kern-Tabellen werden anschließend beim ersten erfolgreichen Start über `CMS\SchemaManager` erzeugt.

Bei Nutzung von `CMS/install.php` werden dieselben zentralen Schema-Queries bereits während der Installation ausgeführt. Der Installer legt neue Installationen mit dem Theme `cms-default`, dem gewählten Site-Namen als `site_title` und den ausgewählten Core-Modulen an.

---

## Konfiguration in `CMS/config/app.php`

Öffnet `CMS/config/app.php` und ersetzt **alle Platzhalterwerte**.

Die wichtigsten Konstanten sind:

| Konstante | Zweck |
|---|---|
| `DB_HOST` | Datenbank-Host |
| `DB_NAME` | Datenbankname |
| `DB_USER` | Datenbankbenutzer |
| `DB_PASS` | Datenbankpasswort |
| `DB_PREFIX` | Tabellenpräfix |
| `SITE_URL` | vollständige Basis-URL ohne Trailing Slash |
| `ADMIN_EMAIL` | zentrale Admin-Adresse |
| `CMS_DEBUG` | Debug-Modus |
| `AUTH_KEY`, `SECURE_AUTH_KEY`, `NONCE_KEY` | Sicherheits-Keys |

Empfehlungen:

- `SITE_URL` exakt auf die produktive URL setzen
- `CMS_DEBUG` in Produktion auf `false`
- Sicherheits-Keys mit kryptographisch sicheren Zufallswerten befüllen
- Platzhalter wie `YOUR_...` vollständig entfernen

Beispiel für einen sicheren Key:

```php
bin2hex(random_bytes(32))
```

---

## Dateirechte

| Pfad | Empfehlung |
|---|---|
| Verzeichnisse | `755` |
| normale Dateien | `644` |
| `CMS/config.php` | `640` oder `644` |
| `CMS/config/app.php` | `640` oder `644` |
| `CMS/logs/` | nur serverseitig beschreibbar |
| `CMS/cache/` | serverseitig beschreibbar |
| `CMS/backups/` | nicht öffentlich auslieferbar |

Keine pauschalen `777`-Rechte in Produktion verwenden.

---

## Webserver konfigurieren

### Apache

Voraussetzungen:

- `mod_rewrite` aktiv
- `AllowOverride All` für das Webroot
- Verzeichnisindexe deaktiviert

### Nginx

Der zentrale Punkt ist die Übergabe unbekannter Pfade an `index.php`:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Zusätzlich sollten sensible Verzeichnisse nicht direkt ausgeliefert werden, insbesondere:

- `config/`
- `logs/`
- `cache/`
- `backups/`

---

## Erster Start

Nach korrekter Konfiguration:

1. Browser auf die produktive `SITE_URL` öffnen
2. 365CMS lädt `CMS/config.php`
3. der Stub lädt `CMS/config/app.php`
4. `CMS\Bootstrap` initialisiert Services und Routing
5. `CMS\Database` und `CMS\SchemaManager` legen die Basisstruktur an
6. falls noch kein Admin existiert, wird ein Standard-Admin erzeugt

Vor Schritt 4 validiert der Bootstrap zusätzlich die produktiv gebündelten Composer-Manifeste von `mailer`, `mime` und `translation` gegen die offizielle Mindestplattform. So wird eine nicht unterstützte PHP-Laufzeit nicht erst mitten im Mail- oder Translation-Pfad sichtbar.

Wichtig: Der erste generierte Admin-Zugang wird vom Schema-Setup in die Logs geschrieben. Prüft nach dem Erststart daher insbesondere `CMS/logs/` bzw. die temporären Zugangsdaten und ändert das Kennwort sofort.

Bei der Installation über `CMS/install.php` wird das erste Admin-Konto direkt im Installer angelegt. Die Formularschritte sind CSRF-geschützt, die Installer-Session nutzt `HttpOnly`, `SameSite=Lax` und bei HTTPS zusätzlich `Secure`, und das Admin-Passwort muss mindestens 12 Zeichen lang sein.

---

## Produktions-Checkliste

- [ ] Platzhalter in `CMS/config/app.php` vollständig ersetzt
- [ ] starke Sicherheits-Keys gesetzt
- [ ] `CMS_DEBUG` in Produktion deaktiviert
- [ ] HTTPS aktiv
- [ ] `config/`, `logs/`, `cache/`, `backups/` serverseitig geschützt
- [ ] erstes Admin-Passwort geändert
- [ ] regelmäßige Backups eingerichtet
- [ ] Schreibrechte auf notwendige Verzeichnisse begrenzt

---

## Troubleshooting

### Weiterleitung auf eine Installations- oder Konfigurationsseite

Ursache meist:

- `CMS/config/app.php` fehlt
- oder enthält noch Platzhalterwerte wie `YOUR_DB_USER`

### Datenbankverbindung schlägt fehl

Prüfen:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Rechte des Datenbankbenutzers
- Erreichbarkeit des Datenbankservers

### Pretty URLs funktionieren nicht

Prüfen:

- Apache-Rewrite bzw. Nginx-`try_files`
- korrektes Webroot
- `SITE_URL` ohne Tippfehler und ohne Trailing Slash

### Admin lädt, aber Teilfunktionen fehlen

Typische Ursachen:

- fehlende PHP-Erweiterungen (`curl`, `zip`, `gd`)
- nicht beschreibbare Verzeichnisse (`cache`, `logs`, `backups`)
- veraltete oder nicht migrierte Tabellenstruktur

Weiterführend:

- [System & Monitoring](admin/system-settings/SYSTEM.md)
- [Systemarchitektur](core/ARCHITECTURE.md)
- [Datenbank-Schema](core/DATABASE-SCHEMA.md)

