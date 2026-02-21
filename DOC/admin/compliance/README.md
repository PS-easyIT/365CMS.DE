# Cookie & Compliance Management

**Datei:** `admin/cookies.php`, `admin/data-access.php`, `admin/data-deletion.php`

Dieses Modul stellt die DSGVO-Konformität (GDPR) des CMS sicher.

## Module

### 1. Cookie Scanner (`cookies.php`)
- **Automatischer Scan:** Durchsucht die Website nach gesetzten Cookies.
- **Kategorisierung:** Ordnet Cookies automatisch Kategorien zu (Notwendig, Marketing, Statistik).
- **Consent Banner:** Konfiguration des Cookie-Banners im Frontend.

### 2. Datenauskunft (`data-access.php`)
- **Export:** Ermöglicht den Export aller gespeicherten Daten zu einem spezifischen Benutzer.
- **Formate:** JSON oder XML Export.

### 3. Datenlöschung (`data-deletion.php`)
- **Löschaufträge:** Bearbeitung von "Recht auf Vergessenwerden" Anfragen.
- **Anonymisierung:** Entfernt personenbezogene Daten aus Logs und Statistiken, ohne die Integrität zu verletzen.
- **Log:** Protokollierung durchgeführter Löschungen.
