# Bestellungen & Zuweisung

Kurzbeschreibung: Dokumentiert die aktuelle Bestellverwaltung inklusive Statuspflege und manueller Paketzuweisung.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Route und Technik

- Route: `/admin/orders`
- Entry Point: `CMS/admin/orders.php`
- Modul: `CMS/admin/modules/subscriptions/OrdersModule.php`
- View: `CMS/admin/views/subscriptions/orders.php`

Die Seite bündelt zwei Aufgaben:

1. Bestellungen verwalten
2. Abos/Pakete manuell Benutzern zuweisen

---

## KPI-Bereich

Die Oberfläche zeigt vier Kennzahlen:

- Gesamt
- Offen
- Bezahlt
- Umsatz

Die Werte werden direkt aus `OrdersModule::getData()` geliefert.

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
