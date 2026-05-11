# 365CMS – Abo-Verwaltung
> **Stand:** 2026-05-10 | **Version:** 2.9.738 | **Status:** Aktuell

<!-- UPDATED: 2026-05-10 -->

## Überblick

Das Abo-System verbindet Paketdefinitionen, manuelle oder prozessgesteuerte Zuweisungen
und eine systemweite Limitlogik. Die Verwaltung erfolgt über drei Admin-Routen und
bildet die Grundlage für den Member-Bereich.

Seit `2.9.621` wirkt das unter `/admin/subscription-settings` konfigurierte Standardpaket für neue Mitglieder wieder in der echten Laufzeit: Öffentliche Registrierungen und neu im Admin angelegte Member-Konten erhalten das referenzierte aktive Paket automatisch, ohne dass bestehende aktive oder Trial-Abos überschrieben werden.

Seit `2.9.736` zeigt `/admin/orders` zusätzlich read-only Ablaufwarnungen und Renewal-Hinweise für fällige Mitgliedschaften. Die Hinweise leiten sich zentral aus `next_billing_date`, `end_date`, globaler Auto-Verlängerung, Kulanzzeit und dem konfigurierten Hinweisfenster `notification_before_expiry` ab. Es wird dabei bewusst **keine** neue Schreibroute, kein Mailversand und kein zusätzlicher Trackingpfad eingeführt.

Seit `2.9.737` bietet `/admin/orders` außerdem zwei sichere read-only CSV-Exporte an:

- Bestellungen, optional mit aktueller Statusfilterung
- Paketnutzung auf Basis von `subscription_usage` plus aktuellem Abo-/Plankontext

Die Downloads bleiben bewusst GET-only ohne CSRF- oder Sicherheitstoken in der URL, härten CSV-Zellen gegen Spreadsheet-Formula-Injection und protokollieren Exporte datensparsam im Audit-Log.

Seit `2.9.738` ergänzen `/admin/orders` und `/admin/packages` außerdem begrenzte read-only Historien auf Basis des vorhandenen `audit_log`. Damit werden Bestellstatuswechsel, Löschungen, Paketzuweisungen, Exporte sowie Paket-Erstellung, -Aktualisierung, -Statuswechsel und -Löschung direkt im jeweiligen Admin-Kontext sichtbar – ohne neue Schreibroute, ohne rohe Metadaten, ohne Token-Ausgabe und mit fail-softem Verhalten bei fehlendem Audit-Log.

## Verfügbare Funktionen

| Funktion | Route | Beschreibung |
|---|---|---|
| Pakete | `/admin/packages` | Abo-Pakete mit Limits, Preisen, Features und Pakethistorie definieren |
| Bestellungen | `/admin/orders` | Bestellungen einsehen, genehmigen, verwalten, als CSV exportieren und mit read-only Historie prüfen |
| Einstellungen | `/admin/subscription-settings` | Systemweite Abo-Konfiguration, Default-Plan und Limits |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [SUBSCRIPTION-SYSTEM.md](SUBSCRIPTION-SYSTEM.md)
- [ORDERS.md](ORDERS.md)
- [PACKAGES.md](PACKAGES.md)
- [../../member/README.md](../../member/README.md)
