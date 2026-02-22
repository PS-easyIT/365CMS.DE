# Abo-Verwaltung


Der Abo-Bereich zeigt den aktuellen Mitgliedschaftsplan und ermöglicht Upgrades, Downgrades sowie den Zugriff auf Rechnungen und Zahlungsinformationen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Aktueller Plan](#2-aktueller-plan)
3. [Verfügbare Pakete](#3-verfügbare-pakete)
4. [Upgrade / Downgrade](#4-upgrade--downgrade)
5. [Zahlungsoptionen](#5-zahlungsoptionen)
6. [Rechnungen & History](#6-rechnungen--history)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/member/subscription`

Mitglieder sehen ihren aktuellen Plan und können direkt upgraden. Kein automatischer Zahlungseinzug – 365CMS verwendet manuelles Billing.

---

## 2. Aktueller Plan

| Information | Beispiel |
|---|---|
| **Plan-Name** | „Pro" |
| **Status** | Aktiv / Abgelaufen / Pending / Gekündigt |
| **Laufzeit** | Monatlich / Jährlich |
| **Nächste Abrechnung** | 21. März 2026 |
| **Ablaufdatum** | 21. März 2026 (oder „Unbegrenzt") |
| **Features** | Liste freigeschalteter Funktionen |

**Status-Badges:**
```php
$statusBadges = [
    'active'    => 'success',   // Grün
    'expired'   => 'danger',    // Rot
    'pending'   => 'warning',   // Gelb
    'cancelled' => 'secondary', // Grau
];
```

---

## 3. Verfügbare Pakete

Pakete werden über `SubscriptionManager::getAllPlans()` geladen:

| Paket | Preis | Intervall | Highlights |
|---|---|---|---|
| **Free** | 0 € | – | Basis-Funktionen, 100 MB Speicher |
| **Starter** | 9,90 € | Monatlich | 1 GB Speicher, Nachrichten |
| **Pro** | 24,90 € | Monatlich | 10 GB Speicher, alle Plugins |
| **Business** | 49,90 € | Monatlich | Unbegrenzt, API-Zugang, White-Label |

*Pakete werden im Admin unter `admin/subscription-packages.php` verwaltet.*

---

## 4. Upgrade / Downgrade

### Upgrade-Prozess
1. Gewünschtes Paket auswählen → „Jetzt upgraden"
2. Zahlungsinformationen werden angezeigt (Banküberweisung oder PayPal)
3. Zahlung manuell durchführen
4. Admin bestätigt → Plan wird aktiviert
5. Bestätigungs-E-Mail wird gesendet

### Downgrade
- Zum Ende der aktuellen Laufzeit möglich
- Restwert wird bei Sofort-Downgrade nicht erstattet
- Automatische Benachrichtigung 7 Tage vor Ablauf

---

## 5. Zahlungsoptionen

Zahlungsinformationen aus CMS-Einstellungen:

```php
$paymentInfo = [
    'bank'   => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_bank'"),
    'paypal' => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_paypal'"),
    'note'   => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_note'"),
];
```

**Konfiguration im Admin:** `admin/settings.php` → Bereich „Zahlungsinformationen"

---

## 6. Rechnungen & History

- **Rechnungsliste:** Datum, Paket, Betrag, Status als Tabelle
- **PDF-Download:** Jede Rechnung einzeln als PDF
- **Bestellnummer:** Eindeutige Referenz für Support-Anfragen

| Status | Bedeutung |
|---|---|
| `paid` | Bezahlt und bestätigt |
| `pending` | Zahlung erwartet |
| `refunded` | Erstattet |
| `cancelled` | Storniert |

---

## 7. Technische Details

**Services:** `CMS\Services\MemberService`, `CMS\SubscriptionManager`

```php
$controller->render('subscription-view', [
    'subscription' => $memberService->getUserSubscription($user->id),
    'allPlans'     => $subscriptionManager->getAllPlans(),
    'paymentInfo'  => $paymentInfo,
    'permissions'  => $memberService->getUserPermissions($user->id),
    'statusBadges' => $statusBadges
]);
```

**Hooks:**
```php
do_action('cms_subscription_upgraded', $userId, $oldPlanId, $newPlanId);
do_action('cms_subscription_cancelled', $userId, $planId, $effectiveDate);
do_action('cms_subscription_expired', $userId, $planId);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
