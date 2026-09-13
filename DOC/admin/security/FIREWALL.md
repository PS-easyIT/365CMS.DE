# 365CMS – Projektdokumentation | Abschnitt: Admin – Firewall
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Firewall administration is available at `/admin/firewall`. The compatibility entry points are `CMS/admin/cms-firewall.php` and `CMS/admin/firewall.php`; the current module and view are `CMS/admin/modules/security/FirewallModule.php` and `CMS/admin/views/security/firewall.php`.

The module supports normalized firewall rules, recent block data, and a simulation preview. The simulation preview is bounded by the configured preview window (1–168 hours). Apply rules through the validated admin form and review the resulting status before relying on them.

## Deutsch

Die Firewall-Verwaltung ist unter `/admin/firewall` verfügbar. Die Kompatibilitätseinstiege sind `CMS/admin/cms-firewall.php` und `CMS/admin/firewall.php`; das aktuelle Modul und die Ansicht liegen in `CMS/admin/modules/security/FirewallModule.php` und `CMS/admin/views/security/firewall.php`.

Das Modul verarbeitet normalisierte Firewall-Regeln, aktuelle Blockierungsdaten und eine Simulationsvorschau. Das Vorschaufenster ist auf den konfigurierten Bereich von 1 bis 168 Stunden begrenzt. Regeln über das geprüfte Admin-Formular anwenden und den Status danach kontrollieren.
