# 365CMS Member Security
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Date:** 2026-09-06 | **Status:** Current | **Last updated:** 2026-09-06

This document explains how the member area protects sessions, actions, personal data, and files. It starts with practical guidance and then records the implementation contract.

## User guide

Members should use a unique password, enable TOTP or a passkey, review active sessions, and keep backup codes offline. Uploaded files belong to the current member area and cannot be addressed through another member's root.

## Technical reference

- Authentication is enforced by `MemberController::requireAuth()`.
- State-changing requests verify action-specific tokens through `Security::verifyToken()`.
- Password, MFA, passkey, notification, privacy, message, profile, and media actions use separate handlers.
- Media roots use `member/user-{id}`; normalized paths reject traversal and target lists are bounded.
- Uploads use `/api/upload` with a member CSRF token and configured size/type limits.
- Output is escaped in the shared header and page templates; custom profile values are normalized by type.
- Member responses are marked private to prevent shared-cache leakage.
- Admin dashboard configuration requires admin status, an allowlisted capability, module enablement, and the `admin_member_dashboard` CSRF context.

---

# 365CMS-Sicherheit im Mitgliederbereich
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Datum:** 2026-09-06 | **Status:** Aktuell | **Zuletzt aktualisiert:** 2026-09-06

Dieses Dokument erklärt den Schutz von Sitzungen, Aktionen, personenbezogenen Daten und Dateien. Zuerst folgen praktische Hinweise, danach der technische Vertrag.

## Anwenderleitfaden

Mitglieder sollten ein einzigartiges Passwort verwenden, TOTP oder einen Passkey aktivieren, aktive Sitzungen prüfen und Backup-Codes offline aufbewahren. Hochgeladene Dateien gehören zum eigenen Member-Bereich und können nicht über den Root eines anderen Mitglieds adressiert werden.

## Technische Referenz

Die Sicherheitsregeln entsprechen den englischen Punkten: verpflichtende Authentifizierung, aktionsbezogene CSRF-Tokens, getrennte Handler, userbezogene Medien-Roots, begrenzte Uploads, typisierte Profilwerte, Escaping, private Cache-Header sowie capability- und CSRF-geschützte Admin-Konfiguration.
