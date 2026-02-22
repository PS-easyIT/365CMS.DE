# THEME AUDIT – 365CMS (`/CMS`)

## Scope
- Relevante Komponenten: `core/ThemeManager.php`, `core/Services/ThemeCustomizer.php`, `admin/themes.php`, `admin/theme-editor.php`, `admin/theme-settings.php`, `admin/theme-marketplace.php`
- Fokus: Theme-Lifecycle, Sicherheit, Wartbarkeit, Editor-/Customizer-Workflow

## Positiv bewertet
- **Zentraler ThemeManager** für aktives Theme, Template-Fallbacks und Theme-Metadaten.
- **Admin-Tooling** für Theme-Verwaltung, Editor, Settings und Marketplace vorhanden.
- **Fallback-Logik** bei fehlendem aktivem Theme (`getActiveTheme()` sucht gültigen Ordner mit `style.css`).
- **Hook-Integration** erlaubt Erweiterung bei Theme-Load/Render-Phasen.

## Kritische / relevante Findings
1. **Repository-Snapshot ohne sichtbares `CMS/themes/`-Verzeichnis**
   - In der aktuellen Arbeitskopie ist kein Theme-Verzeichnis enthalten.
   - Risiko: Eingeschränkte Validierbarkeit des End-to-End-Theme-Lifecycles im aktuellen Stand.

2. **Theme-Operationen mit Dateisystembezug sind sicherheitskritisch**
   - Löschen/Aktivieren wird sanitisiert, bleibt aber ein High-Impact-Pfad.
   - Zusätzliche Schutzmechanismen (Audit-Log, Rollen-Granularität) sind für produktive Umgebungen essenziell.

3. **UI-/Flow-Konsistenz**
   - Theme-Verwaltung enthält Legacy-Muster (Inline-Styling, `window.confirm()`-Dialoge), die den Standardprozess fragmentieren.

## Empfehlungen (priorisiert)
### P1 – kurzfristig
- Theme-Lifecycle dokumentieren (Installieren, Aktivieren, Löschen, Restore).
- Für Theme-Löschungen verpflichtendes internes Confirm-Modal + Audit-Log-Eintrag.
- End-to-End-Validierung mit mindestens einem Referenz-Theme im Repo oder als Testfixture.

### P2 – mittelfristig
- Theme-Filesystem-Zugriffe zentral härten (Allowlist, path guards, atomare Operationen).
- Theme-Health-Check (vollständige Pflichtdateien, Syntaxcheck, Kompatibilitätsprüfung) vor Aktivierung.

### P3 – langfristig
- Versionierte Theme-Migrationen für Settings/Customizer-Daten.
- Signatur-/Vertrauensmodell für Marketplace-Themes (Integrität, Herkunft).

## Ergebnis (Ampel)
- **Gesamtstatus:** 🟡 **Funktional gut aufgestellt, Hardening & Lifecycle-Transparenz ausbauen**
- **Schwerpunkt:** Sichere Dateisystem-Operationen und konsistente Admin-UX.
