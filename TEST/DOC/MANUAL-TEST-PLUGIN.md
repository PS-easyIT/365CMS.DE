# 365CMS – Manueller Test: Plugin-System

> **Scope:** Plugin-Installation, Aktivierung/Deaktivierung, Lifecycle-Hooks, Security-Scan  
> **Vorbedingung:** Admin-Zugang aktiv, Test-Plugins unter `CMS/plugins/` vorhanden  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. Vorbereitung

| Aufgabe | Erledigt |
|---|---|
| Admin eingeloggt | ☐ |
| Mindestens 1 deaktiviertes Test-Plugin vorhanden | ☐ |
| Debug-Log aktiv (`logs/app.log` schreibbar) | ☐ |
| Audit-Log-Tabelle existiert (DB-Check) | ☐ |

---

## 2. Plugin-Installation via ZIP

### TC-PLUG-01 🟠 · Gültiges Plugin installieren

**Vorgehen:**
1. Admin → Plugins → „Plugin hochladen"
2. Gültiges `.zip` mit korrekter `index.json` hochladen

**Erwartetes Ergebnis:**
- Plugin erscheint in Liste als „Deaktiviert"
- `index.json` korrekt geparst (Name, Version, Beschreibung)
- Keine Fehlermeldung
- Audit-Log-Eintrag: `plugin.install`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Installation erfolgreich | ☐ | ☐ |
| Audit-Log-Eintrag vorhanden | ☐ | ☐ |

---

### TC-PLUG-02 🔴 · PHP-Schadcode in ZIP blockieren

**Vorgehen:**
1. ZIP erstellen, das `shell.php` mit `<?php system($_GET['cmd']); ?>` enthält
2. Hochladen versuchen

**Erwartetes Ergebnis:**
- Upload abgelehnt (Security-Scan schlägt an)
- Fehlermeldung: „Sicherheitsscan fehlgeschlagen" o. ä.
- Keine Dateien werden gespeichert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Schadcode-ZIP abgelehnt | ☐ | ☐ |

---

### TC-PLUG-03 🟠 · ZIP mit `exec()`-Calls blockieren

**Vorgehen:**
1. Plugin-PHP mit `exec('rm -rf /')` erstellen und als ZIP hochladen

**Erwartetes Ergebnis:**
- Security-Scan erkennt `exec`/`shell_exec`/`passthru`
- Upload abgelehnt

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Gefährliche Funktionen erkannt | ☐ | ☐ |

---

### TC-PLUG-04 🟡 · Ungültiges ZIP (kein index.json)

**Vorgehen:**
1. ZIP ohne `index.json` hochladen

**Erwartetes Ergebnis:**
- Fehlermeldung: fehlende Metadaten
- Plugin wird nicht installiert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Fehlermeldung korrekt | ☐ | ☐ |

---

## 3. Aktivierung & Deaktivierung

### TC-PLUG-05 🟠 · Plugin aktivieren

**Vorgehen:**
1. Deaktiviertes Plugin in Liste auswählen
2. „Aktivieren" klicken

**Erwartetes Ergebnis:**
- Status wechselt zu „Aktiv"
- Plugin-`activate`-Hook wird ausgeführt (falls implementiert)
- Audit-Log: `plugin.activate`
- Seite bleibt voll funktionsfähig (keine PHP-Fehler)

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Status „Aktiv" | ☐ | ☐ |
| Activate-Hook ausgeführt | ☐ | ☐ |
| Audit-Log-Eintrag | ☐ | ☐ |

---

### TC-PLUG-06 · Plugin deaktivieren

**Vorgehen:**
1. Aktives Plugin deaktivieren

**Erwartetes Ergebnis:**
- Status wechselt zu „Deaktiviert"
- Plugin-`deactivate`-Hook ausgeführt
- Audit-Log: `plugin.deactivate`
- Seite bleibt voll funktionsfähig

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Deaktivierung korrekt | ☐ | ☐ |

---

### TC-PLUG-07 🔴 · Plugin mit Fatal-Error automatisch deaktivieren

**Vorgehen:**
1. Test-Plugin erstellen, das beim Laden einen `E_ERROR` erzeugt
2. Plugin aktivieren

**Erwartetes Ergebnis:**
- CMS erkennt Fatal-Error in Plugin
- Plugin wird automatisch deaktiviert
- Fehlermeldung im Admin angezeigt
- Rest der CMS-Instanz bleibt erreichbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Auto-Deaktivierung bei Fatal | ☐ | ☐ |
| CMS nicht komplett ausgefallen | ☐ | ☐ |

---

## 4. Plugin-Abhängigkeiten

### TC-PLUG-08 🟡 · Abhängigkeits-Check

**Vorgehen:**
1. Plugin mit Abhängigkeit zu nicht aktiviertem Plugin aktivieren

**Erwartetes Ergebnis:**
- Fehlermeldung: „Abhängigkeit [Plugin-Name] nicht aktiv"
- Aktivierung abgelehnt

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Abhängigkeitsfehler angezeigt | ☐ | ☐ |

---

## 5. Plugin-Löschen

### TC-PLUG-09 🟠 · Aktives Plugin löschen verhindern

**Vorgehen:**
1. Aktives Plugin löschen versuchen

**Erwartetes Ergebnis:**
- Fehlermeldung: „Plugin muss erst deaktiviert werden"
- Plugin bleibt erhalten

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Löschen aktiver Plugins blockiert | ☐ | ☐ |

---

### TC-PLUG-10 · Deaktiviertes Plugin löschen

**Vorgehen:**
1. Deaktiviertes Plugin löschen

**Erwartetes Ergebnis:**
- Plugin-Verzeichnis aus `CMS/plugins/` entfernt
- Plugin nicht mehr in Liste
- Audit-Log: `plugin.delete`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Verzeichnis gelöscht | ☐ | ☐ |
| Audit-Log-Eintrag | ☐ | ☐ |

---

## 6. Testprotokoll

| Datum | Tester | Umgebung | Ergebnis |
|---|---|---|---|
| | | | |

**Offene Punkte:**

<!-- Hier gefundene Probleme dokumentieren -->
