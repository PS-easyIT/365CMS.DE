# Pakete & Abo-Pläne

Kurzbeschreibung: Verwaltung der Abo-Pakete mit Preisen, Limits, Feature-Flags, Paketstatus und read-only Pakethistorie.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.738

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/packages` |
| Modul | `CMS/admin/modules/subscriptions/PackagesModule.php` |
| View | `CMS/admin/views/subscriptions/packages.php` |
| CSRF-Kontext | `admin_packages` |

---

## Funktionsumfang

### Paketliste

Die Übersicht zeigt alle Abo-Pläne mit:

- Name, Slug, Status (aktiv/inaktiv)
- Monats- und Jahrespreis
- Aktive Abonnenten (`subscriber_count`)
- Featured-Markierung
- Sortierung
- read-only Pakethistorie unterhalb der Paketkarten

### Paket anlegen / bearbeiten

Felder pro Paket:

| Feld | Typ | Hinweis |
|---|---|---|
| Name | `VARCHAR(255)` | Pflicht |
| Slug | `VARCHAR(100)` | automatisch aus Name |
| Beschreibung | `TEXT` | optional |
| Monatspreis | `DECIMAL` | 0 = kostenlos |
| Jahrespreis | `DECIMAL` | 0 = kostenlos |
| Limits | diverse | z. B. max. Seiten, Medien, Benutzer |
| Sort-Order | `INT` | Reihenfolge in Listen |
| Featured | `BOOLEAN` | hervorgehoben in der Paketübersicht |

### 8 Feature-Flags

Jedes Paket kann granulare Features aktivieren:

| Flag | Zweck |
|---|---|
| `feature_analytics` | Zugriff auf Statistiken |
| `feature_advanced_search` | Erweiterte Suche |
| `feature_api_access` | API-Zugriff |
| `feature_custom_branding` | Eigenes Branding |
| `feature_priority_support` | Priorisierter Support |
| `feature_export_data` | Datenexport |
| `feature_integrations` | Drittanbieter-Integrationen |
| `feature_custom_domains` | Eigene Domains |

### Seed-Defaults

Beim ersten Aufruf legt `seedDefaults()` sechs Standardpakete an und markiert „Professional" als `is_featured`.

### Pakethistorie

Seit `2.9.738` zeigt `/admin/packages` einen begrenzten read-only Verlauf für paketbezogene Admin-Ereignisse aus dem vorhandenen `audit_log`.

Darunter fallen insbesondere:

- Paket erstellt
- Paket aktualisiert
- Paket aktiviert / deaktiviert
- Paket gelöscht
- Standardpakete ergänzt

Der Verlauf bleibt bewusst defensiv:

- keine neue Schreibroute
- keine rohen Audit-Metadaten im UI
- keine Token- oder Secret-Ausgabe
- fail-soft bei nicht lesbarem Audit-Log
- neue Paket-Audit-Einträge setzen zusätzlich die Paket-ID als `entity_id`, damit die Zuordnung robuster bleibt

---

## Aktionen

| Aktion | Methode |
|---|---|
| Paket erstellen/bearbeiten | `save(array $post)` |
| Paket löschen | `delete(int $id)` |
| Status umschalten | `toggleStatus(int $id)` |
| Historie lesen | `getData()` → `package_history` |

---

## Migration

`ensurePlanColumns()` prüft beim Laden, ob die Spalte `is_featured` in `subscription_plans` existiert und legt sie bei Bedarf an.

Für die Pakethistorie wird keine neue Tabelle eingeführt. Die Anzeige nutzt das bestehende `audit_log`.

---

## Verwandte Seiten

- [Abo-System](SUBSCRIPTION-SYSTEM.md)
- [Bestellungen](ORDERS.md)
- [Abo-Einstellungen → Paket-Einstellungen](SUBSCRIPTION-SYSTEM.md#paket-einstellungen)
