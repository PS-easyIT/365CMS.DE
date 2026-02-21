# 365CMS – Media-Management Roadmap

**Bereich:** Medien-Verwaltung, Bilder, Video, DAM, CDN  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 1. Upload-System

### 🔴 M-01 · Upload-Engine
| Stufe | Feature |
|---|---|
| Stufe 1 | Chunked-Upload (große Dateien in Teilen hochladen) |
| Stufe 2 | Paralleler Multi-File-Upload |
| Stufe 3 | Upload-Fortschrittsbalken mit Cancel-Option |
| Stufe 4 | Drag & Drop Zonen (gesamte Seite als Drop-Target) |
| Stufe 5 | Paste-Upload (STRG+V fügt Screenshot ein) |
| Stufe 6 | URL-Import (Bild von externer URL importieren) |
| Stufe 7 | Resume-Upload (unterbrochene Uploads fortsetzen) |

**Unterstützte Dateitypen:**
| Kategorie | Formate |
|---|---|
| Bilder | JPEG, PNG, WebP, AVIF, GIF, SVG (sanitized), HEIC |
| Dokumente | PDF, DOCX, XLSX, PPTX, TXT, CSV |
| Video | MP4, WebM, MOV, AVI (mit Konvertierungs-Hook) |
| Audio | MP3, WAV, OGG, M4A |
| Archive | ZIP, RAR, 7z (für Download-Pakete) |
| Code | JSON, XML, CSV |

---

## 2. Bildoptimierung

### 🔴 M-02 · Automatische Bildoptimierung
| Stufe | Feature |
|---|---|
| Stufe 1 | Automatische WebP-Konvertierung bei Upload |
| Stufe 2 | AVIF-Generierung als nächste Generation |
| Stufe 3 | Responsive-Bilder (mehrere Größen: 320, 640, 1024, 1920px) |
| Stufe 4 | Intelligentes Cropping (Gesichtserkennung für Ausschnitt) |
| Stufe 5 | Lazy Loading (native `loading="lazy"` + IntersectionObserver) |
| Stufe 6 | Blur-Placeholder (Base64-LQIP) |
| Stufe 7 | Verlustfreie vs. verlustbehaftete Kompression pro MIME-Type |
| Stufe 8 | Batch-Reoptimierung bestehender Bilder (Hintergrund-Job) |

---

### 🟠 M-03 · Bild-Editor im Browser
| Stufe | Feature |
|---|---|
| Stufe 1 | Freihand-Crop (Seitenverhältnis frei) |
| Stufe 2 | Preset-Crops (1:1, 16:9, 4:3, 3:2) |
| Stufe 3 | Drehen und Spiegeln |
| Stufe 4 | Grundlegende Filter (Helligkeit, Kontrast, Sättigung) |
| Stufe 5 | Wasserzeichen-Overlay (Logo oder Text) |
| Stufe 6 | Verpixelung/Unschärfe für sensible Bereiche |
| Stufe 7 | Hintergrund-Entfernung (KI-basiert) |
| Stufe 8 | KI-Upscaling (niedrigauflösende Bilder vergrößern) |

---

## 3. Medienverwaltung

### 🟠 M-04 · Ordner-System
| Stufe | Feature |
|---|---|
| Stufe 1 | Virtuelle Ordner (ohne physische Verzeichnisse) |
| Stufe 2 | Drag & Drop zum Verschieben in Ordner |
| Stufe 3 | Ordner-Berechtigungen (Nur bestimmte Rollen sehen Ordner X) |
| Stufe 4 | Smart-Ordner (automatisch nach Typ/Datum/Tag befüllt) |
| Stufe 5 | Ordner als Kollektionen (wie Adobe Bridge) |
| Stufe 6 | Ordner-Abonnement (Benachrichtigung bei neuen Dateien) |

---

### 🟠 M-05 · Metadaten & Tagging
| Stufe | Feature |
|---|---|
| Stufe 1 | Tags für alle Medien |
| Stufe 2 | Alt-Text und Beschriftung (DSGVO: kein Namen in Alt-Text) |
| Stufe 3 | EXIF-Daten anzeigen und löschen |
| Stufe 4 | Copyright-Informationen |
| Stufe 5 | Automatische KI-Tags (Bildinhalt erkennen) |
| Stufe 6 | Farb-Extraktion (Dominant-Farbe als Meta-Information) |
| Stufe 7 | Benutzerdefinierte Meta-Felder pro Medientyp |

---

### 🟠 M-06 · Suche & Filter
| Stufe | Feature |
|---|---|
| Stufe 1 | Volltextsuche in Dateinamen, Alt-Text, Tags |
| Stufe 2 | Filter: Dateityp, Größe, Datum, Ordner |
| Stufe 3 | Duplikat-Erkennung (Hash-basiert) |
| Stufe 4 | Ähnliche-Bilder-Suche (visuelle Ähnlichkeit) |
| Stufe 5 | Unbenutzte Medien finden (nicht in Inhalten referenziert) |

---

## 4. CDN & externe Speicherung

### 🟡 M-07 · CDN-Integration
| Stufe | Feature |
|---|---|
| Stufe 1 | CDN-URL-Rewriting (alle Medien-URLs → CDN-URL) |
| Stufe 2 | Push-CDN-Invalidierung bei Medien-Update |
| Stufe 3 | Cloudflare-Images-Integration (Auto-Optimierung via CF) |
| Stufe 4 | Imgix/Cloudinary-Adapter |
| Stufe 5 | Multi-CDN-Strategie (geografische Verteilung) |

---

### 🟡 M-08 · Objekt-Speicher
| Stufe | Feature |
|---|---|
| Stufe 1 | S3-Abstraktion (Adapter-Pattern für verschiedene Anbieter) |
| Stufe 2 | Automatischer Upload zu Objekt-Speicher nach lokaler Kopie |
| Stufe 3 | Off-loading (lokale Datei nach Upload löschen) |
| Stufe 4 | Pre-signed Download-URLs (zeitlich begrenzt) |
| Stufe 5 | Verschlüsselte Objekte (Server-Side Encryption) |

---

## 5. Video & Audio

### 🟡 M-09 · Video-Management
| Stufe | Feature |
|---|---|
| Stufe 1 | Video-Player (anpassbarer, DSGVO-konformer Player) |
| Stufe 2 | Automatische Thumbnail-Generierung (FFmpeg) |
| Stufe 3 | Video-Qualitätswahl (720p, 1080p, 4K) |
| Stufe 4 | HLS-Streaming (adaptives Bitrate-Streaming) |
| Stufe 5 | Untertitel/Captions (SRT/VTT-Upload) |
| Stufe 6 | Video-Kapitel-Marker |
| Stufe 7 | Passwortgeschützte Videos |

---

### 🟡 M-10 · Audio-Management
| Stufe | Feature |
|---|---|
| Stufe 1 | Audio-Player (anpassbar) |
| Stufe 2 | Podcast-Feed-Generierung (RSS 2.0 mit iTunes-Tags) |
| Stufe 3 | Transkriptions-Integration (via Whisper API) |
| Stufe 4 | Kapitel-Marker für Podcasts |

---

## 6. Digital Asset Management (DAM)

### 🟢 M-11 · DAM-Features
| Stufe | Feature |
|---|---|
| Stufe 1 | Asset-Rights-Management (wer darf was wann nutzen) |
| Stufe 2 | Ablaufdaten für Medienlizenzen |
| Stufe 3 | Brand-Asset-Manager (offizielle Logos, Farben, Fonts) |
| Stufe 4 | Medien-Kollektionen als Packages (ZIP-Download mehrerer Assets) |
| Stufe 5 | Medien-Preview in verschiedenen Kontexten |

---

*Letzte Aktualisierung: 19. Februar 2026*
