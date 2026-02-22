# Rechtstexte & Legal-Sites

**Datei:** `admin/legal-sites.php`

---

## Übersicht

Verwaltung aller rechtlich erforderlichen Dokumente: Impressum, Datenschutzrichtlinie, AGB, Widerrufsbelehrung und Cookie-Richtlinie. Texte werden in CMS-Seiten gespeichert und können mit dem integrierten Editor bearbeitet werden.

---

## Pflicht-Dokumente (DE)

| Dokument | Gesetzliche Basis | Pflicht für |
|----------|-------------------|-------------|
| **Impressum** | § 5 TMG, § 55 RStV | Alle kommerziellen Websites |
| **Datenschutzerklärung** | Art. 13 DSGVO | Alle Websites mit Datenverarbeitung |
| **AGB** | § 305 BGB | Onlineshops, kostenpflichtige Dienste |
| **Widerrufsbelehrung** | § 355 BGB | Verbraucherverträge |
| **Cookie-Richtlinie** | § 25 TTDSG | Bei Einsatz von Tracking-Cookies |

---

## Rechtstexte verwalten

### Bearbeitung
1. **Dokument wählen** – Tabs: Impressum / Datenschutz / AGB / Widerruf / Cookies
2. **Editor** – WYSIWYG-Editor (SunEditor) für komfortable Bearbeitung
3. **Verknüpfte Seite** – Dropdown: Welche CMS-Seite enthält diesen Text?
4. **Speichern** – Text wird in der CMS-Seite aktualisiert

### Felder je Dokument

**Impressum:**
- Unternehmensname
- Anschrift (Straße, PLZ, Ort, Land)
- Vertreter / Geschäftsführer
- Kontakt (Telefon, E-Mail)
- Handelsregister / USt-IdNr. (optional)
- Redaktionell Verantwortlicher (bei redaktionellen Inhalten)

**Datenschutzerklärung (Generator):**
- Kontaktdaten des Verantwortlichen
- Hosting-Anbieter
- Verwendete Dienste (aus [Cookie-Manager](COOKIES.md) befüllbar)
- Rechte der Betroffenen (Art. 15-22 DSGVO)
- Kontaktdaten Datenschutzbeauftragter (falls vorhanden)

---

## Automatische Verknüpfung

Der Legal-Sites-Verwalter kann Dokumente automatisch in Footer-Links einbinden:
- Fußzeilen-Navigation wird automatisch ergänzt
- Meta-Tags (`<link rel="policy">`) werden gesetzt
- CMS-Seiten als "nicht löschbar" markiert

---

## Versions-History

Alle Änderungen an Rechtstexten werden versioniert:

| Spalte | Beschreibung |
|--------|--------------|
| Version | Fortlaufende Nummer |
| Datum | Zeitpunkt der Änderung |
| Geändert von | Admin-Name |
| Änderung | Beschreibung der Änderung |
| Archiv | Download als PDF |

---

## Datenbank

| Tabelle | Beschreibung |
|---------|--------------|
| `cms_legal_documents` | Rechtstext-Versionen |
| `cms_pages` | Enthält die verknüpften Seiten (published, protected) |

---

## Verwandte Seiten

- [DSGVO Compliance](DSGVO.md)
- [Cookie-Manager](COOKIES.md)
- [Seiten verwalten](../pages-posts/PAGES.md)
