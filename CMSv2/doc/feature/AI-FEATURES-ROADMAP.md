# 365CMS – KI & Automatisierungs-Roadmap

**Bereich:** Künstliche Intelligenz, Machine Learning, Automatisierung, LLM-Integration  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Grundsatz: KI als Assistent, nicht als Ersatz
Alle KI-Features sind:
- **Optional** – klassische Nutzung bleibt vollständig möglich
- **Transparent** – KI-generierter Content wird gekennzeichnet
- **Kontrollierbar** – Admin kann KI-Features per Plugin deaktivieren
- **Datenschutzkonform** – keine Weitergabe von Nutzerdaten ohne Einwilligung

---

## 1. Content-KI

### 🟠 AI-01 · KI-Schreibassistent
| Stufe | Feature |
|---|---|
| Stufe 1 | Text verbessern (Grammatik, Stil, Formulierung) |
| Stufe 2 | Text kürzen / ausbauen (auf gewünschte Länge) |
| Stufe 3 | Ton anpassen (formal, locker, technisch, emotional) |
| Stufe 4 | Text übersetzen (50+ Sprachen) |
| Stufe 5 | Artikel-Outline erstellen (Struktur aus Keyword) |
| Stufe 6 | Vollständige Artikelentwürfe generieren |
| Stufe 7 | Meta-Beschreibungen und SEO-Titles generieren |
| Stufe 8 | Social-Media-Posts aus Artikel-Content ableiten |
| Stufe 9 | E-Mail-Betreffzeilen-Vorschläge |

**Unterstützte Provider:**
- OpenAI (GPT-4o)
- Anthropic (Claude)
- Google Gemini
- Lokale Modelle (Ollama, LM Studio)

---

### 🟡 AI-02 · KI-Bild-Generierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Featured-Image generieren (aus Artikel-Titel) |
| Stufe 2 | Variations einer bestehenden Grafik |
| Stufe 3 | Hintergrundentfernung |
| Stufe 4 | Bild-Beschreibung generieren (Alt-Text) |
| Stufe 5 | Bild upscaling (2x, 4x via Real-ESRGAN) |

---

### 🟡 AI-03 · Inhalts-Klassifizierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Automatische Tag-Vorschläge für Artikel |
| Stufe 2 | Kategorie-Vorhersage |
| Stufe 3 | Sentiment-Analyse (positiv, negativ, neutral) |
| Stufe 4 | Lesbarkeits-Score (Flesch-Kincaid) |
| Stufe 5 | Inhalts-Moderation (KI-Filter für unerwünschte Inhalte) |

---

## 2. Suche & Empfehlungen

### 🟠 AI-04 · Semantische Suche
| Stufe | Feature |
|---|---|
| Stufe 1 | Embedding-Generierung für alle Inhalte |
| Stufe 2 | Vektor-Datenbank (pgvector oder Qdrant) |
| Stufe 3 | Semantische Ähnlichkeits-Suche (nicht keyword-basiert) |
| Stufe 4 | Hybride Suche (keyword + semantisch kombiniert) |
| Stufe 5 | Frage-Antwort-System (RAG – Retrieval Augmented Generation) |
| Stufe 6 | "Frag deine Inhalte" (Chat-Interface über eigene Daten) |

---

### 🟡 AI-05 · Recommendations Engine
| Stufe | Feature |
|---|---|
| Stufe 1 | Ähnliche Artikel (inhaltsbasiert) |
| Stufe 2 | Ähnliche Experten / Produkte |
| Stufe 3 | Collaborative Filtering (Nutzer X hat auch Y gesehen) |
| Stufe 4 | Personalisierte Homepage basierend auf Nutzerprofil |
| Stufe 5 | "Das könnte Sie interessieren" im Member-Dashboard |
| Stufe 6 | E-Mail-Empfehlungen (wöchentliche personalisierte Zusammenfassung) |

---

## 3. Automatisierung & Workflows

### 🟡 AI-06 · No-Code-Automatisierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Trigger-Aktions-System (Wenn X, dann Y) |
| Stufe 2 | Trigger-Bibliothek (Formular eingereicht, User registriert, Produkt gekauft) |
| Stufe 3 | Aktions-Bibliothek (E-Mail senden, Tag hinzufügen, Webhook feuern) |
| Stufe 4 | Bedingungen (Wenn-Dann-Sonst) |
| Stufe 5 | Zeitverzögerungen (Wait X Stunden/Tage) |
| Stufe 6 | Visuelle Workflow-Builder-UI |
| Stufe 7 | Pre-built Workflows (Onboarding-Sequenz, Warenkorb-Abbruch) |

---

### 🟡 AI-07 · KI-Chatbot / FAQ-Bot
| Stufe | Feature |
|---|---|
| Stufe 1 | Basis-Chatbot mit vorkonfigurierten Antworten |
| Stufe 2 | RAG-Chatbot (antwortet aus eigenem Wiki/FAQ) |
| Stufe 3 | Übergabe an menschlichen Support |
| Stufe 4 | Konversations-Log und Zusammenfassung |
| Stufe 5 | Mehrsprachiger Bot |
| Stufe 6 | Voice-Bot (Text-to-Speech + Speech-to-Text) |

---

## 4. Analytics & Predictions

### 🟢 AI-08 · Predictive Analytics
| Stufe | Feature |
|---|---|
| Stufe 1 | Churn-Prediction (Welche Nutzer drohen abzuspringen) |
| Stufe 2 | Revenue-Forecast (Umsatz nächste 30/90 Tage) |
| Stufe 3 | Content-Performance-Prediction (wird dieser Artikel gut performen) |
| Stufe 4 | Beste-Versand-Zeit-Vorschlag für Newsletter |
| Stufe 5 | Anomalie-Erkennung in Traffic/Umsatz |

---

## 5. Moderation & Sicherheit

### 🟠 AI-09 · KI-Moderation
| Stufe | Feature |
|---|---|
| Stufe 1 | Spam-Erkennung für Kommentare (ML-Modell) |
| Stufe 2 | Toxic-Content-Filter (Hassrede, Beleidigungen) |
| Stufe 3 | Deepfake-Erkennung für Upload-Bilder |
| Stufe 4 | Bot-Erkennung bei Registrierung und Formularen |
| Stufe 5 | Automatische Profil-Verifizierungs-Hilfe |

---

## 6. KI-Infrastruktur

### 🟡 AI-10 · KI-Provider-Abstraktion
| Stufe | Feature |
|---|---|
| Stufe 1 | Einheitliches KI-Interface (Provider austauschbar) |
| Stufe 2 | Fallback-Chain (Provider A → B wenn A ausfällt) |
| Stufe 3 | Cost-Tracking pro KI-Feature |
| Stufe 4 | Rate-Limit-Schutz vor KI-API-Kosten |
| Stufe 5 | Lokale LLM-Option (keine API-Abhängigkeit, Datenschutz) |
| Stufe 6 | Prompt-Management (Prompt-Templates versioniert) |
| Stufe 7 | KI-Feature-Flags (einzelne Features ein/ausschalten) |

---

*Letzte Aktualisierung: 19. Februar 2026*
