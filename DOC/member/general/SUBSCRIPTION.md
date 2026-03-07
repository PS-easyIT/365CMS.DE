# 365CMS – Abo-Verwaltung

Kurzbeschreibung: Detaildokumentation der Mitgliedschaftsseite unter `/member/subscription` mit aktuellem Paket, verfügbaren Plänen und Zahlungsinfos.

Letzte Aktualisierung: 2026-03-07

**Route:** `/member/subscription`

---

## Überblick

Die Subscription-View zeigt:

- aktuelles Abo, sofern vorhanden
- Zahlungsinformationen aus den Systemeinstellungen
- alle verfügbaren Pläne
- direkten Bestellaufruf über die Order-Route

---

## Aktuelles Paket

Wenn ein aktives Abo vorhanden ist, zeigt die Seite typischerweise:

- Paketname
- Status
- Preis und Billing Cycle
- Start- und Enddatum
- Limits, z. B. Experten-, Firmen- und Speicherlimits

Die Statusdarstellung arbeitet mit Badges wie `active`, `expired`, `pending` und `cancelled`.

---

## Verfügbare Pläne

Die Seite rendert `allPlans` und markiert das aktuelle Paket optisch.

Neue Bestellungen werden derzeit über Links der Form

- `/order?plan_id=<id>`

angestoßen.

---

## Zahlungsinformationen

Die View erwartet die Settings:

- `payment_info_bank`
- `payment_info_paypal`
- `payment_info_note`

Administrativ gepflegt werden diese Informationen im System-/Settings-Bereich.

---

## Wichtige Umstellung

Historische Verweise auf `admin/subscription-packages.php` sind veraltet. Für den aktuellen Admin-Stand gelten:

- `/admin/packages`
- `/admin/orders`
- `/admin/subscription-settings`

---

## Verwandte Dokumente

- [DASHBOARD.md](DASHBOARD.md)
- [../README.md](../README.md)
- [../../admin/subscription/SUBSCRIPTION-SYSTEM.md](../../admin/subscription/SUBSCRIPTION-SYSTEM.md)
