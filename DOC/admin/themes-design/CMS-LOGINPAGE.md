# 365CMS – CMS Loginpage

Kurzbeschreibung: Themeunabhängige Core-Oberfläche für Login, Registrierung und Passwort-Reset inklusive zentraler Admin-Steuerung.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

Route: `/admin/cms-loginpage`

Öffentliche Core-Routen:

- `/cms-login`
- `/cms-register`
- `/cms-password-forgot`

---

## Zweck

Die **CMS Loginpage** löst die öffentliche Authentifizierung vom aktiven Frontend-Theme.

Damit rendert 365CMS Login, Registrierung und Passwort-Reset nicht mehr nur über themeeigene Templates, sondern bei Bedarf vollständig aus dem Core. Das stabilisiert vor allem:

- Logins bei Theme-Wechseln
- mehrsprachige bzw. locale-aware MFA-Flows
- einheitliche Redirect- und Sicherheitsverträge
- Admin-seitige Pflege von Auth-Texten und Brand-Elementen

---

## Bearbeitbare Bereiche

Die Admin-Seite arbeitet über `CMS\Services\CmsAuthPageService` und speichert ihre Werte überwiegend als `cms_loginpage_*`-Settings in `cms_settings`.

### 1. Grundlayout

- Brandname
- Logo-URL
- Kartenbreite
- Footer-Hinweis

### 2. Farben

- Background Start / Ende
- Kartenhintergrund
- Text- und Muted-Farbe
- Linkfarbe
- Primary-Button + Textfarbe
- Input-Hintergrund + Border

### 3. Texte & Headlines

Getrennte Headlines und Subheadlines für:

- Login
- Registrierung
- Passwort-Reset

### 4. Login-Formular

- Label für Benutzername/E-Mail
- Label für Passwort
- Button-Text
- „Passwort vergessen“-Linktext
- Platzhalter für Login-Felder
- Label für „Angemeldet bleiben“
- Schalter zum Ein-/Ausblenden von Remember-Me
- Schalter zum Ein-/Ausblenden des Passkey-Buttons
- eigener Button-Text für Passkey-Login

### 5. Registrierung

- globale Registrierungsfreigabe
- Freigabe der Member-Registrierung
- Feld-Labels und Platzhalter
- Button-Text
- Pflicht-Häkchen für Rechtszustimmung
- freier Text für Rechtszustimmung
- Hinweistext bei deaktivierter Registrierung

### 6. Passwort vergessen

- Label und Placeholder für E-Mail
- Button-Texte für Anfordern / Zurücksetzen / Erfolgszustand
- Erfolgsmeldung für Link-Anforderung
- Erfolgsmeldung für Reset

### 7. Recht & Footer

Verknüpfung mit veröffentlichten Seiten für:

- Datenschutzerklärung
- Nutzungsbedingungen
- Impressum

Zusätzlich frei pflegbare Footer-Link-Texte für:

- Login
- Registrierung
- Passwort vergessen
- Startseite

### 8. Reset-E-Mail

- Gültigkeit des Reset-Links in Minuten
- eigener Mail-Betreff
- eigener Mail-Text

Verfügbare Platzhalter im Mail-Text:

- `{site_name}`
- `{brand_name}`
- `{expires_minutes}`
- `{reset_url}`

---

## Sicherheits- und Laufzeitvertrag

Die CMS Loginpage ist nicht nur „hübscher Login“, sondern Teil des Security-Vertrags.

### Redirects

- öffentliche Redirects werden allowlist-basiert auf interne Same-Origin-Ziele normalisiert
- offene Redirects auf fremde Hosts oder lose zusammengesetzte Schemas werden verworfen

### CSRF

- Login, Registrierung und Reset arbeiten mit Core-CSRF-Tokens
- Admin-Speicherungen der CMS Loginpage folgen dem üblichen Admin-PRG-Flow

### Registrierung

Die Seite verwendet bewusst die vorhandenen globalen Settings:

- `registration_enabled`
- `member_registration_enabled`

Es gibt also **keinen zweiten konkurrierenden Registrierungs-Schalter** nur für die UI.

### MFA / Passkeys / LDAP

Seit `2.9.0` finalisieren folgende Login-Arten dieselbe authentifizierte Session:

- Passwort-Login
- MFA / TOTP
- Backup-Codes
- Passkey / WebAuthn
- LDAP

Das behebt insbesondere den früheren Effekt, dass MFA-Benutzer nach erfolgreicher Bestätigung wieder aus der Login-Strecke herausfielen.

### Remember-Me

Der Schalter **„Angemeldet bleiben“** ist seit `2.9.0` eine echte persistente Backend-Option und keine reine UI-Dekoration mehr.

---

## Abgrenzung zu anderen Admin-Seiten

| Route | Aufgabe |
|---|---|
| `/admin/cms-loginpage` | öffentliche Core-Auth-Seiten visuell und textlich steuern |
| `/admin/user-settings` | fachliche Auth- und Registrierungsgrundschalter, Provider-Status |
| `/admin/theme-editor` | theme-spezifischen Customizer des aktiven Themes laden |
| `/admin/theme-explorer` | Theme-Dateien kontrolliert durchsuchen |

---

## Typischer Betriebsablauf

1. Registrierung und Auth-Grundschalter unter `/admin/user-settings` prüfen.
2. Branding, Farben, Texte und Rechtsseiten unter `/admin/cms-loginpage` konfigurieren.
3. Öffentliche Pfade testen:
   - `/cms-login`
   - `/cms-register`
   - `/cms-password-forgot`
4. Optional MFA-, Passkey- und Reset-Mail-Flows mit einem Testkonto prüfen.

---

## Verwandte Dokumente

- [AUTH-SETTINGS.md](../users-groups/AUTH-SETTINGS.md)
- [README.md](README.md)
- [CUSTOMIZER.md](CUSTOMIZER.md)
- [../../member/README.md](../../member/README.md)
- [../../../Changelog.md](../../../Changelog.md)