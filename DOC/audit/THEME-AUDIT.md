# THEME AUDIT 2026 – 365CMS (`/CMS`)

## 1) Prüfrahmen

**Ziel:** Bewertung des Theme-Stacks (Management, Editor, Customizer, Marketplace) nach Best Practices 2026 für Sicherheit, Wartbarkeit und Betrieb.  
**Methode:** statische Analyse der Theme-relevanten Core/Admin-Komponenten.

Geprüfte Stichproben:
- `CMS/core/ThemeManager.php`
- `CMS/core/Services/ThemeCustomizer.php`
- `CMS/admin/themes.php`
- `CMS/admin/theme-editor.php`
- `CMS/admin/theme-settings.php`
- `CMS/admin/theme-marketplace.php`

---

## 2) Executive Summary

**Gesamtstatus:** 🟡 **Funktional stark, Lifecycle- und Sicherheits-Hardening empfohlen.**

Stärken:
- Zentrale Theme-Logik im Core.
- Admin-Werkzeuge für Aktivierung, Settings, Editor, Marketplace vorhanden.
- Fallback-Mechanismus für aktives Theme implementiert.

Risiken:
- Dateisystemnahe Aktionen sind high impact und brauchen maximalen Guard.
- Legacy-UI-Patterns (`window.confirm`) in Theme-nahen Aktionen.

---

## 3) Positive Befunde

1. **`ThemeManager` kapselt Kernlogik** (active theme, Fallbacks, Theme-Load).  
2. **Theme-Customizer als Service** vorhanden, also technisch erweiterbar.  
3. **Admin-Workflow vollständig abgedeckt** (Verwaltung bis Marketplace).

---

## 4) Findings

### P1-HIGH: Theme-Operationen brauchen stärkere Defense-in-Depth
- Evidenz: Admin-Aktionen in `themes.php` (aktivieren/löschen), zusätzlich clientseitige Confirm-Dialoge.
- Wirkung: Bei Fehlkonfigurationen potenziell riskante Dateisystem-Eingriffe.
- Empfehlung 2026:
  - Mehrstufige Validierung (Capability + CSRF + serverseitiger Intent-Token + Audit-Log).

### P1-MEDIUM: Confirm-Mechanik noch teilweise `window.confirm()`
- Evidenz: `CMS/admin/themes.php` sowie weitere Admin-Seiten.
- Wirkung: inkonsistente UX und geringere Prozesshärte.
- Empfehlung:
  - Einheitliches Modal-Framework mit nachvollziehbarer Serverbestätigung.

### P2-MEDIUM: Theme-Lifecycle-Dokumentation technisch ausbaufähig
- Evidenz: Funktionsumfang groß, aber End-to-End-Hardening-Checklisten nicht zentral.
- Wirkung: Betriebsrisiko bei Teamwechsel/Incident-Fällen.
- Empfehlung:
  - Runbook für Installieren/Aktivieren/Rollback/Recovery.

---

## 5) Best Practice 2026 (Theme/Design)

- **Filesystem Guard:** canonical path, symlink-blocking, allowlist für bearbeitbare Dateien.
- **Activation Health Check:** Pflichtdateien, Syntaxcheck, Kompatibilitätsprüfung vor Aktivierung.
- **Versionierte Theme-Settings:** Migrationen bei Schemaänderungen im Customizer.
- **Marketplace-Trust-Modell:** Integritätsprüfung, Herkunft, Version-Pinning.

---

## 6) Maßnahmenplan

### 0–30 Tage
- Theme-Aktionen auf einheitliches servergesichertes Confirm-Modell.
- Audit-Logging für Aktivierung/Löschung und Fehlversuche.

### 31–90 Tage
- Theme-Health-Checks vor Aktivierung implementieren.
- Rollback-Mechanismus dokumentieren und testen.

### >90 Tage
- Signatur-/Trust-Validierung für externe Theme-Pakete.

---

## 7) Abschlussbewertung

**Theme-Reifegrad:** **B**  
**Urteil:** Starker Funktionsumfang, aber produktiv sollte der Lifecycle noch systematischer abgesichert werden.
