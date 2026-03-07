# DSGVO-Löschanträge

Kurzbeschreibung: Verwaltung und Durchführung von Datenlöschungsanfragen gemäß DSGVO Art. 17 (Recht auf Löschung).

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/deletion-requests` |
| Modul | `CMS/admin/modules/legal/DeletionRequestsModule.php` |
| CSRF-Kontext | `admin_deletion_requests` |

---

## Funktionsumfang

### Antragsliste

`getData()` zeigt alle eingegangenen Löschanträge mit Status, Antragsteller und Datum.

### Aktionen

| Aktion | Methode | Beschreibung |
|---|---|---|
| Bearbeiten | `processRequest(int $id)` | Antrag in Bearbeitung nehmen |
| Durchführen | `executeDeletion(int $id)` | Personenbezogene Daten löschen |
| Ablehnen | `rejectRequest(int $id, string $reason)` | Antrag mit Begründung ablehnen |
| Löschen | `deleteRequest(int $id)` | Antragseintrag entfernen |

### Lösch-Workflow

1. Mitglied stellt Löschantrag (Member-Bereich oder manuell)
2. Admin sieht Antrag in der Liste → `processRequest()`
3. Admin prüft Berechtigung und Abhängigkeiten
4. Löschung durchführen → `executeDeletion()` oder Ablehnung → `rejectRequest()`

Die Löschung triggert den Hook `dsgvo_delete_data`, damit angebundene Plugins ihre Daten ebenfalls bereinigen können.

---

## Sicherheit

- Nur Administratoren
- CSRF-Prüfung
- Begründungspflicht bei Ablehnung

---

## Verwandte Seiten

- [DSGVO](../legal/DSGVO.md)
- [Rechtstexte](../legal/LEGAL.md)
- [Benutzer & Gruppen](../users-groups/README.md)
