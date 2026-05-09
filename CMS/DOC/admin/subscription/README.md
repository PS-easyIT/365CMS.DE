# 365CMS – Abo-Verwaltung
> **Stand:** 2026-05-09 | **Version:** 2.9.621 | **Status:** Aktuell

<!-- UPDATED: 2026-05-09 -->

## Überblick

Das Abo-System verbindet Paketdefinitionen, manuelle oder prozessgesteuerte Zuweisungen
und eine systemweite Limitlogik. Die Verwaltung erfolgt über drei Admin-Routen und
bildet die Grundlage für den Member-Bereich.

Seit `2.9.621` wirkt das unter `/admin/subscription-settings` konfigurierte Standardpaket für neue Mitglieder wieder in der echten Laufzeit: Öffentliche Registrierungen und neu im Admin angelegte Member-Konten erhalten das referenzierte aktive Paket automatisch, ohne dass bestehende aktive oder Trial-Abos überschrieben werden.

## Verfügbare Funktionen

| Funktion | Route | Beschreibung |
|---|---|---|
| Pakete | `/admin/packages` | Abo-Pakete mit Limits, Preisen und Features definieren |
| Bestellungen | `/admin/orders` | Bestellungen einsehen, genehmigen und verwalten |
| Einstellungen | `/admin/subscription-settings` | Systemweite Abo-Konfiguration, Default-Plan und Limits |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [SUBSCRIPTION-SYSTEM.md](SUBSCRIPTION-SYSTEM.md)
- [ORDERS.md](ORDERS.md)
- [PACKAGES.md](PACKAGES.md)
- [../../member/README.md](../../member/README.md)
