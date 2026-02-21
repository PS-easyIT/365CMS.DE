# 365CMS - Subscription System Dokumentation

## 📋 Übersicht

Das Subscription-System ist ein vollständig integriertes Abo-Management für 365CMS. Es ermöglicht flexible Benutzer- und Gruppen-Zuweisungen mit granularen Limits für alle Plugins.

## 🎯 Features

### ✅ Abo-Pakete
- **6 vordefinierte Pakete**: Free, Basic, Professional, Business, Premium, Enterprise
- Monatliche und jährliche Preise
- Unbegrenzte Anzahl eigener Pakete erstellbar

### ✅ Limits & Kontrolle
- **Plugin-Zugriff**: An/Aus für jeden Plugin (Experts, Companies, Events, Speakers)
- **Ressourcen-Limits**: Anzahl-Beschränkungen pro Ressource
  - `-1` = Unbegrenzt
  - `0` = Deaktiviert
  - `>0` = Spezifisches Limit
- **Storage-Limits**: Speicherplatz in MB
- **Premium-Features**: 8 zusätzliche Features (Analytics, API, Branding, etc.)

### ✅ Zuweisung
- **Direkt**: Benutzer → Abo-Paket
- **Gruppen**: Gruppen → Abo-Paket → Alle Mitglieder erhalten Zugriff

### ✅ Automatisches Tracking
- Nutzungs-Zähler für alle Ressourcen
- Automatische Limit-Prüfungen
- Visuelle Warnungen bei 80% Nutzung

## 📊 Standard-Pakete

| Paket | Preis/Monat | Preis/Jahr | Experts | Companies | Events | Speakers | Storage | Features |
|-------|-------------|------------|---------|-----------|--------|----------|---------|----------|
| **Free** | €0 | €0 | 1 | 1 | 5 | 1 | 100 MB | Basis |
| **Basic** | €9.99 | €99 | 5 | 3 | 20 | 5 | 500 MB | + Erweiterte Suche |
| **Professional** | €29.99 | €299 | 20 | 10 | 100 | 20 | 2 GB | + Analytics, API, Branding |
| **Business** | €79.99 | €799 | 100 | 50 | 500 | 100 | 10 GB | + Priority Support, Custom Domains |
| **Premium** | €149.99 | €1499 | 500 | 200 | 2000 | 500 | 50 GB | Alle Features |
| **Enterprise** | €499.99 | €4999 | ∞ | ∞ | ∞ | ∞ | 200 GB | Alle Features |

## 🗄️ Datenbank-Struktur

### Tables

```sql
cms_subscription_plans (Abo-Pakete)
├── id
├── name, slug, description
├── price_monthly, price_yearly
├── limit_experts, limit_companies, limit_events, limit_speakers
├── limit_storage_mb
├── plugin_* (Zugriffskontrolle)
└── feature_* (Premium-Features)

cms_user_subscriptions (Benutzer-Abos)
├── id
├── user_id, plan_id
├── status (active, cancelled, expired, trial, suspended)
├── billing_cycle (monthly, yearly, lifetime)
└── start_date, end_date, next_billing_date

cms_user_groups (Gruppen)
├── id
├── name, slug, description
├── plan_id (Abo für alle Mitglieder)
└── is_active

cms_user_group_members (Gruppen-Mitglieder)
├── id
├── user_id, group_id
└── joined_at

cms_subscription_usage (Nutzungszähler)
├── id
├── user_id
├── resource_type (experts, companies, events, speakers, storage)
└── current_count
```

## 💻 API & Helper-Funktionen

### Zugriffsprüfung

```php
// Plugin-Zugriff prüfen
if (user_can_access_plugin('cms-experts')) {
    // Plugin laden
}

// Ressourcen-Limit prüfen
if (user_can_create_resource('experts')) {
    // Neuen Expert erstellen
} else {
    display_upgrade_notice('Limit erreicht!');
}
```

### Limit-Informationen

```php
// Aktuelles Limit abrufen
$limit = get_user_resource_limit('experts'); 
// Returns: -1 (unbegrenzt), 0 (deaktiviert), oder Zahl

// Aktuelle Nutzung abrufen
$usage = get_user_resource_usage('experts');
// Returns: Anzahl der erstellten Experts

// Warnung anzeigen
display_resource_limit_warning('experts', 'Experten');
// Zeigt automatisch Warnung bei 80% oder Error bei 100%
```

### Feature-Checks

```php
// Premium-Feature prüfen
if (user_has_feature('analytics')) {
    // Analytics anzeigen
}

if (user_has_feature('api_access')) {
    // API freischalten
}
```

### Abo-Informationen

```php
// Aktuelles Abo abrufen
$subscription = get_current_subscription();

echo $subscription->name; // "Professional"
echo $subscription->price_monthly; // 29.99
echo $subscription->limit_experts; // 20
```

### Nutzung aktualisieren

```php
// Nach Erstellen eines Experts
$currentCount = count_user_experts($userId);
update_resource_usage('experts', $currentCount, $userId);

// System macht das automatisch, aber kann manuell getriggert werden
```

## 🔧 Admin-Interfaces

### 1. Abo-Verwaltung (`/admin/subscriptions`)

**Tabs:**
- **Abo-Pakete**: Alle Pakete anzeigen/erstellen
- **Benutzer-Zuweisungen**: Abos direkt an Benutzer zuweisen
- **Gruppen**: Link zur Gruppen-Verwaltung

**Features:**
- Standard-Pakete mit einem Klick erstellen
- Eigene Pakete mit allen Einstellungen erstellen
- Visuelle Paket-Karten mit allen Limits
- Direktzuweisung an Benutzer

### 2. Gruppen-Verwaltung (`/admin/groups`)

**Features:**
- Gruppen erstellen (Name, Slug, Beschreibung, Abo)
- Mitglieder verwalten
- Automatische Abo-Zuweisung an alle Mitglieder

## 🔌 Plugin-Integration

### In bestehenden Plugins eingebaut:

**Alle 4 Core-Plugins (Experts, Companies, Events, Speakers):**

1. **Archive-Seite**: Zugriffs-Check mit Upgrade-Notice
2. **Admin-Liste**: Limit-Warnung oben
3. **Admin-Save**: Limit-Check vor Erstellen
4. **Automatisch**: Admin-Menü-Einträge nur wenn Zugriff

### Beispiel: cms-experts Integration

```php
// In class-post-type.php - archive_page()
public function archive_page(): void
{
    // Subscription-Check
    if (!user_can_access_plugin('cms-experts')) {
        display_upgrade_notice('Zugriff auf Experten nicht verfügbar.');
        return;
    }
    
    // Normal weiterlaufen...
}

// In admin_list()
display_resource_limit_warning('experts', 'Experten');

// In admin_save()
if ($expert_id === 0 && !user_can_create_resource('experts')) {
    redirect('/admin/experts?error=limit_reached');
    return;
}
```

## 🚀 Aktivierung & Setup

### 1. System-Installation

Das Subscription-System ist automatisch aktiv nach Update:

```php
// In Bootstrap.php bereits integriert
require_once CORE_PATH . 'SubscriptionManager.php';
require_once ABSPATH . 'includes/subscription-helpers.php';

SubscriptionManager::instance();
```

### 2. Standard-Pakete erstellen

**Web-Interface:**
1. Admin → Abos (`/admin/subscriptions`)
2. Button "Standard-Pakete erstellen" klicken
3. 6 Pakete werden automatisch angelegt

**Programmatisch:**
```php
$sm = CMS\SubscriptionManager::instance();
$sm->seedDefaultPlans();
```

### 3. Erste Abo-Zuweisung

```php
// User ID 1 bekommt Enterprise-Paket
$subscriptionManager = CMS\SubscriptionManager::instance();
$plans = $subscriptionManager->getAllPlans();
$enterprisePlan = array_filter($plans, fn($p) => $p->slug === 'enterprise')[0];

$subscriptionManager->assignSubscription(
    userId: 1, 
    planId: $enterprisePlan->id, 
    billingCycle: 'yearly'
);
```

### 4. Gruppen einrichten

1. Admin → Gruppen (`/admin/groups`)
2. "Neue Gruppe erstellen"
3. Name: "Premium Members", Abo: "Premium"
4. Benutzer zur Gruppe hinzufügen
5. Alle Mitglieder erhalten automatisch Premium-Zugriff

## 📈 Verwendungs-Beispiele

### Szenario 1: Neue Firma registriert sich

```php
// Firma registriert sich → automatisch Free Plan
$userId = create_new_user($email, $password);

// Free Plan wird automatisch zugewiesen (Fallback in getUserSubscription())
$subscription = $subscriptionManager->getUserSubscription($userId);
// $subscription->name === "Free"
// $subscription->limit_experts === 1

// Firma versucht 2. Expert anzulegen → Blockiert
if (!user_can_create_resource('experts')) {
    echo "Limit erreicht! Bitte upgraden.";
}
```

### Szenario 2: Upgrade auf Business

```php
// Admin weist Business-Paket zu
$businessPlan = $subscriptionManager->getPlan(4); // Business = ID 4
$subscriptionManager->assignSubscription($userId, 4, 'yearly');

// Jetzt verfügbar:
// - 100 Experts
// - 50 Companies
// - Priority Support
// - Custom Domains
```

### Szenario 3: Agentur mit mehreren Mitarbeitern

```php
// Gruppe erstellen
$db->insert('user_groups', [
    'name' => 'Agentur XYZ',
    'slug' => 'agentur-xyz',
    'plan_id' => 5, // Premium
]);

// 10 Mitarbeiter hinzufügen
foreach ($employeeIds as $empId) {
    $db->insert('user_group_members', [
        'user_id' => $empId,
        'group_id' => $groupId
    ]);
}

// ALLE 10 Mitarbeiter haben jetzt Premium-Zugriff!
```

## ⚙️ Erweiterte Konfiguration

### Eigenes Paket erstellen

**Via Admin-Interface:**
1. `/admin/subscriptions`
2. "Neues Paket erstellen"
3. Alle Limits konfigurieren
4. Speichern

**Programmatisch:**
```php
$db = CMS\Database::instance();
$db->insert('subscription_plans', [
    'name' => 'Startup Special',
    'slug' => 'startup-special',
    'price_monthly' => 19.99,
    'price_yearly' => 199.00,
    'limit_experts' => 10,
    'limit_companies' => 5,
    'limit_events' => 50,
    'limit_speakers' => 10,
    'limit_storage_mb' => 1000,
    'plugin_experts' => 1,
    'plugin_companies' => 1,
    'plugin_events' => 1,
    'plugin_speakers' => 1,
    'feature_analytics' => 1,
    'feature_api_access' => 0,
    'sort_order' => 99
]);
```

### Temporäres Trial-Abo

```php
$subscriptionManager->assignSubscription($userId, $premiumPlanId, 'monthly');

// Status auf 'trial' setzen
$db->update('user_subscriptions', 
    ['status' => 'trial', 'end_date' => date('Y-m-d', strtotime('+14 days'))],
    ['user_id' => $userId, 'status' => 'active']
);
```

## 🔍 Monitoring & Reports

### Nutzungs-Statistiken abrufen

```php
// Alle Abos mit Nutzung
$stats = $db->query("
    SELECT 
        us.user_id,
        u.username,
        sp.name as plan_name,
        (SELECT current_count FROM cms_subscription_usage 
         WHERE user_id = us.user_id AND resource_type = 'experts') as expert_count,
        sp.limit_experts
    FROM cms_user_subscriptions us
    JOIN cms_users u ON us.user_id = u.id
    JOIN cms_subscription_plans sp ON us.plan_id = sp.id
    WHERE us.status = 'active'
")->fetchAll();

foreach ($stats as $stat) {
    $percentage = ($stat->expert_count / $stat->limit_experts) * 100;
    echo "{$stat->username}: {$stat->expert_count}/{$stat->limit_experts} ({$percentage}%)\n";
}
```

### Upgrade-Kandidaten finden

```php
// Benutzer die > 80% ihres Limits nutzen
$candidates = $db->query("
    SELECT us.user_id, u.email, su.current_count, sp.limit_experts
    FROM cms_subscription_usage su
    JOIN cms_user_subscriptions us ON su.user_id = us.user_id
    JOIN cms_users u ON us.user_id = u.id
    JOIN cms_subscription_plans sp ON us.plan_id = sp.id
    WHERE su.resource_type = 'experts'
      AND us.status = 'active'
      AND (su.current_count / sp.limit_experts) >= 0.8
")->fetchAll();

// Email-Kampagne: "Zeit für ein Upgrade!"
```

## 🛠️ Hooks & Erweiterungen

### Verfügbare Hooks

```php
// Nach Abo-Zuweisung
CMS\Hooks::addAction('subscription_assigned', function($userId, $planId) {
    // Email senden
    // Willkommens-Nachricht
    // Analytics tracken
});

// Vor Limit-Check (für custom Logic)
CMS\Hooks::addFilter('check_resource_limit', function($canCreate, $userId, $resourceType) {
    // Custom Logik
    return $canCreate;
}, 10, 3);
```

## 📝 Checklist: Neues Plugin hinzufügen

Wenn Sie ein neues Plugin erstellen und Subscription-System integrieren:

- [ ] Limit-Feld in `subscription_plans` Tabelle hinzufügen
  ```sql
  ALTER TABLE cms_subscription_plans 
  ADD COLUMN limit_my_resource INT DEFAULT -1,
  ADD COLUMN plugin_my_plugin BOOLEAN DEFAULT 1;
  ```

- [ ] Helper-Funktionen nutzen:
  ```php
  // In archive_page()
  if (!user_can_access_plugin('cms-my-plugin')) {
      display_upgrade_notice('Zugriff nicht verfügbar');
      return;
  }
  
  // In admin_list()
  display_resource_limit_warning('my_resource', 'Meine Ressourcen');
  
  // In admin_save()
  if (!user_can_create_resource('my_resource')) {
      redirect with error
  }
  ```

- [ ] Usage-Tracking implementieren:
  ```php
  // Nach Create/Delete
  $count = count_my_resources($userId);
  update_resource_usage('my_resource', $count, $userId);
  ```

- [ ] Standard-Pakete updaten via Admin-Interface

## 🎨 UI-Komponenten

### Upgrade-Notice

```php
display_upgrade_notice('Custom Nachricht');
// Zeigt großen Upgrade-Banner mit Link zu /admin/subscriptions
```

### Limit-Warnung

```php
display_resource_limit_warning('experts', 'Experten');
// Automatisch:
// - Grün bei < 80%
// - Gelb bei 80-99%
// - Rot bei 100%
```

### Manuelles Limit-Display

```php
$subscription = get_current_subscription();
?>
<div class="subscription-info">
    <h3>Ihr Abo: <?= $subscription->name ?></h3>
    <p>Experten: <?= get_user_resource_usage('experts') ?> / <?= $subscription->limit_experts === -1 ? '∞' : $subscription->limit_experts ?></p>
</div>
```

## 🔐 Sicherheit

- ✅ **CSRF-Schutz**: Alle Forms mit Token
- ✅ **Admin-Only**: Subscription-Verwaltung nur für Admins
- ✅ **SQL-Escaping**: Alle Queries mit Prepared Statements
- ✅ **Input-Sanitization**: sanitize_text(), sanitize_email()
- ✅ **Permission-Checks**: Vor jeder kritischen Operation

## 📞 Support & Weiterentwicklung

### Geplante Features (Roadmap)

- [ ] **Automatische Abrechnung**: Stripe/PayPal Integration
- [ ] **Rechnungs-System**: PDF-Generierung
- [ ] **Email-Benachrichtigungen**: Bei Limit-Warnung, Ablauf, etc.
- [ ] **Self-Service**: Benutzer können selbst upgraden
- [ ] **Coupons**: Rabatt-Codes
- [ ] **Free Trials**: Automatische Trial-Periods
- [ ] **Downgrade-Protection**: Daten bei Downgrade schützen
- [ ] **Usage-Analytics**: Dashboard für Admins

### Troubleshooting

**Problem: Limit-Check funktioniert nicht**
```php
// Debug-Info ausgeben
$subscription = get_current_subscription();
var_dump($subscription);

$usage = get_user_resource_usage('experts');
$limit = get_user_resource_limit('experts');
echo "Usage: $usage, Limit: $limit";
```

**Problem: Benutzer hat kein Abo**
```php
// Manuell Free Plan zuweisen
$freePlan = $db->query("SELECT id FROM cms_subscription_plans WHERE slug = 'free'")->fetch();
$subscriptionManager->assignSubscription($userId, $freePlan->id, 'lifetime');
```

**Problem: Nutzungs-Counter stimmt nicht**
```php
// Counter neu berechnen
$actualCount = $db->query("SELECT COUNT(*) as c FROM cms_experts WHERE user_id = {$userId}")->fetch()->c;
update_resource_usage('experts', $actualCount, $userId);
```

## 🎉 Zusammenfassung

Das Subscription-System ist **produktionsbereit** und vollständig in alle 4 Core-Plugins integriert:

✅ **Core**: SubscriptionManager, Database-Tables, Helper-Functions  
✅ **Admin**: Subscription Management, Gruppen-Verwaltung, User-Assignment  
✅ **Plugins**: cms-experts, cms-companies, cms-events, cms-speakers  
✅ **Features**: 6 Standard-Pakete, flexible Limits, Premium-Features  
✅ **UX**: Automatische Warnungen, Upgrade-Notices, visuelle Limits  

**Nächster Schritt**: `/admin/subscriptions` → "Standard-Pakete erstellen" klicken! 🚀
