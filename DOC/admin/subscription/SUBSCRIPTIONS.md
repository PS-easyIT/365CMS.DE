# Subscription System Documentation
**Datei:** `admin/subscriptions.php`

Das Subscription-System ermöglicht die Verwaltung von Mitgliedschaftsplänen, Benutzer-Abonnements und Zugriffsbeschränkungen.

## Core-Komponenten

### 1. Pläne (Plans)
Definieren die verfügbaren Abo-Modelle.
- **Name:** Anzeigename (z.B. "Pro", "Enterprise").
- **Preis:** Monatlicher/Jährlicher Preis.
- **Features:** Liste der enthaltenen Funktionen.
- **Limits:** Begrenzungen (z.B. Anzahl Projekte, API-Calls).

### 2. Benutzer-Abos
Verknüpfung zwischen User und Plan.
- **Laufzeit:** Start- und Enddatum.
- **Status:** Aktiv, Gekündigt, Abgelaufen.
- **Payment:** Zahlungsinformationen (Referenz).

### 3. Zugriffskontrolle
Das System prüft bei geschützten Inhalten automatisch den Abo-Status des Nutzers.
- Integration in `MemberService`.
- Hooks für Plugin-Erweiterungen.
