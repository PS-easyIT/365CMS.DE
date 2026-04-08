# Abo-System

Kurzbeschreibung: Überblick über die aktuelle Aboarchitektur mit Paketen, Limits, Zuweisungen und dem Member-Bezug.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Das Abo-System in 365CMS besteht aus drei zentralen Admin-Bausteinen:

- `/admin/packages`
- `/admin/orders`
- `/admin/subscription-settings`

Es verbindet Paketdefinitionen, manuelle oder prozessgesteuerte Zuweisungen und eine systemweite Limitlogik.

---

## Aktuelle Admin-Struktur

Bereich **Aboverwaltung** im Sidebar:

| Route | Seite | Modul |
|---|---|---|
| `/admin/packages` | Pakete & Pläne | `PackagesModule` |
| `/admin/orders` | Bestellungen & Zuweisung | `OrdersModule` |
| `/admin/subscription-settings` | Einstellungen | `SubscriptionSettingsModule` |

---

## Zentrale Datenbereiche

### Paketdefinitionen

Paketdaten werden über die Subscription-Plan-Struktur verwaltet. Relevante Bezüge im Code und in Views zeigen insbesondere:

- `subscription_plans`
- `user_subscriptions`
- settings-basierte globale Abo-Konfiguration

### Bestellungen

Bestellungen werden über die Tabelle `orders` geführt. Wichtig:

- der kanonische Fremdschlüssel ist `plan_id`
- alte Installationen können noch `package_id` enthalten
- die aktuelle Implementierung behandelt beides kompatibel

### Limits und Sichtbarkeit

Globale Schalter steuern, ob Paketlimits systemweit überhaupt erzwungen werden.

---

## Globale Einstellungen

Die Seite `/admin/subscription-settings` verwaltet zwei Bereiche über Tabs:

### General Settings (6 Optionen)

| Key | Zweck | Default |
|---|---|---|
| `subscription_limits_enabled` | Paketlimits systemweit durchsetzen | `0` |
| `subscription_default_plan` | Standardpaket für neue Mitglieder | – |
| `subscription_member_area` | Abo-Bereich im Member-Dashboard | `0` |
| `subscription_ordering_enabled` | Bestell-/Upgrade-Prozesse zulassen | `0` |
| `subscription_public_pricing_enabled` | Pakete öffentlich kommunizieren | `0` |
| `subscription_disabled_notice` | Hinweistext bei Deaktivierung | – |

### Package Settings (15 Optionen)

| Key | Zweck | Default |
|---|---|---|
| `subscription_enabled` | Abo-System aktiv | `0` |
| `subscription_trial_enabled` | Trial-Phase aktivieren | `0` |
| `subscription_trial_days` | Dauer Trial in Tagen | `14` |
| `subscription_auto_renewal` | Auto-Verlängerung | `1` |
| `subscription_grace_period_days` | Karenzzeit nach Ablauf | `3` |
| `subscription_cancellation_period_days` | Kündigungsfrist | `0` |
| `subscription_payment_methods` | Erlaubte Zahlungsmethoden | – |
| `subscription_invoice_prefix` | Rechnungsnummer-Prefix | – |
| `subscription_invoice_next_number` | Nächste Rechnungsnummer | – |
| `subscription_tax_rate` | Steuersatz (%) | `0` |
| `subscription_notification_*` | E-Mail-Benachrichtigungen | – |
| `subscription_terms_page` | AGB-Seite (Page-ID) | – |
| `subscription_cancellation_page` | Widerrufsseite (Page-ID) | – |

Preislogik, Trial, Steuern und Paketdetails werden im Package-Settings-Tab gepflegt, nicht bei General Settings.

---

## Beziehung zum Member-Bereich

Das Abo-System ist im Member-Bereich sichtbar, wenn entsprechende Optionen aktiv sind.

Wichtige Bezugspunkte:

- Member-Navigation kann den Bereich `subscription` anzeigen
- der Member-Bereich verlinkt auf Bestell-/Upgrade-Flows wie `/order?plan_id=...`
- Admin-Einstellungen können den Abo-Bereich im Member Dashboard ein- oder ausblenden

---

## Typische Arbeitsabläufe

### Neues Standardpaket für Registrierungen festlegen

1. `/admin/subscription-settings` öffnen
2. Standardpaket auswählen
3. speichern

### Paket manuell zuweisen

1. `/admin/orders` öffnen
2. „Zuweisen“ verwenden
3. Benutzer, Paket und Abrechnungsintervall wählen

### Limits global deaktivieren

1. `/admin/subscription-settings` öffnen
2. Schalter „Abo-Limits systemweit durchsetzen“ deaktivieren
3. speichern

---

## Verwandte Seiten

- [Pakete & Pläne](PACKAGES.md)
- [Bestellungen & Zuweisung](ORDERS.md)
- [Member Dashboard](../member/README.md)
- [Benutzer & Gruppen](../users-groups/README.md)
