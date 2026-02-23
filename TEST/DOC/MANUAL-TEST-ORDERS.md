# 365CMS – Manueller Test: Bestellungen & Checkout

> **Scope:** Bestellformular, Validierung, DB-Integrität, Statusverwaltung  
> **Vorbedingung:** Admin + Member-Account, Bestellfunktion aktiv  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. Vorbereitung

| Aufgabe | Erledigt |
|---|---|
| Member-Account eingeloggt | ☐ |
| Admin-Account für Status-Verwaltung bereit | ☐ |
| DB-Zugang für Direktprüfung verfügbar | ☐ |
| Bestelltabelle (`orders`) vorhanden | ☐ |

---

## 2. Bestellformular

### TC-ORD-01 · Gültige Bestellung aufgeben

**Vorgehen:**
1. Als eingeloggter Member zum Bestellformular navigieren
2. Alle Pflichtfelder korrekt ausfüllen (Name, E-Mail, Produkt, Menge)
3. Bestellung absenden

**Erwartetes Ergebnis:**
- Bestätigungsmeldung erscheint
- Bestellung in DB unter `orders` gespeichert
- Status initial: `pending`
- Keine sensiblen Daten (z. B. Passwörter, CSRF-Token) in Response

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Bestätigung angezeigt | ☐ | ☐ |
| DB-Eintrag mit Status `pending` | ☐ | ☐ |

---

### TC-ORD-02 · Pflichtfelder-Validierung

**Vorgehen:**
1. Bestellformular mit leerem Namen absenden
2. Bestellformular mit ungültiger E-Mail absenden
3. Bestellformular mit Menge 0 absenden

**Erwartetes Ergebnis:**
- Fehlermeldung für jedes fehlerhafte Feld
- Kein Eintrag in DB

| Feld | Fehler korrekt angezeigt |
|---|---|
| Leerer Name | ☐ |
| Ungültige E-Mail | ☐ |
| Menge = 0 | ☐ |

---

### TC-ORD-03 🔴 · CSRF-Schutz im Bestellformular

**Vorgehen:**
1. Bestellformular-Submit-Request in Burp Suite abfangen
2. `csrf_token` entfernen oder manipulieren
3. Request erneut senden

**Erwartetes Ergebnis:**
- HTTP 403 oder Fehlermeldung „Sicherheitscheck"
- Keine Bestellung in DB

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| CSRF-Token verwertet | ☐ | ☐ |
| Kein DB-Eintrag | ☐ | ☐ |

---

### TC-ORD-04 🔴 · SQL-Injection im Bestellformular

**Vorgehen:**
Folgende Werte in Formularfelder eingeben:

| Feld | Payload |
|---|---|
| Name | `'; DROP TABLE orders; --` |
| E-Mail | `test@test.com' OR 1=1 --` |

**Erwartetes Ergebnis:**
- Kein DB-Fehler in Response
- `orders`-Tabelle unverändert
- Eingabe wird escaped gespeichert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein SQL-Injection-Erfolg | ☐ | ☐ |

---

### TC-ORD-05 🟠 · XSS im Bestellformular

**Vorgehen:**
1. `<script>alert('xss')</script>` als Name eingeben
2. Bestellung abschicken
3. Bestellung in Admin-Ansicht aufrufen

**Erwartetes Ergebnis:**
- Kein Alert erscheint
- Wert korrekt escaped in Admin-Ansicht: `&lt;script&gt;...`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein Script-Ausführung | ☐ | ☐ |
| Korrekt escaped in Admin | ☐ | ☐ |

---

## 3. Bestellstatus-Verwaltung (Admin)

### TC-ORD-06 · Bestellstatus ändern

**Vorgehen:**
1. Als Admin zur Bestellübersicht navigieren
2. Status einer Bestellung von `pending` auf `completed` ändern

**Erwartetes Ergebnis:**
- Status in DB aktualisiert
- Änderung sofort in Übersicht sichtbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Status Änderung persistiert | ☐ | ☐ |

---

### TC-ORD-07 🟠 · Nur Admin kann Status ändern

**Vorgehen:**
1. Als normaler Member-User einloggen
2. POST-Request an Orders-Endpunkt mit Status-Änderung senden

**Erwartetes Ergebnis:**
- HTTP 403 oder Redirect
- Status bleibt unverändert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Zugriff verweigert | ☐ | ☐ |

---

## 4. Bestellhistorie

### TC-ORD-08 · Member sieht nur eigene Bestellungen

**Vorgehen:**
1. Als Member einloggen (User A)
2. Direkter Aufruf einer Bestellung von User B per URL:  
   `?id=<bekannte_ID_von_User_B>`

**Erwartetes Ergebnis:**
- HTTP 403 oder 404
- Kein Zugriff auf fremde Bestellungen

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Horizontale Zugriffskontrolle OK | ☐ | ☐ |

---

## 5. DB-Integrität

### TC-ORD-09 · Bestellung mit nicht-existierender Produkt-ID

**Vorgehen:**
1. POST-Request mit `product_id=99999999` (nicht vorhanden) senden

**Erwartetes Ergebnis:**
- Fehlermeldung: „Produkt nicht gefunden"
- Keine Bestellung in DB

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Validierung greift | ☐ | ☐ |

---

## 6. Testprotokoll

| Datum | Tester | Umgebung | Ergebnis |
|---|---|---|---|
| | | | |

**Offene Punkte:**

<!-- Hier gefundene Probleme dokumentieren -->
