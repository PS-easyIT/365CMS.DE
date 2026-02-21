# Subscription-System (Admin)

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/subscriptions.php`

Das Subscription-System ermöglicht die Verwaltung von Mitgliedschaftsplänen, Benutzer-Abonnements und zugehörigen Zugriffsbeschränkungen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Pläne verwalten](#2-pläne-verwalten)
3. [Benutzer-Abonnements](#3-benutzer-abonnements)
4. [Zahlungsverwaltung](#4-zahlungsverwaltung)
5. [Zugriffskontrolle](#5-zugriffskontrolle)
6. [Statistiken & Reports](#6-statistiken--reports)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/admin/subscriptions.php`

Das Subscription-System managt die Monetarisierung der 365CMS-Plattform. Es unterstützt:
- Monatliche und jährliche Abrechnungsintervalle
- Manuelles Billing (Banküberweisung, PayPal)
- Automatisches Ablauf-Management mit Grace Periods
- Granulare Feature-Kontrolle pro Plan

---

## 2. Pläne verwalten

### Plan erstellen/bearbeiten

| Feld | Typ | Beschreibung |
|---|---|---|
| `name` | Text | Anzeigename (z.B. „Pro", „Business") |
| `slug` | Text | Technischer Key (z.B. `pro`, `business`) |
| `price_monthly` | Dezimal | Monatspreis in EUR |
| `price_yearly` | Dezimal | Jahrespreis (oft Rabatt auf ×12) |
| `description` | Text | Kurzbeschreibung für Upgrade-Seite |
| `features` | JSON | Liste der enthaltenen Features (für Darstellung) |
| `limits` | JSON | Technische Limits (Speicher, API-Calls, etc.) |
| `is_active` | Bool | Plan in Upgrade-Auswahl anzeigen |
| `trial_days` | Int | Kostenlose Testphase (0 = keine) |
| `sort_order` | Int | Reihenfolge in der Planübersicht |

### Plan-Limits (JSON-Format)
```json
{
  "storage_mb": 10240,
  "api_calls_per_day": 1000,
  "max_projects": 10,
  "max_team_members": 5,
  "allowed_plugins": ["cms-experts", "cms-events"],
  "features": {
    "advanced_analytics": true,
    "white_label": false,
    "priority_support": true
  }
}
```

### Grandfathering
Wenn ein Plan geändert wird (z.B. Preiserhöhung), können bestehende Abonnenten den alten Preis behalten:
- Checkbox „Bestehende Abonnenten nicht betreffen"
- Änderung gilt nur für neue Abonnements

---

## 3. Benutzer-Abonnements

### Abonnements-Übersicht (Tabelle)

| Spalte | Beschreibung |
|---|---|
| Benutzer | Name + E-Mail, Link zur Benutzerverwaltung |
| Plan | Aktiver Plan-Name |
| Status | `active`, `expired`, `cancelled`, `pending`, `trial` |
| Start | Datum des Beginns |
| Ende / Verlängerung | Ablaufdatum |
| Betrag | Gezahlter/offener Betrag |
| Aktionen | Verlängern, Plan ändern, Kündigen, Stornieren |

### Abonnement manuell anlegen/ändern

1. Benutzer auswählen (Autocomplete mit E-Mail-Suche)
2. Plan wählen
3. Laufzeit: Start-/Enddatum manuell setzen
4. Notiz eingeben (intern, nicht für Benutzer sichtbar)
5. Speichern → Benutzer erhält E-Mail-Bestätigung

### Grace Period
Nach Ablauf: 7-tägige Kulanzfrist (konfigurierbar)  
Während Grace Period: Benutzer kann weiterverwenden, erhält tägliche Erinnerungen  
Nach Grace Period: Plan wird auf Free-Tier degradiert

---

## 4. Zahlungsverwaltung

365CMS verwendet manuelles Billing:

### Zahlung einbuchen
1. Abonnement in der Liste finden
2. „Zahlung manuell bestätigen" klicken
3. Betrag, Datum, Referenz-Nummer eingeben
4. Plan wird aktiviert/verlängert
5. Rechnung wird generiert und per E-Mail versandt

### Rechnungen

| Feld | Beschreibung |
|---|---|
| Rechnungsnummer | Auto-generiert: `INV-YYYY-NNNNN` |
| Betrag netto | Plan-Preis exkl. MwSt. |
| MwSt. | Standard 19% (konfigurierbar per Plan) |
| Betrag brutto | Endbetrag |
| Status | `paid`, `pending`, `cancelled`, `refunded` |

**PDF-Rechnung:** Automatisch generiert, als Download für Admin und Benutzer.

### Zahlungsinformationen konfigurieren
Unter `admin/settings.php` → Sektion „Zahlungsinformationen":
- Bankverbindung (IBAN, BIC, Kontoinhaber)
- PayPal-Adresse
- Zusätzliche Hinweistexte

---

## 5. Zugriffskontrolle

Das System prüft bei JEDEM Request auf geschützte Inhalte den Abo-Status:

```php
// In MemberService
public function hasAccess(int $userId, string $feature): bool {
    $sub = $this->getUserSubscription($userId);
    if (!$sub || $sub->status !== 'active') {
        return false; // Nur Free-Features
    }
    $limits = json_decode($sub->plan->limits, true);
    return $limits['features'][$feature] ?? false;
}
```

**Integration:**
- Alle Member-Seiten nutzen `MemberService::hasAccess()`
- Plugins prüfen via `cms_user_can_access` Hook
- REST-API-Endpoints prüfen via `permission_callback`

**Fehlerbehandlung:**
- Kein Zugriff → Weiterleitung zu `/member/subscription?upgrade=1`
- Toast-Notification: „Dieses Feature ist in Ihrem aktuellen Plan nicht enthalten."

---

## 6. Statistiken & Reports

**Übersichts-Dashboard:**
- Aktive Abonnenten gesamt (pro Plan aufgeteilt)
- Monatlich wiederkehrender Umsatz (MRR)
- Churn Rate (Kündigungsrate letzter 30 Tage)
- Neue Abonnements letzte 30 Tage

**Exportfunktion:**
- CSV-Export aller Abonnements (für Buchhaltung)
- Filterbar nach: Zeitraum, Plan, Status

---

## 7. Technische Details

**Klassen:**
- `CMS\SubscriptionManager` – Plan-Verwaltung
- `CMS\Services\MemberService` – User-Subscription-Zugriff
- `CMS\Services\InvoiceService` – Rechnungsgenerierung

**Datenbank-Tabellen:**

```sql
-- Pläne
CREATE TABLE cms_subscription_plans (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(50) UNIQUE NOT NULL,
    name          VARCHAR(100) NOT NULL,
    price_monthly DECIMAL(10,2) DEFAULT 0.00,
    price_yearly  DECIMAL(10,2) DEFAULT 0.00,
    limits        JSON,
    is_active     TINYINT(1) DEFAULT 1,
    sort_order    INT DEFAULT 0,
    trial_days    INT DEFAULT 0
);

-- Benutzer-Abonnements
CREATE TABLE cms_subscriptions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    plan_id    INT NOT NULL,
    status     ENUM('trial','active','expired','cancelled','pending') DEFAULT 'pending',
    starts_at  DATETIME,
    ends_at    DATETIME,
    notes      TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Hooks:**
```php
do_action('cms_subscription_created', $subId, $userId, $planId);
do_action('cms_subscription_activated', $subId, $userId, $planId);
do_action('cms_subscription_expired', $subId, $userId, $planId);
do_action('cms_subscription_cancelled', $subId, $userId, $planId);
add_filter('cms_subscription_price', 'my_discount_filter', 10, 3);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
