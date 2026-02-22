# CORE AUDIT 2026 – 365CMS (`/CMS/core`)

## 1) Prüfrahmen

**Ziel:** Bewertung der Kernarchitektur gegen Best Practices 2026 für PHP 8.3+, modulare CMS-Kerne und erweiterbare Runtime-Systeme.  
**Methode:** statische Architekturprüfung.

Geprüfte Stichproben:
- `CMS/core/Bootstrap.php`
- `CMS/core/Database.php`
- `CMS/core/Security.php`
- `CMS/core/Router.php`
- `CMS/core/Hooks.php`
- `CMS/core/PluginManager.php`
- `CMS/core/ThemeManager.php`

---

## 2) Executive Summary

**Gesamtstatus:** 🟢 **Architektonisch solide Kernbasis mit klaren Erweiterungspunkten.**

Stärken:
- Klar erkennbare Kernkomponenten (Auth, Security, DB, Router, Hooks).
- Plugin-/Theme-System organisatorisch sauber eingebunden.
- Strict Types in Core-Dateien vorhanden.

Verbesserungsfelder:
- Initialisierung stärker modularisieren.
- Side-Effects besser steuerbar machen.
- Betriebsmodi (web/admin/cli) explizit modellieren.

---

## 3) Positive Architekturmerkmale

1. **Hook-getriebene Erweiterbarkeit** via Actions/Filters.  
2. **Service-orientierte Core-Struktur** mit klaren Klassenverantwortungen.  
3. **Fallback-Konstanten** in `Bootstrap::ensureConstants()` erhöhen Robustheit in heterogenen Umgebungen.

---

## 4) Findings

### P1-MEDIUM: Bootstrap als zentraler Orchestrator mit hoher Kopplung
- Evidenz: `CMS/core/Bootstrap.php` initialisiert DB, Security, Auth, Router, Plugins, Themes, Subscription in einem Pfad.
- Wirkung: Erschwerte Testbarkeit und eingeschränkte Teilstarts.
- Empfehlung 2026:
  - Initialisierung in Phasen zerlegen (Kernel-Phasen + Boot-Profile).

### P1-MEDIUM: Frühes Laden von Plugins/Themes im Standard-Startpfad
- Evidenz: `loadPlugins()` und `loadTheme()` im Core-Init.
- Wirkung: Startkosten und Fehlereinfluss steigen.
- Empfehlung:
  - Lazy/Conditional Init für nicht-kritische Komponenten.

### P2-MEDIUM: Schema- und Runtime-Themen sind eng verbunden
- Evidenz: DB-Instanz ist direkt Teil des Startpfads; Schema-Migrationsthemen nahe am Runtime-Layer.
- Wirkung: Operative Pfade und Setup-/Migrationsthemen weniger klar getrennt.
- Empfehlung:
  - Install/Upgrade-Pipeline explizit vom Request-Lifecycle trennen.

### P2-LOW: Einzelne Core-Helfer mit Error Suppression (`@`)
- Evidenz: mehrere Stellen (z. B. mkdir/unlink/file_get_contents im Projekt).
- Wirkung: Diagnostik erschwert.
- Empfehlung:
  - Einheitliches Exception-/Logging-Konzept ohne Error Suppression.

---

## 5) Best-Practice-Zielbild 2026 (Core)

- **Kernel Modes:** frontend/admin/cli/maintenance.
- **Dependency Injection/Factory Layer:** bessere Austauschbarkeit & Testbarkeit.
- **Policy-basierte Security Guards:** zentrale Durchsetzung statt Seitenlogik.
- **Observability:** strukturierte Logs, Korrelations-ID, Metriken pro Kernkomponente.

---

## 6) Maßnahmenplan

### 0–30 Tage
- Bootstrap-Phasen dokumentieren und als internen Contract festhalten.
- Nichtkritische Initialisierungen hinter Guards verschieben.

### 31–90 Tage
- Lightweight DI/Service-Factory einführen.
- Setup-/Migration-Layer aus Runtime-Pfad entkoppeln.

### >90 Tage
- Architekturtests (Layer-Regeln) und Kernel-Modes vollständig etablieren.

---

## 7) Abschlussbewertung

**Core-Reifegrad:** **B+**  
**Urteil:** Gute Basis für Wachstum; Fokus jetzt auf Entkopplung, Betriebsprofile und testbare Initialisierung.
