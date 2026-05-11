# Bestellungen & Zuweisung

Kurzbeschreibung: Dokumentiert die aktuelle Bestellverwaltung inklusive Statuspflege, manueller Paketzuweisung, read-only Renewal-Hinweisen, CSV-Exporten und Historie.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.738

---

## Route und Technik

- Route: `/admin/orders`
- Entry Point: `CMS/admin/orders.php`
- Modul: `CMS/admin/modules/subscriptions/OrdersModule.php`
- View: `CMS/admin/views/subscriptions/orders.php`

Die Seite bündelt zwei Aufgaben:

1. Bestellungen verwalten
2. Abos/Pakete manuell Benutzern zuweisen

POST-Aktionen laufen im aktuellen Stand über den CSRF-Kontext `admin_orders`.

Read-only CSV-Exporte für Bestellungen und Paketnutzung laufen bewusst **nicht** über eine neue POST-Route, sondern über einen separaten GET-Download-Pfad ohne CSRF- oder Sicherheitstoken in der URL.

Die read-only Historie läuft ebenfalls ohne neue Mutation: Sie nutzt ausschließlich einen begrenzten Auszug aus dem vorhandenen `audit_log` und bleibt bei Problemen mit dem Logspeicher fail-soft.

---

## KPI-Bereich

Die Oberfläche zeigt vier Kennzahlen:

- Gesamt
- Offen
- Bezahlt
- Umsatz

Die Werte werden direkt aus `OrdersModule::getData()` geliefert.

Zusätzlich zeigt die Seite seit `2.9.736` eine eigene read-only Sektion **„Ablaufwarnungen & Renewal-Hinweise“**.

Sie verdichtet fällige oder überfällige Mitgliedschaften aus `user_subscriptions`, ohne neue POST-Aktion:

- globales Hinweisfenster aus `notification_before_expiry`
- Renewal-Interpretation über `next_billing_date`, wenn Auto-Verlängerung aktiv ist
- Laufzeitende über `end_date`, wenn keine Auto-Verlängerung greift
- überfällige Verträge und Hinweise innerhalb des konfigurierten Fensters

Die Darstellung bleibt bewusst operativ und lesend. Es werden keine E-Mails verschickt und keine zusätzlichen Trackingdaten geschrieben.

---

## Bestellliste

Die Tabelle enthält aktuell unter anderem:

- Bestellung / Paketname
- Kunde
- Betrag inkl. optionaler Steueranzeige
- Status
- Zahlungsmethode
- Datum
- Aktionsmenü

Filterbar sind Bestellungen über den Status:

- `pending`
- `paid`
- `cancelled`
- `refunded`
- `failed`

## Exporte

Seit `2.9.737` bietet `/admin/orders` zwei direkte CSV-Exporte an:

1. **Orders CSV**
	- exportiert Bestellungen
	- übernimmt optional die aktuell gesetzte Statusfilterung
	- enthält nur die für den Admin-Alltag relevanten Bestell- und Kundenspalten, nicht aber rohe Kontakt- oder Adresspayloads

2. **Paketnutzung CSV**
	- exportiert die aktuelle `subscription_usage`
	- ergänzt den Export um den aktuellen Abo-/Plan-Kontext des Benutzers
	- zeigt Ressourcentyp, aktuellen Zählerstand, Limitwert, Restmenge und Limitstatus

Sicherheits- und Betriebsvertrag:

- read-only GET-Downloads ohne state-changing Aktion
- keine CSRF- oder Sicherheitstoken in URLs
- datensparsame Audit-Logs ohne unnötige PII im Logkontext
- CSV-Zellen werden gegen Spreadsheet-Formula-Injection gehärtet
- große Exportmengen werden fail-soft begrenzt, statt den Request unkontrolliert zu verlängern

---

## Historie

Seit `2.9.738` zeigt `/admin/orders` zusätzlich eine read-only Historie für die aktuell sichtbaren Bestellungen und Paketzuweisungen.

Abgedeckt werden insbesondere:

- Bestellstatuswechsel
- Bestelllöschungen
- manuelle Paketzuweisungen
- Orders- und Paketnutzungs-Exporte

Sicherheits- und Betriebsvertrag:

- keine neue GET- oder POST-Mutation
- nur begrenzter Auszug aus `audit_log`
- keine Ausgabe roher Audit-Metadaten, Kontakt-Payloads oder Tokenwerte
- generischer Warnhinweis statt technischem Fehlerdetail, falls das Audit-Log nicht lesbar ist
- Escape im View-Kontext, damit Log-Inhalte nicht zum XSS-/Log-Injection-Nachläufer werden

---

## Statusmodell

Im aktuellen Modul sind folgende Statuswerte zulässig:

| Status | Darstellung |
|---|---|
| `pending` | Offen |
| `paid` | Bezahlt |
| `cancelled` | Storniert |
| `refunded` | Erstattet |
| `failed` | Fehlgeschlagen |

Ältere Begriffe wie `completed` oder `partial_refund` gehören nicht zum aktuellen Kernstatusmodell dieser Seite.

---

## Paketzuweisung

Ein zentrales Merkmal der Seite ist die manuelle Paketzuweisung per Modal.

Pflichtfelder:

- Benutzer
- Paket
- Abrechnungsintervall

Unterstützte Intervalle:

- `monthly`
- `yearly`
- `lifetime`

Die Zuweisung wird intern über `CMS\SubscriptionManager::instance()->assignSubscription()` ausgeführt.

---

## Wichtige Datenbankbesonderheit

Die aktuelle Implementierung bevorzugt in der Bestelltabelle den Schlüssel:

- `plan_id`

Für ältere Installationen wird zusätzlich noch `package_id` berücksichtigt. Diese Kompatibilität ist wichtig für migrationsnahe Systeme.

---

## Unterstützte Aktionen

Die Seite verarbeitet derzeit insbesondere:

- Status ändern
- Bestellung löschen
- Paket aus Bestellung in die Zuweisungsmaske übernehmen
- direkte Paketzuweisung an Benutzer

Ein vollautomatisiertes Rechnungs-, Refund- oder Payment-Gateway-Backoffice ist hier nicht vollständig abgebildet.

---

## Verwandte Seiten

- [Abo-System](SUBSCRIPTION-SYSTEM.md)
- [Mitgliedschaften im Member-Bereich](../../member/general/SUBSCRIPTION.md)
- [Aboverwaltung – Überblick](../member/README.md)
