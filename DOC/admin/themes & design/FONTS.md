# Font Management
**Datei:** `admin/fonts-local.php`

Verwaltung von Schriftarten unter Berücksichtigung der DSGVO (Lokales Hosting).

## Funktionen

### 1. Google Fonts Import
- Suche im Google Fonts Katalog.
- **Download:** Schriftarten werden vom Google Server heruntergeladen und lokal (`/assets/fonts/`) gespeichert.
- **CSS-Generierung:** `@font-face` Regeln werden automatisch erstellt.
- **Vorteil:** Keine Verbindung zu Google-Servern beim Besucher -> DSGVO-konform.

### 2. Eigene Fonts
- Upload von `.woff`, `.woff2`, `.ttf` Dateien.
- Manuelle Zuweisung von Font-Family Namen.

### 3. Vorschau
- Live-Vorschau aller installierten Schriftarten.
