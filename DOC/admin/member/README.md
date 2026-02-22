# Member-Dashboard – Admin-Verwaltung

**Datei:** `admin/member-dashboard.php`

---

## Übersicht

Der Admin-Bereich des Member-Dashboards bietet eine zentrale Verwaltungsschnittstelle für alle Mitglieder-bezogenen Funktionen. Admins können Mitgliederbereiche einsehen, konfigurieren und überwachen.

---

## Funktionen im Admin

### 📊 Member-Statistiken
- **Aktive Mitglieder** – Eingeloggte Nutzer der letzten 30 Tage
- **Neue Registrierungen** – Anmeldungen pro Zeitraum
- **Profil-Vollständigkeit** – Durchschnittliche Profilqualität
- **Nachrichten-Volumen** – Anzahl privater Nachrichten

### 👤 Profil-Verwaltung
- Mitgliederprofile einsehen und bearbeiten
- Avatar überschreiben oder löschen
- Bio und Social Links administrieren
- Profilsichtbarkeit steuern (Öffentlich / Privat)

### 💬 Nachrichten-Überwachung
- Nachrichtenvolumen überwachen (kein Lesen von privaten Nachrichten)
- Gemeldete Nachrichten verwalten
- Nachrichtensystem aktivieren/deaktivieren

### 🔔 Benachrichtigungs-Verwaltung
- System-Benachrichtigungen erstellen und versenden
- Benachrichtigungen an alle oder ausgewählte Gruppen
- Benachrichtigungs-Templates verwalten

### ❤️ Favoriten-Übersicht
- Meistgelikte Inhalte im Überblick
- Favoriten-Statistiken pro Inhaltstyp

### 🖼️ Medien-Kontrolle
- Mitglieder-Uploads einsehen und moderieren
- Speicherlimits pro Benutzer oder Abo-Paket konfigurieren
- Medien löschen oder freigeben

---

## Member-Dashboard Konfiguration

Im Admin kann konfiguriert werden, welche Bereiche im Member-Dashboard sichtbar sind:

| Bereich | Ein/Aus | Beschreibung |
|---------|---------|--------------|
| Profil | ✅ | Immer aktiv |
| Nachrichten | ⚙️ | Deaktivierbar |
| Benachrichtigungen | ⚙️ | Deaktivierbar |
| Favoriten | ⚙️ | Deaktivierbar |
| Medien | ⚙️ | Deaktivierbar |
| Sicherheit | ✅ | Immer aktiv |
| Mitgliedschaft | ⚙️ | Abhängig von Abo-System |

---

## Member-Bereich Zugangs-URL

```
/member           → Member-Dashboard
/member/profile   → Profil bearbeiten
/member/messages  → Nachrichten
/member/media     → Eigene Medien
/member/security  → Sicherheit & Login-Log
/member/subscription → Mitgliedschaft/Abo
```

---

## Datenbank-Tabellen

| Tabelle | Inhalt |
|---------|--------|
| `cms_users` | Basis-Profildaten |
| `cms_user_meta` | Erweiterte Profildaten, Avatar, Bio |
| `cms_messages` | Private Nachrichten |
| `cms_notifications` | Benachrichtigungen |
| `cms_media` | Mitglieder-Uploads |
| `cms_user_subscriptions` | Abo-Zuordnungen |

---

## Verwandte Seiten

- [Benutzerverwaltung](../users-groups/USERS.md)
- [Abo-Verwaltung](../subscription/SUBSCRIPTIONS.md)
- [Medien-Bibliothek](../media/README.md)
