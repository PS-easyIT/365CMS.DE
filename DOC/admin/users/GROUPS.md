# User Groups
**Datei:** `admin/groups.php`

Gruppierung von Benutzern zur effizienten Rechteverwaltung.

## Funktionen

### 1. Gruppen erstellen
- Name (z.B. "Premium Mitglieder", "Redaktion").
- Beschreibung.

### 2. Mitglieder zuweisen
- Manuelles Hinzufügen von Benutzern zu Gruppen.
- Automatische Zuweisung bei Registrierung (optional).
- Koppelung an Subscriptions (z.B. Plan "Gold" -> Gruppe "V.I.P.").

### 3. Berechtigungen (ACL)
- Definieren, was die Gruppe darf:
  - `page_view_restricted`: Zugriff auf geschützte Seiten.
  - `file_download`: Download-Berechtigung.
  - `no_ads`: Werbefreiheit.
