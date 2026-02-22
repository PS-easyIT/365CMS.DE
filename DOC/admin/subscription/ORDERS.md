# Bestellverwaltung & Abonnements

**Dateien:** `admin/orders.php`, `admin/subscription-settings.php`

---

## Übersicht

Die Subscription-/Bestellverwaltung steuert kostenpflichtige Mitgliedschaften, Transaktionen und Zahlungsanbieter.

---

## Bestellübersicht – `admin/orders.php`

### Dashboard-KPIs

| Kachel | Beschreibung |
|--------|--------------|
| **Umsatz (Monat)** | Gesamtumsatz im aktuellen Monat |
| **Aktive Abos** | Anzahl aktiver Subscriptions |
| **Neue Bestellungen** | Bestellungen der letzten 30 Tage |
| **Ausstehend** | Offene / unbestätigte Bestellungen |

### Bestelltabelle

| Spalte | Beschreibung |
|--------|--------------|
| **Bestell-ID** | Eindeutige Bestellnummer |
| **Benutzer** | Besteller (Name + E-Mail) |
| **Paket** | Abonnement-Paket-Name |
| **Betrag** | Zahlungsbetrag in EUR |
| **Zahlungsmethode** | Stripe, PayPal, SEPA, Überweisung |
| **Status** | Ausstehend / Bezahlt / Fehlgeschlagen / Erstattet |
| **Datum** | Bestellzeitpunkt |
| **Aktionen** | Anzeigen, Rechnung, Erstatten |

---

## Bestellstatus

| Status | Farbe | Beschreibung |
|--------|-------|--------------|
| `pending` | 🟡 Gelb | Warte auf Zahlung |
| `completed` | 🟢 Grün | Zahlung erfolgreich |
| `failed` | 🔴 Rot | Zahlung fehlgeschlagen |
| `refunded` | 🔵 Blau | Vollständig erstattet |
| `partial_refund` | 🟠 Orange | Teilweise erstattet |
| `cancelled` | ⚫ Grau | Storniert |

---

## Bestelldetails

### Anzeigen einer Bestellung
- Benutzerinformationen (Name, E-Mail, Adresse)
- Paketinformationen (Name, Laufzeit, Preis)
- Zahlungsdetails (Methode, Transaction-ID, Datum)
- Rechnung als PDF
- Rechnungsnummer (automatisch generiert)

### Rechnung generieren
- Automatisch beim Kauf als PDF erzeugt
- Enthält: Rechnungsnummer, Datum, Positionen, MwSt., Gesamtbetrag
- Logo und Firmendaten aus den [allgemeinen Einstellungen](../system-settings/README.md)

### Erstattung
1. Bestellung öffnen
2. "Erstatten" wählen
3. Betrag eingeben (Voll- oder Teilerstattung)
4. Grund eingeben
5. Bestätigen → API-Ruf an Zahlungsanbieter

---

## Abonnement-Einstellungen – `admin/subscription-settings.php`

### Pakete verwalten

| Paket-Feld | Beschreibung |
|------------|--------------|
| **Name** | Paket-Bezeichnung |
| **Beschreibung** | Kurztext für Checkout |
| **Preis** | Betrag in EUR |
| **Laufzeit** | Monatlich / Vierteljährlich / Jährlich / Einmalig |
| **Testphase** | Kostenlose Testphase in Tagen (0 = deaktiviert) |
| **Features** | Liste der enthaltenen Funktionen |
| **Benutzerrolle** | Welche Rolle nach Kauf zugewiesen wird |
| **Sichtbarkeit** | Öffentlich / Privat / Versteckt |

### Standard-Pakete

| Paket | Preis | Zielgruppe |
|-------|-------|------------|
| **Free** | 0€/Monat | Basisnutzer |
| **Basic** | 9,90€/Monat | Mitglieder |
| **Pro** | 29,90€/Monat | Professionelle |
| **Business** | 79,90€/Monat | Unternehmen |

---

## Zahlungsanbieter

| Anbieter | Status | Konfiguration |
|---------|--------|---------------|
| **Stripe** | Aktiv (empfohlen) | Öffentlicher + Privater Schlüssel |
| **PayPal** | Optional | Client-ID + Secret |
| **SEPA** | Optional | Bankdaten, Mandat-Verwaltung |
| **Überweisung** | Optional | Bankverbindung als Info-Text |

### Stripe-Konfiguration
```
Öffentlicher Schlüssel: pk_live_...
Privater Schlüssel:     sk_live_...
Webhook-URL:            https://deinedomain.de/cms/payment/webhook
Webhook-Secret:         whsec_...
```

---

## Statistiken & Auswertungen

| Report | Verfügbar als |
|--------|---------------|
| **Umsatz-Übersicht** | Tabelle + Chart |
| **Neue Abonnenten** | Tabelle + Chart |
| **Abonnement-Ablauf** | Tabelle (kommende 30 Tage) |
| **Umsatz nach Paket** | Kreisdiagramm |
| **Zahlungsfluss** | Zeitreihen-Chart |

---

## Datenbank

| Tabelle | Beschreibung |
|---------|--------------|
| `cms_orders` | Bestellungen |
| `cms_subscriptions` | Aktive Abonnements |
| `cms_subscription_packages` | Paket-Definitionen |
| `cms_payment_methods` | Gespeicherte Zahlungsmethoden |

### `cms_orders`

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `user_id` | INT | Käufer |
| `package_id` | INT | Paket-Referenz |
| `amount` | DECIMAL | Betrag |
| `currency` | VARCHAR | Währung (EUR) |
| `payment_method` | VARCHAR | stripe/paypal/sepa |
| `transaction_id` | VARCHAR | Anbieter-Transaktions-ID |
| `status` | ENUM | pending/completed/failed/refunded |
| `invoice_number` | VARCHAR | Rechnungsnummer |
| `created_at` | TIMESTAMP | Bestelldatum |

---

## Verwandte Seiten

- [Benutzerverwaltung](../users-groups/USERS.md)
- [Mitglieder](../member/README.md)
- [Analytics](../seo-performance/ANALYTICS.md)
