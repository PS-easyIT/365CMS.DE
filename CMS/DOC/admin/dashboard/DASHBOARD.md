# Admin-Dashboard

Kurzbeschreibung: Beschreibt die aktuelle Startseite des Admin-Bereichs inklusive Kennzahlen, Schnellzugriffen, Warnhinweisen und segmentweisem Fail-Soft-Verhalten.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.720

---

## Überblick

- Route: `/admin`
- Entry Point: `CMS/admin/index.php`
- Logik: `CMS/admin/modules/dashboard/DashboardModule.php`

Das Admin-Dashboard ist die zentrale Einstiegsseite für Redakteure und Administratoren. Es zeigt einen kuratierten Überblick über Systemzustand, Inhalte, Aktivität und – falls aktiv – Bestellungen. Seit `2.9.701` können Admins optionale Blöcke pro Benutzer ein- oder ausblenden; kritische Alerts und die zentrale Arbeitsübersicht bleiben dabei bewusst sichtbar.

Seit `2.9.716` lässt sich zusätzlich **jedes einzelne Widget innerhalb der zentralen Arbeitsübersicht** pro Admin-Benutzer aktivieren oder deaktivieren. Die Hauptsektion bleibt sichtbar, aber ihre Karten sind jetzt granular konfigurierbar – von Kernzahlen über Moderation bis hin zu Security- und System-Snapshots.

Seit `2.9.717` ergänzt das Dashboard außerdem einen optionalen Block **„Favoriten & zuletzt genutzt“**: ausgewählte Admin-Ziele werden serverseitig als persönliche Favoriten gespeichert, und eine lokale Verlaufsliste zeigt zuletzt genutzte Admin-Seiten an, ohne dafür sensible Daten oder serverseitige Verlaufsprofile anzulegen.

Seit `2.9.718` lässt sich die Reihenfolge der Arbeits-Widgets und Favoriten zusätzlich **persistiert sortieren**. Das Dashboard nutzt dafür einen progressiv erweiterten Sortierpfad: per Drag-&-Drop im Browser oder per Auf/Ab-Buttons als Fallback, sodass die Personalisierung nicht an einer einzigen Maus-Interaktion hängt.

Seit `2.9.719` wird dieser Pfad zusätzlich nachgehärtet: Die browserlokale Recent-Liste wird vor Anzeige und Speicherung bereinigt, dedupliziert und größenbegrenzt, Drop-Zustände werden im Sortier-JS robuster zurückgesetzt und das Dashboard-CSS wird als cachebares Seiten-Asset statt inline ausgeliefert.

Seit `2.9.720` ergänzt das Dashboard darauf aufbauend rollenbasierte Standardvorlagen: Neue oder auf Standard zurückgesetzte persönliche Ansichten übernehmen pro Rolle bzw. capability-basierter Rollenfamilie sinnvolle Defaults für sichtbare Bereiche, aktive Arbeits-Widgets, Favoriten und deren Reihenfolge. Persönliche Anpassungen bleiben dabei bewusst benutzerbezogen und überschreiben nicht die zugrunde liegende Rollen-Vorlage.

Seit `2.9.615` wird jeder Statistikblock einzeln geladen. Fällt z. B. die Sicherheits-, Sessions- oder Orders-Datenquelle aus, bleibt die Startseite renderbar und arbeitet für den betroffenen Block mit neutralen Fallback-Werten statt mit einem Full-Page-Fatal.

Seit `2.9.705` ist der Speichern-Flow der Dashboard-Personalisierung zusätzlich gegen stale Tabs und parallel geöffnete Admin-Formulare gehärtet: Mehrere Tokens pro CSRF-Action bleiben innerhalb des TTL-Fensters gültig, der konkret verwendete Token wird danach weiterhin invalidiert.

Seit `2.9.707` akzeptiert die View für Quicklinks und Deep-Links außerdem nur noch interne Pfade mit führendem `/`. Unerwartete oder beschädigte Zielwerte fallen fail-closed auf ein internes Admin-Ziel zurück, statt roh übernommen zu werden.

---

## Zentrale Arbeitsübersicht

Die zentrale Arbeitsübersicht wird in `DashboardModule::buildWorkOverviewWidgets()` aufgebaut. Sie bündelt scanbare Management-Karten mit Direktlinks in die jeweiligen Admin-Bereiche.

Aktuell sind unter anderem vorgesehen:

| Widget | Quelle | Link |
|---|---|---|
| Benutzer | Benutzerstatistik | `/admin/users` |
| Seiten | Seitenstatistik | `/admin/pages` |
| Beiträge | Beitragsstatistik | `/admin/posts` |
| Medien | Medienstatistik | `/admin/media` |
| Nutzerwachstum | Benutzerstatistik | `/admin/users` |
| Redaktions-Pipeline | Seiten- und Beitragsstatus | `/admin/posts` |
| Kommentar-Moderation | Kommentarbestand | `/admin/comments` |
| Aktive Sessions | Sessionstatistik | `/admin/analytics` |
| Security Snapshot | Sicherheitsstatistik | `/admin/security-audit` |
| System-Stack | Systemstatistik | `/admin/settings` |
| Umsatz (30T) | nur bei aktivem Abo-/Order-System | `/admin/orders` |

Die Umsatz-Kachel erscheint nur, wenn das Abo-/Bestellsystem aktiv ist. Alle Widgets verwenden serverseitig allowlistete Schlüssel aus `DashboardModule::WORK_OVERVIEW_WIDGET_DEFINITIONS`.

---

## Widget-Personalisierung & Reihenfolge

Die Dashboard-Personalisierung speichert sichtbare Bereiche pro Admin-Benutzer in `settings` unter `admin_dashboard_preferences_user_<id>`.

Seit `2.9.716` werden dort zusätzlich zu `visible_sections` auch `visible_work_overview_widgets` abgelegt. Seit `2.9.718` kommen außerdem `work_overview_widget_order` und `favorite_shortcut_order` hinzu. Der Server akzeptiert nur bekannte Bereichs-, Widget- und Favoriten-Schlüssel, normalisiert Duplikate heraus, ergänzt fehlende bekannte Keys kontrolliert und lässt die zentrale Arbeitsübersicht selbst weiterhin sichtbar.

Der Speichern-Flow:

1. POST auf `/admin` mit Action `save_dashboard_preferences`
2. CSRF-Prüfung über die gemeinsame Section-Shell (`admin_dashboard`)
3. Allowlist-Normalisierung der gewählten Bereiche, Widgets, Favoriten und ihrer Reihenfolgen
4. Persistenz in `settings` mit `autoload = 0`
5. Audit-Eintrag `dashboard.preferences.save`

Dadurch können Admins die Arbeitsübersicht pro Benutzer fein zuschneiden und sortieren, ohne Warnlogik, Berechtigungen oder Pflichtbereiche abzuschalten. Seit `2.9.720` startet dieser Pfad außerdem aus einer rollenbasierten Ausgangslage statt aus einem einzigen generischen Default für alle.

Die Sortier-UI hängt an `CMS/assets/js/admin-dashboard.js` und arbeitet bewusst progressiv:

- Drag-&-Drop via HTML Drag and Drop API für schnelle Mausinteraktionen
- persistente Reihenfolge über Hidden-Inputs im Formular
- Auf/Ab-Buttons als browser- und zugänglichkeitsfreundlicher Fallback
- robustes Cleanup von Drop-Markierungen und browserlokale Storage-Härtung im begleitenden Recent-Block

Selbst wenn Drag-&-Drop im konkreten Browser nicht genutzt wird, bleibt die Sortierung über die Button-Steuerung weiter möglich.

---

## Favoriten & zuletzt genutzt

Der Bereich „Favoriten & zuletzt genutzt“ ergänzt das Dashboard um zwei persönliche Navigationsebenen:

| Teil | Speicherort | Zweck |
|---|---|---|
| Favoriten | `settings` → `admin_dashboard_preferences_user_<id>` | serverseitig gespeicherte Schnellzugriffe pro Admin-Benutzer |
| Zuletzt genutzt | `localStorage` im Browser | lokale Verlaufsliste zuletzt besuchter Admin-Ziele |

Die Favoriten werden über `favorite_shortcuts` im bestehenden Dashboard-Preference-Payload gespeichert und serverseitig gegen `DashboardModule::FAVORITE_SHORTCUT_DEFINITIONS` normalisiert. Seit `2.9.718` wird zusätzlich eine separate bevorzugte Reihenfolge gespeichert, sodass aktive Favoriten nicht nur sichtbar, sondern auch bewusst priorisiert angeordnet werden können.

Die Verlaufsliste „Zuletzt genutzt“ wird bewusst **nicht** serverseitig als Benutzertracking gespeichert, sondern nur lokal im Browser. Dabei werden ausschließlich interne relative Admin-URLs und Labels erfasst; flüchtige Parameter wie Tokens oder Flash-Meldungen werden vor dem Speichern entfernt. Seit `2.9.719` werden beschädigte, doppelte oder übergroße Einträge zusätzlich bereinigt, bevor sie erneut angezeigt oder fortgeschrieben werden. Ist Browser-Persistenz deaktiviert oder nicht verfügbar, bleibt der Bereich leer und der Admin bleibt weiter vollständig nutzbar.

---

## Letzte Aktivitäten

Die Aktivitätsliste greift auf die Tabelle `audit_log` zu und zeigt die jüngsten Einträge chronologisch.

Dargestellt werden bis zu acht Einträge aus:

- Aktionen im Admin
- Systemprozessen
- workflow-relevanten Änderungen

---

## Schnellzugriffe

Der Bereich „Schnellzugriffe“ enthält derzeit feste Links auf:

- neue Seite
- neuer Beitrag
- Medien hochladen
- Einstellungen

Diese Links werden zentral in `DashboardModule::getQuickLinks()` definiert.

---

## Benutzerbezogene Sichtbarkeit & Rollen-Vorlagen

Die CSRF-Prüfung bleibt ein One-Time-Token-Vertrag pro tatsächlich eingereichtem Token, akzeptiert aber seit `2.9.705` eine begrenzte Token-Historie pro Action, damit ältere noch gültige Admin-Formulare nicht fälschlich scheitern.

Ausblendbar sind optionale Bereiche wie Favoriten & zuletzt genutzt, Aufmerksamkeit, Systemstatus, Sicherheit & Performance, Bestellungen und letzte Aktivitäten. Zusätzlich sind die einzelnen Widgets der Arbeitsübersicht schaltbar und ihre Reihenfolge – ebenso wie die der Favoriten – pro Benutzer persistent. Nicht ausblendbar sind kritische Alerts sowie die zentrale Arbeitsübersicht selbst.

Seit `2.9.720` greift oberhalb dieser persönlichen Persistenz eine Rollen-Vorlage:

- sie liefert den Default für Benutzer ohne eigene gespeicherte Dashboard-Ansicht,
- sie wird über einen expliziten POST-Reset wiederhergestellt,
- sie bleibt fail-closed auf vordefinierte Bereichs-, Widget- und Favoriten-Keys beschränkt,
- und sie respektiert capability-basierte Fallback-Familien für benutzerdefinierte Rollen (`admin`, `editor`, `author`, `member`).

Der aktuelle Einstieg `/admin` bleibt weiterhin admin-geschützt. Im heutigen Standardbetrieb wirkt deshalb primär die Admin-Vorlage direkt sichtbar; die zusätzliche Rollenableitung dient aber bereits als konsistente Default-Basis für kompatible oder künftig capability-basierte Rollenszenarien.

---

## Warnungen und Aufmerksamkeitspunkte

Das Dashboard zeigt zwei unterschiedliche Arten von Hinweisen:

### Alerts

Direkte Warnmeldungen aus `DashboardModule::getAlerts()`:

- Kommentare in Moderation
- erhöhte Zahl fehlgeschlagener Logins

### Attention Items

Zusätzliche Systemhinweise aus `DashboardService::getAttentionItems()`.

Diese zweite Ebene bündelt situationsabhängige Punkte, die besondere Aufmerksamkeit brauchen.

### Fallback-Warnung bei degradierten Statistikquellen

Kann ein einzelnes Dashboard-Segment nicht geladen werden, ergänzt `DashboardModule` einen zusätzlichen `warning`-Hinweis mit Deep-Link auf `/admin/cms-logs`.

Damit wird der degradierte Zustand sichtbar, ohne den übrigen Dashboard-Renderpfad zu blockieren.

---

## Begrenzungen der Seite

- Es gibt seit `2.9.720` rollenbasierte **Standardvorlagen** für die bestehende Auswahl an Bereichen, Arbeits-Widgets, Favoriten und Reihenfolgen. Frei definierbare Widget-Typen oder ein eigener Vorlagen-Editor pro Rolle sind damit aber weiterhin nicht umgesetzt.
- Die frühere Dokumentation zu einem separaten „Admin Dashboard Widgets“-Designer ist nicht mehr aktuell.
- Konfigurierbare Widgets betreffen heute primär das **Member Dashboard**, nicht die Admin-Startseite.
- Die lokale Liste „Zuletzt genutzt“ ist browsergebunden und kein serverseitig synchronisierter Verlauf über Geräte oder Browser hinweg.
- Live-Plausibilitätsprüfungen der Kennzahlen bleiben weiterhin Aufgabe des Betriebs-/QA-Durchlaufs gegen eine reale Datenbank, nicht der statischen Doku.

---

## Verwandte Seiten

- [Member-Dashboard-Widgets](../themes-design/DASHBOARD-WIDGETS.md)
- [Analytics](../seo/ANALYTICS.md)
- [Bestellungen & Zuweisung](../subscription/ORDERS.md)
