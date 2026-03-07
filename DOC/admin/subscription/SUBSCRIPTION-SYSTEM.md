# Abo-System

Kurzbeschreibung: Überblick über die aktuelle Aboarchitektur mit Paketen, Limits, Zuweisungen und dem Member-Bezug.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Das Abo-System in 365CMS besteht aus drei zentralen Admin-Bausteinen:

- `/admin/packages`
- `/admin/orders`
- `/admin/subscription-settings`

Es verbindet Paketdefinitionen, manuelle oder prozessgesteuerte Zuweisungen und eine systemweite Limitlogik.

---

## Aktuelle Admin-Struktur

Bereich **Aboverwaltung** standardmäßig verlinkt:

- **Pakete & Abo-Einstellungen** → `/admin/packages`
- **Bestellungen & Zuweisung** → `/admin/orders`
- **Einstellungen** → `/admin/subscription-settings`

Ältere Sammelrouten wie `/admin/subscriptions` sind nicht mehr die maßgebliche Benutzerführung.

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

Die Seite `/admin/subscription-settings` verwaltet heute vor allem das **Systemverhalten**, nicht alle Paketdetails.

Aktuell dokumentierte Schalter:

- Abo-Limits systemweit durchsetzen
- Abo-Bereich im Member-Dashboard anzeigen
- Bestell- und Upgrade-Prozesse zulassen
- Pakete öffentlich kommunizieren
- Standardpaket für neue Mitglieder
- Hinweistext bei Deaktivierung

Preislogik, Trial, Steuern und Paketdetails gehören bewusst in die Paket- bzw. Bestellbereiche.

---

## Beziehung zum Member-Bereich

Das Abo-System ist im Member-Bereich sichtbar, wenn entsprechende Optionen aktiv sind.

Wichtige Bezugspunkte:

- Member-Navigation kann den Bereich `subscription` anzeigen
- der Member-Bereich verlinkt auf Bestell-/Upgrade-Flows wie `/order?plan_id=...`
- Admin-Einstellungen können den Abo-Bereich im Member Dashboard ein- oder ausblenden

---

## Typische Arbeitsabläufe

### Neues Standardpaket für Registrierungen festlegen

1. `/admin/subscription-settings` öffnen
2. Standardpaket auswählen
3. speichern

### Paket manuell zuweisen

1. `/admin/orders` öffnen
2. „Zuweisen“ verwenden
3. Benutzer, Paket und Abrechnungsintervall wählen

### Limits global deaktivieren

1. `/admin/subscription-settings` öffnen
2. Schalter „Abo-Limits systemweit durchsetzen“ deaktivieren
3. speichern

---

## Verwandte Seiten

- [Bestellungen & Zuweisung](ORDERS.md)
- [Member Dashboard](../member/README.md)
- [Benutzer & Gruppen](../users-groups/README.md)
