# 365CMS – Commerce & Monetarisierung Roadmap

**Bereich:** E-Commerce, Payments, Subscriptions, Monetarisierung  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 1. Shop-System

### 🔴 CO-01 · Shop-Fundament
| Stufe | Feature |
|---|---|
| Stufe 1 | Produkt-Katalog (CPT mit Kategorien, Attributen, Varianten) |
| Stufe 2 | Preis-System (Brutto/Netto, MwSt.-Sätze konfigurierbar) |
| Stufe 3 | Bestands-Verwaltung (Lagerbestand, Mindest-Alarm) |
| Stufe 4 | Warenkorb (Session/Cookie-basiert, persistent bei Login) |
| Stufe 5 | Checkout-Flow (Adresse → Versand → Zahlung → Bestätigung) |
| Stufe 6 | Bestellungs-Verwaltung im Admin |
| Stufe 7 | Bestell-Statusmailing (Bestätigung, Versand, Lieferung) |
| Stufe 8 | Rechnungs-PDF (automatisch generiert, GDPR-konform) |

---

### 🟠 CO-02 · Produkt-Typen
| Typ | Beschreibung | Priorität |
|---|---|---|
| Einfaches Produkt | Ein Preis, ein Artikel | 🔴 Kritisch |
| Varianten-Produkt | Größe, Farbe → eigene SKU | 🟠 High |
| Digitales Produkt | PDF, Software – sofort-Download | 🔴 Kritisch |
| Abo-Produkt | Monatlich/jährlich wiederkehrend | 🟠 High |
| Dienstleistung | Stundenweise buchbar | 🟡 Mittel |
| Ticket | Event-Zugang | 🟠 High |
| Paket | Bündel aus mehreren Produkten | 🟡 Mittel |
| Kurs (LMS) | Zugang zu Lerninhalt | 🟢 Low |

---

### 🟠 CO-03 · Rabatte & Promotions
| Stufe | Feature |
|---|---|
| Stufe 1 | Gutscheincodes (Prozent oder Fest-Betrag) |
| Stufe 2 | Mindestbestellwert für Gutscheine |
| Stufe 3 | Einmal-Gutscheine vs. wiederverwendbare |
| Stufe 4 | Flash-Sales (Zeitlich begrenzte Rabatte) |
| Stufe 5 | Mengenrabatte (ab 5 Stück = 10% Rabatt) |
| Stufe 6 | Treuepunkte-Einlösung |
| Stufe 7 | Automatische Rabatte (regel-basiert, kein Code nötig) |
| Stufe 8 | First-Order-Rabatt für Neukunden |

---

## 2. Zahlungs-System

### 🔴 CO-04 · Zahlungs-Gateways
| Gateway | Features | Priorität |
|---|---|---|
| **Stripe** | Karten, SEPA, Apple/Google Pay, Wallets | 🔴 Kritisch |
| **PayPal** | Express Checkout, Zahlungen | 🟠 High |
| **Klarna** | Ratenzahlung, Sofort | 🟡 Mittel |
| **Mollie** | DE/NL-Fokus, viele Methoden | 🟡 Mittel |
| **Rechnung** | B2B: Auf Rechnung kaufen (Bonitätsprüfung) | 🟡 Mittel |
| **Überweisung** | Manuelle Bestätigung nach Zahlungseingang | 🟠 High |

**Gemeinsame Anforderungen:**
- PCI-DSS-Compliance (keine Kreditkartendaten im CMS speichern)
- 3D-Secure-Unterstützung (SCA-konform)
- Rückerstattungen aus dem Admin
- Teilrückerstattungen

---

### 🟠 CO-05 · Abo-Billing
| Stufe | Feature |
|---|---|
| Stufe 1 | Stripe Subscriptions Integration |
| Stufe 2 | PayPal Subscriptions |
| Stufe 3 | Upgrade/Downgrade mid-cycle mit proratierter Abrechnung |
| Stufe 4 | Kündigung mit Zugang bis Laufzeitende |
| Stufe 5 | Pause-Funktion (Abo pausieren, nicht kündigen) |
| Stufe 6 | Dunning Management (fehlgeschlagene Zahlungen + Mahnung) |
| Stufe 7 | Automatisches Retry bei Zahlungsausfall (3x) |
| Stufe 8 | Abo-Historien und Rechnungs-Archiv im Nutzerprofil |

---

## 3. Marketplace & Provisionen

### 🟡 CO-06 · Multi-Vendor-Marketplace
| Stufe | Feature |
|---|---|
| Stufe 1 | Vendor-Registrierung und -Onboarding |
| Stufe 2 | Eigener Vendor-Shop (Sub-Seite pro Anbieter) |
| Stufe 3 | Provisions-System (% pro Verkauf geht an Betreiber) |
| Stufe 4 | Stripe Connect / PayPal Marketplace (automatisches Splitting) |
| Stufe 5 | Vendor-Auszahlung (manuell oder automatisch) |
| Stufe 6 | Vendor-Bewertungen |
| Stufe 7 | Vendor-Dashboard (eigene Bestellungen, Statistiken) |
| Stufe 8 | Payout-Schwellenwert konfigurierbar |

---

## 4. Versand & Logistik

### 🟡 CO-07 · Versand-System
| Stufe | Feature |
|---|---|
| Stufe 1 | Versand-Methoden konfigurierbar (Flat-Rate, Gratis ab X€) |
| Stufe 2 | Gewichts- und größenbasierter Versand |
| Stufe 3 | DHL-Plugin (Versand-Label, Tracking) |
| Stufe 4 | DPD/GLS/Hermes-Adapter |
| Stufe 5 | Tracking-Nummer per E-Mail senden |
| Stufe 6 | Rücksendungs-Label generieren |
| Stufe 7 | Fulfillment-Center-Integration (Dropshipping) |

---

## 5. Steuer & Compliance

### 🔴 CO-08 · Steuer-System
| Stufe | Feature |
|---|---|
| Stufe 1 | MwSt.-Konfiguration (Standard 19%, ermäßigt 7%) |
| Stufe 2 | Reverse-Charge für EU-B2B |
| Stufe 3 | OSS (One-Stop-Shop) für EU-Verkäufe |
| Stufe 4 | USt-ID-Validierung (EU-VIES-API) |
| Stufe 5 | Steuer-Bericht (nach Land, Zeitraum) |
| Stufe 6 | DATEV-Export für Buchhaltung |
| Stufe 7 | XRechnung / ZUGFeRD für B2G-Geschäfte |

---

## 6. Analysen & Berichte

### 🟠 CO-09 · Commerce-Analytics
| Stufe | Feature |
|---|---|
| Stufe 1 | Umsatz-Dashboard (heute, Woche, Monat, Jahr) |
| Stufe 2 | Top-Produkte nach Umsatz |
| Stufe 3 | Conversion-Rate (Besucher → Kauf) |
| Stufe 4 | Cart-Abandonment-Rate |
| Stufe 5 | Customer-Lifetime-Value (CLV) |
| Stufe 6 | Cohort-Analyse (Kundenbindung über Zeit) |
| Stufe 7 | Revenue-by-Country-Karte |
| Stufe 8 | Prognose-Funktion (kommender Monat) |

---

*Letzte Aktualisierung: 19. Februar 2026*
