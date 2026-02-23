# 365CMS – Manueller Test: Theme-System

> **Scope:** Theme-Aktivierung, Editor-Sicherheit, Rollback, Vorschau  
> **Vorbedingung:** Admin-Zugang, Mindestens 2 Themes installiert  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. Vorbereitung

| Aufgabe | Erledigt |
|---|---|
| Admin eingeloggt | ☐ |
| Mindestens 2 Themes unter `CMS/themes/` vorhanden | ☐ |
| Aktuell aktives Theme bekannt | ☐ |
| Audit-Log-Tabelle existiert | ☐ |

---

## 2. Theme-Aktivierung

### TC-THEME-01 · Theme wechseln

**Vorgehen:**
1. Admin → Design → Themes
2. Inaktives Theme aktivieren

**Erwartetes Ergebnis:**
- Theme ist sofort aktiv (Frontend-Reload prüfen)
- Altes Theme deaktiviert
- Audit-Log: `theme.switch` mit altem und neuem Theme-Slug
- Kein PHP-Fehler auf Front- oder Backend

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Theme-Wechsel erfolgreich | ☐ | ☐ |
| Frontend mit neuem Theme | ☐ | ☐ |
| Audit-Log-Eintrag | ☐ | ☐ |

---

### TC-THEME-02 🔴 · Aktuell aktives Theme nicht löschbar

**Vorgehen:**
1. Aktives Theme → Löschen versuchen

**Erwartetes Ergebnis:**
- Fehlermeldung: „Aktives Theme kann nicht gelöscht werden"
- Theme bleibt erhalten

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Löschen verweigert | ☐ | ☐ |

---

### TC-THEME-03 · Inaktives Theme löschen

**Vorgehen:**
1. Inaktives Theme auswählen → Löschen

**Erwartetes Ergebnis:**
- Theme-Verzeichnis aus `CMS/themes/` entfernt
- Theme nicht mehr in Auswahlliste
- Audit-Log: `theme.delete`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Verzeichnis gelöscht | ☐ | ☐ |
| Audit-Log-Eintrag | ☐ | ☐ |

---

## 3. Theme-Editor-Sicherheit

### TC-THEME-04 🔴 · Path-Traversal im Theme-Editor verhindern

**Vorgehen (mit Burp Suite oder curl):**
```bash
# Manipulierten Dateinamen an den Editor senden
curl -X POST https://your-test-instance/admin/theme-editor.php \
  -d "theme=my-theme&file=../../../../config.php&content=EVIL" \
  -b "PHPSESSID=valid_admin_session" \
  -d "csrf_token=VALID_TOKEN"
```

**Erwartetes Ergebnis:**
- HTTP 403 oder Fehlermeldung
- `config.php` bleibt unverändert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Path-Traversal blockiert | ☐ | ☐ |
| config.php unverändert | ☐ | ☐ |

---

### TC-THEME-05 🔴 · Nur `.php`/`.css`/`.js`/`.html` im Editor bearbeitbar

**Vorgehen:**
1. Im Theme-Editor URL manuell auf eine `.env`-Datei oder System-Datei ändern

**Erwartetes Ergebnis:**
- Fehler: Dateityp nicht erlaubt
- Keine sensiblen Dateien editierbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Unerlaubte Dateitypen geblockt | ☐ | ☐ |

---

### TC-THEME-06 🟠 · PHP-Code-Änderung wird im Audit-Log erfasst

**Vorgehen:**
1. Gültige PHP-Template-Datei im Theme-Editor öffnen
2. Kleinen Kommentar hinzufügen und speichern

**Erwartetes Ergebnis:**
- Datei gespeichert
- Audit-Log: `theme.file_edit` mit Theme-Slug und Dateiname
- Eintrag enthält Admin-User-ID und IP

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Speichern erfolgreich | ☐ | ☐ |
| Audit-Log: `theme.file_edit` | ☐ | ☐ |

---

## 4. Theme-Rollback

### TC-THEME-07 🟠 · Rollback bei Theme mit Fatal-Error

**Vorgehen:**
1. Theme-Editor: Syntax-Fehler in `index.php` einbauen (z. B. fehlendes `;`)
2. Speichern
3. Frontend aufrufen

**Erwartetes Ergebnis:**
- CMS erkennt PHP-Fatal im Theme
- Automatischer Fallback auf Default-Theme oder letztes funktionierendes Theme
- Admin-Benachrichtigung / Log-Eintrag

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein weißer Bildschirm im Frontend | ☐ | ☐ |
| Fallback-Theme aktiv | ☐ | ☐ |
| Log-Eintrag vorhanden | ☐ | ☐ |

---

## 5. Theme-Installation via ZIP

### TC-THEME-08 🟠 · Gültiges Theme-ZIP installieren

**Vorgehen:**
1. Admin → Design → Theme hochladen
2. Gültiges `.zip` mit `index.json` hochladen

**Erwartetes Ergebnis:**
- Theme in Liste angezeigt
- Name, Version, Beschreibung korrekt geparst

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Installation erfolgreich | ☐ | ☐ |

---

### TC-THEME-09 🔴 · Theme mit PHP-Schadcode blockieren

**Vorgehen:**
1. ZIP mit `shell.php` (Payload: `<?php system($_GET['cmd']); ?>`) erstellen
2. Als Theme hochladen

**Erwartetes Ergebnis:**
- Upload abgelehnt
- Keine Dateien im `themes/`-Verzeichnis

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Schadcode blockiert | ☐ | ☐ |

---

## 6. Testprotokoll

| Datum | Tester | Umgebung | Ergebnis |
|---|---|---|---|
| | | | |

**Offene Punkte:**

<!-- Hier gefundene Probleme dokumentieren -->
