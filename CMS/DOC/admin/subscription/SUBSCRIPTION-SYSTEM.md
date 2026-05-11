# Abo-System

Kurzbeschreibung: Überblick über die aktuelle Aboarchitektur mit Paketen, Limits, Zuweisungen, Exporten, Historie und dem Member-Bezug.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.738

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
| `subscription_limits_enabled` | Paketlimits systemweit durchsetzen | `1` |
| `subscription_default_plan_id` | Standardpaket für neue Mitglieder | `0` |
| `subscription_member_area_enabled` | Abo-Bereich im Member-Dashboard | `1` |
| `subscription_ordering_enabled` | Bestell-/Upgrade-Prozesse zulassen | `1` |
| `subscription_public_pricing_enabled` | Pakete öffentlich kommunizieren | `1` |
| `subscription_disabled_notice` | Hinweistext bei Deaktivierung | – |

### Package Settings (15 Optionen)

| Key | Zweck | Default |
|---|---|---|
| `subscription_enabled` | Abo-System aktiv | `0` |
| `trial_enabled` | Trial-Phase aktivieren | `0` |
| `trial_days` | Dauer Trial in Tagen | `14` |
| `auto_renewal` | Auto-Verlängerung | `1` |
| `grace_period_days` | Karenzzeit nach Ablauf | `3` |
| `cancellation_period_days` | Kündigungsfrist | `0` |
| `payment_methods` | Erlaubte Zahlungsmethoden | `invoice` |
| `invoice_prefix` | Rechnungsnummer-Prefix | `INV-` |
| `invoice_next_number` | Nächste Rechnungsnummer | `1001` |
| `tax_rate` | Steuersatz (%) | `19` |
| `tax_included` | Preise inklusive MwSt. | `1` |
| `notification_before_expiry` | Vorwarnung vor Ablauf in Tagen | `7` |
| `notification_email` | Zieladresse für Abo-Hinweise | – |
| `terms_page_id` | AGB-Seite (Page-ID) | `0` |
| `cancellation_page_id` | Widerrufsseite (Page-ID) | `0` |

Preislogik, Trial, Steuern und Paketdetails werden im Package-Settings-Tab gepflegt, nicht bei General Settings.

### Laufzeitvertrag für Ablaufwarnungen seit 2.9.736

Die bestehende Einstellung `notification_before_expiry` ist nicht länger nur dekorativ gespeichert.

Aktueller Vertragsstand:

- `SubscriptionManager` normalisiert Renewal-/Ablaufhinweise zentral für Admin und Member
- als Fälligkeitsdatum gilt bevorzugt `next_billing_date`, sonst `end_date`
- bei aktivierter globaler Auto-Verlängerung wird das Fälligkeitsdatum als Renewal-Termin interpretiert
- bei deaktivierter Auto-Verlängerung oder fehlendem Renewal-Termin wird dasselbe Datum als Laufzeitende behandelt
- `/admin/orders` zeigt read-only die fälligen bzw. überfälligen Verträge im konfigurierten Hinweisfenster
- `/member/subscription` nutzt denselben Vertrag für den persönlichen Hinweistext
- der Ausbau bleibt bewusst ohne neue POST-Route, ohne Mailversand und ohne zusätzliche Trackingtabelle

### Exportvertrag seit 2.9.737

`/admin/orders` ergänzt den operativen Abo-Pfad um zwei read-only CSV-Exporte:

- **Orders CSV** für Bestellungen, optional mit aktiver Statusfilterung
- **Paketnutzung CSV** auf Basis von `subscription_usage` plus aktuellem Abo-/Plankontext

Der Vertrag bleibt bewusst defensiv:

- GET-only Download ohne state-changing Aktion
- keine CSRF- oder Sicherheitstoken in der URL
- CSV-Zellhärtung gegen Spreadsheet-Formula-Injection
- fail-softe Begrenzung großer Exportmengen statt unkontrolliert langer Requests
- datensparsame Audit-Einträge ohne unnötige Export-Payloads im Log

### Historienvertrag seit 2.9.738

Die Aboverwaltung zeigt jetzt zusätzlich begrenzte read-only Historien auf Basis des vorhandenen `audit_log`:

- `/admin/orders` für sichtbare Bestellungen, Paketzuweisungen und Exporte
- `/admin/packages` für paketbezogene Änderungsereignisse

Der Vertrag bleibt bewusst konservativ:

- keine neue Mutation oder Spezialroute nur für Historie
- Ausgabe nur als begrenzter, escaped View-Auszug
- keine rohen Audit-Metadaten, Kontakt-Payloads oder Tokenwerte im UI
- fail-soft bei nicht verfügbarem Audit-Log statt Full-Page-Fehler

### Laufzeitvertrag des Standardpakets seit 2.9.621

Das unter `subscription_default_plan_id` gespeicherte Standardpaket wirkt jetzt direkt auf neue Mitglieder:

- öffentliche Registrierungen über `CMS\Auth::register()`
- neu im Admin angelegte Member-Konten über `CMS\Services\UserService::createUser()`

Die Zuweisung läuft zentral über `CMS\SubscriptionManager::assignConfiguredDefaultPlan()`.

Wichtige Details:

- nur **aktive** referenzierte Pakete werden automatisch zugewiesen
- bestehende aktive oder Trial-Abos werden nicht still überschrieben
- ist kein Standardpaket konfiguriert, bleibt die Registrierung ohne Zusatzmutation erfolgreich
- stale oder deaktivierte Paket-IDs führen fail-soft dazu, dass kein Default-Abo angelegt wird

---

## Beziehung zum Member-Bereich

Das Abo-System ist im Member-Bereich sichtbar, wenn entsprechende Optionen aktiv sind.

Wichtige Bezugspunkte:

- Member-Navigation kann den Bereich `subscription` anzeigen
- der Member-Bereich verlinkt auf Bestell-/Upgrade-Flows wie `/order?plan_id=...`
- Admin-Einstellungen können den Abo-Bereich im Member Dashboard ein- oder ausblenden
- Laufzeit- und Renewal-Hinweise für das aktive Paket werden dort seit `2.9.736` zentral aus echten Vertragsdaten statt aus dekorativen View-Feldern abgeleitet

---

## Typische Arbeitsabläufe

### Neues Standardpaket für Registrierungen festlegen

1. `/admin/subscription-settings` öffnen
2. Standardpaket auswählen
3. speichern
4. neue öffentliche Registrierungen und neu im Admin angelegte Member-Konten erhalten das aktive Paket automatisch

### Paket manuell zuweisen

1. `/admin/orders` öffnen
2. „Zuweisen“ verwenden
3. Benutzer, Paket und Abrechnungsintervall wählen

### Bestellungen oder Paketnutzung exportieren

1. `/admin/orders` öffnen
2. optional Statusfilter setzen
3. `Orders CSV` oder `Paketnutzung CSV` verwenden

### Historie prüfen

1. `/admin/orders` oder `/admin/packages` öffnen
2. read-only Historienblock prüfen
3. Änderungen, Exporte oder Paketereignisse ohne Rohdaten-Einsicht nachvollziehen

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
