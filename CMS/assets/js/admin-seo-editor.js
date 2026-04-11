(function () {
    'use strict';

    function toLower(value) {
        return String(value || '').toLocaleLowerCase('de-DE');
    }

    function wordCount(text) {
        var match = String(text || '').match(/[\p{L}\p{N}\-]+/gu);
        return match ? match.length : 0;
    }

    function splitSentences(text) {
        return String(text || '').split(/(?<=[.!?])\s+/u).map(function (item) {
            return item.trim();
        }).filter(Boolean);
    }

    function splitParagraphs(text) {
        var items = String(text || '').split(/\n\s*\n|\r\n\s*\r\n/u).map(function (item) {
            return item.trim();
        }).filter(Boolean);
        return items.length ? items : [String(text || '').trim()].filter(Boolean);
    }

    function parseEditorJson(raw) {
        var analysis = analyzeEditorJson(raw);
        return analysis ? analysis.plainText : raw;
    }

    function normalizeVisibleText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function buildEmptyAnalysis() {
        return {
            plainText: '',
            paragraphs: [],
            links: { internal: 0, external: 0 },
            images: { count: 0, missing: 0 }
        };
    }

    function pushParagraph(analysis, value) {
        var text = normalizeVisibleText(value);
        if (text !== '') {
            analysis.paragraphs.push(text);
        }
    }

    function finalizeAnalysis(analysis) {
        analysis.plainText = normalizeVisibleText(analysis.paragraphs.join(' '));
        return analysis;
    }

    function classifyHref(href) {
        var value = String(href || '').trim();

        if (value === '') {
            return '';
        }

        if (value.indexOf('/') === 0) {
            return 'internal';
        }

        try {
            if (window.location) {
                var resolved = new URL(value, window.location.origin);
                return resolved.origin === window.location.origin ? 'internal' : 'external';
            }
        } catch (_error) {
            // ignore and fall back below
        }

        return /^(https?:|mailto:|tel:)/i.test(value) ? 'external' : '';
    }

    function parseHtmlDocument(html) {
        if (typeof DOMParser === 'function') {
            return new DOMParser().parseFromString(String(html || ''), 'text/html');
        }

        var fallbackDocument = document.implementation.createHTMLDocument('');
        fallbackDocument.body.textContent = String(html || '');
        return fallbackDocument;
    }

    function clearElement(element) {
        if (!element) {
            return;
        }

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function collectHtmlAnalysis(html, analysis, addParagraph) {
        var htmlDocument = parseHtmlDocument(html);
        var root = htmlDocument.body || htmlDocument;

        root.querySelectorAll('a[href]').forEach(function (link) {
            var kind = classifyHref(link.getAttribute('href') || '');
            if (kind === 'internal') {
                analysis.links.internal += 1;
            } else if (kind === 'external') {
                analysis.links.external += 1;
            }
        });

        root.querySelectorAll('img').forEach(function (image) {
            analysis.images.count += 1;
            if (normalizeVisibleText(image.getAttribute('alt') || '') === '') {
                analysis.images.missing += 1;
            }
        });

        if (addParagraph) {
            pushParagraph(analysis, root.textContent || '');
        }
    }

    function collectListItemAnalysis(item, analysis) {
        if (typeof item === 'string') {
            collectHtmlAnalysis(item, analysis, true);
            return;
        }

        if (!item || typeof item !== 'object') {
            return;
        }

        ['content', 'text'].forEach(function (key) {
            if (typeof item[key] === 'string') {
                collectHtmlAnalysis(item[key], analysis, true);
            }
        });

        if (Array.isArray(item.items)) {
            item.items.forEach(function (child) {
                collectListItemAnalysis(child, analysis);
            });
        }
    }

    function collectImageAnalysis(candidate, analysis) {
        var hasImage = false;
        var altText = '';

        if (!candidate || typeof candidate !== 'object') {
            return;
        }

        hasImage = Boolean(
            candidate.url
            || candidate.src
            || (candidate.file && (candidate.file.url || candidate.file.src || candidate.file.path))
        );

        if (!hasImage) {
            return;
        }

        altText = normalizeVisibleText(candidate.alt || candidate.caption || (candidate.file && candidate.file.alt) || '');
        analysis.images.count += 1;
        if (altText === '') {
            analysis.images.missing += 1;
        }

        if (typeof candidate.caption === 'string') {
            collectHtmlAnalysis(candidate.caption, analysis, true);
        }
    }

    function collectEditorBlockAnalysis(block, analysis) {
        var type = String(block && block.type || '');
        var data = block && block.data && typeof block.data === 'object' ? block.data : {};

        switch (type) {
            case 'paragraph':
            case 'header':
            case 'quote':
            case 'warning':
            case 'callout':
            case 'mediaText':
            case 'raw':
                ['text', 'caption', 'title', 'message', 'html'].forEach(function (key) {
                    if (typeof data[key] === 'string') {
                        collectHtmlAnalysis(data[key], analysis, true);
                    }
                });
                break;
            case 'checklist':
                (Array.isArray(data.items) ? data.items : []).forEach(function (item) {
                    if (item && typeof item === 'object' && typeof item.text === 'string') {
                        collectHtmlAnalysis(item.text, analysis, true);
                    }
                });
                break;
            case 'list':
                (Array.isArray(data.items) ? data.items : []).forEach(function (item) {
                    collectListItemAnalysis(item, analysis);
                });
                break;
            case 'image':
                collectImageAnalysis(data, analysis);
                break;
            case 'imageGallery':
            case 'carousel':
                (Array.isArray(data.items) ? data.items : []).forEach(function (item) {
                    collectImageAnalysis(item, analysis);
                });
                break;
            case 'linkTool':
            case 'link':
                ['link', 'url', 'href'].forEach(function (key) {
                    if (typeof data[key] === 'string') {
                        var kind = classifyHref(data[key]);
                        if (kind === 'internal') {
                            analysis.links.internal += 1;
                        } else if (kind === 'external') {
                            analysis.links.external += 1;
                        }
                    }
                });
                ['title', 'description', 'caption', 'text'].forEach(function (key) {
                    if (typeof data[key] === 'string') {
                        collectHtmlAnalysis(data[key], analysis, true);
                    }
                });
                break;
            default:
                Object.keys(data).forEach(function (key) {
                    if (/^(?:url|href|src|path|id|type|level|alignment|style|preset|target|rel)$/i.test(key)) {
                        return;
                    }

                    if (typeof data[key] === 'string') {
                        collectHtmlAnalysis(data[key], analysis, true);
                    }
                });
                break;
        }
    }

    function analyzeEditorJson(raw) {
        try {
            var parsed = JSON.parse(raw);

            if (!parsed || !Array.isArray(parsed.blocks)) {
                return null;
            }

            var analysis = buildEmptyAnalysis();
            parsed.blocks.forEach(function (block) {
                collectEditorBlockAnalysis(block, analysis);
            });

            return finalizeAnalysis(analysis);
        } catch (_error) {
            return null;
        }
    }

    function extractPlainText(raw, editorContainer) {
        var analysis = analyzeContent(raw, editorContainer);
        return analysis.plainText;
    }

    function analyzeContent(raw, editorContainer) {
        var analysis = buildEmptyAnalysis();
        var source = String(raw || '').trim();

        if (source && (source.charAt(0) === '{' || source.charAt(0) === '[')) {
            var jsonAnalysis = analyzeEditorJson(source);
            if (jsonAnalysis) {
                return jsonAnalysis;
            }
        }

        if (editorContainer && editorContainer.querySelectorAll) {
            editorContainer.querySelectorAll('a[href]').forEach(function (link) {
                var kind = classifyHref(link.getAttribute('href') || '');
                if (kind === 'internal') {
                    analysis.links.internal += 1;
                } else if (kind === 'external') {
                    analysis.links.external += 1;
                }
            });

            editorContainer.querySelectorAll('img').forEach(function (image) {
                analysis.images.count += 1;
                if (normalizeVisibleText(image.getAttribute('alt') || '') === '') {
                    analysis.images.missing += 1;
                }
            });

            Array.prototype.slice.call(editorContainer.querySelectorAll('.ce-block')).forEach(function (block) {
                pushParagraph(analysis, block.textContent || '');
            });
        }

        if (analysis.paragraphs.length > 0 || analysis.links.internal > 0 || analysis.links.external > 0 || analysis.images.count > 0) {
            return finalizeAnalysis(analysis);
        }

        if (!source) {
            return finalizeAnalysis(analysis);
        }

        collectHtmlAnalysis(source, analysis, true);
        return finalizeAnalysis(analysis);
    }

    function extractFirstParagraph(analysis) {
        if (!analysis || !Array.isArray(analysis.paragraphs) || analysis.paragraphs.length === 0) {
            return '';
        }

        return String(analysis.paragraphs[0] || '').slice(0, 155).trim();
    }

    function containsPhrase(text, phrase) {
        return countPhrase(text, phrase) > 0;
    }

    function isPhraseBoundary(text, index, length) {
        var previous = index > 0 ? text.charAt(index - 1) : '';
        var next = index + length < text.length ? text.charAt(index + length) : '';

        return !/[\p{L}\p{N}_]/u.test(previous) && !/[\p{L}\p{N}_]/u.test(next);
    }

    function countPhrase(text, phrase) {
        var haystack;
        var needle;
        var count = 0;
        var offset = 0;
        var index;

        if (!phrase) {
            return 0;
        }

        haystack = toLower(text);
        needle = toLower(phrase).trim();

        if (!needle) {
            return 0;
        }

        while ((index = haystack.indexOf(needle, offset)) !== -1) {
            if (isPhraseBoundary(haystack, index, needle.length)) {
                count += 1;
            }
            offset = index + needle.length;
        }

        return count;
    }

    function countTransitionWords(text) {
        var words = ['außerdem', 'zudem', 'darüber hinaus', 'deshalb', 'daher', 'allerdings', 'jedoch', 'dennoch', 'somit', 'folglich', 'beispielsweise', 'anschließend', 'gleichzeitig', 'insbesondere', 'schließlich', 'weiterhin'];
        return words.reduce(function (sum, word) {
            return sum + countPhrase(text, word);
        }, 0);
    }

    function countPassive(text) {
        var patterns = [/\bwird\b[^.!?]{0,40}\b(?:von|durch|worden)\b/giu, /\bwurden\b/giu, /\bworden\b/giu];
        return patterns.reduce(function (sum, pattern) {
            var matches = String(text || '').match(pattern);
            return sum + (matches ? matches.length : 0);
        }, 0);
    }

    function countLongSentences(sentences, maxWords) {
        return sentences.filter(function (sentence) {
            return wordCount(sentence) > maxWords;
        }).length;
    }

    function countLongParagraphs(paragraphs, maxWords) {
        return paragraphs.filter(function (paragraph) {
            return wordCount(paragraph) > maxWords;
        }).length;
    }

    function extractLinks(raw, editorContainer) {
        return analyzeContent(raw, editorContainer).links;
    }

    function extractImageAltInfo(raw, editorContainer) {
        return analyzeContent(raw, editorContainer).images;
    }

    function renderRuleList(rulesList, rules) {
        if (!rulesList) {
            return;
        }

        clearElement(rulesList);
        rules.forEach(function (rule) {
            var item = document.createElement('div');
            var content = document.createElement('span');
            var title = document.createElement('strong');
            var detail = document.createElement('small');
            var badge = document.createElement('span');

            item.className = 'd-flex justify-content-between gap-3 py-1 border-bottom';
            content.className = 'd-inline-flex flex-column';
            title.textContent = rule.label;
            detail.className = 'text-secondary';
            detail.textContent = rule.detail;
            badge.className = 'badge ' + (rule.passed ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning');
            badge.textContent = rule.passed ? 'OK' : 'Offen';

            content.appendChild(title);
            content.appendChild(detail);
            item.appendChild(content);
            item.appendChild(badge);
            rulesList.appendChild(item);
        });
    }

    function resolveMetaTitle(metaTitle, title, siteName, format, separator) {
        if (metaTitle) {
            return metaTitle;
        }
        return String(format || '%%title%% %%sep%% %%sitename%%')
            .replace(/%%title%%|%title%/g, title || '')
            .replace(/%%sitename%%|%sitename%/g, siteName || '')
            .replace(/%%sep%%|%sep%/g, separator || '|')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function createRule(key, label, passed, detail, weight) {
        return { key: key, label: label, passed: passed, detail: detail, weight: weight };
    }

    function init(config) {
        var form = document.getElementById(config.formId);
        if (!form) {
            return;
        }

        var titleInput = document.getElementById(config.titleId);
        var slugInput = document.getElementById(config.slugId);
        var metaTitleInput = document.getElementById(config.metaTitleId);
        var metaDescInput = document.getElementById(config.metaDescId);
        var focusInput = document.getElementById(config.focusKeyphraseId);
        var ogTitleInput = document.getElementById(config.ogTitleId);
        var ogDescriptionInput = document.getElementById(config.ogDescriptionId);
        var ogImageInput = document.getElementById(config.ogImageId);
        var twitterTitleInput = document.getElementById(config.twitterTitleId);
        var twitterDescriptionInput = document.getElementById(config.twitterDescriptionId);
        var twitterImageInput = document.getElementById(config.twitterImageId);
        var featuredInput = document.getElementById(config.featuredImageId);
        var statusInput = document.getElementById(config.statusId);
        var contentInput = document.getElementById(config.contentInputId);
        var editorContainer = document.getElementById(config.editorContainerId);
        var previewTitle = document.getElementById(config.serpTitleId);
        var previewUrl = document.getElementById(config.serpUrlId);
        var previewDesc = document.getElementById(config.serpDescriptionId);
        var progressBar = document.getElementById(config.scoreBarId);
        var scoreLabel = document.getElementById(config.scoreLabelId);
        var scoreBadge = document.getElementById(config.scoreBadgeId);
        var rulesList = document.getElementById(config.scoreRulesId);
        var socialTitle = document.getElementById(config.socialTitleId);
        var socialDesc = document.getElementById(config.socialDescriptionId);
        var socialImage = document.getElementById(config.socialImageId);
        var warningBox = document.getElementById(config.publishWarningId);
        var slugState = document.getElementById(config.slugStateId);
        var wordCountLabel = document.getElementById(config.wordCountId);
        var densityLabel = document.getElementById(config.densityId);
        var internalLinksLabel = document.getElementById(config.internalLinksId);
        var externalLinksLabel = document.getElementById(config.externalLinksId);
        var transitionWordsLabel = document.getElementById(config.transitionWordsId);
        var longSentencesLabel = document.getElementById(config.longSentencesId);
        var longParagraphsLabel = document.getElementById(config.longParagraphsId);
        var missingAltLabel = document.getElementById(config.missingAltId);
        var readabilityBadge = document.getElementById(config.readabilityBadgeId);
        var readabilitySummary = document.getElementById(config.readabilitySummaryId);

        var update = function () {
            var title = titleInput ? titleInput.value.trim() : '';
            var slug = slugInput ? slugInput.value.trim().replace(/^\/+/, '') : '';
            var metaTitle = metaTitleInput ? metaTitleInput.value.trim() : '';
            var metaDesc = metaDescInput ? metaDescInput.value.trim() : '';
            var focusPhrase = focusInput ? focusInput.value.trim().split(/[,;\n]+/)[0].trim() : '';
            var ogTitle = ogTitleInput ? ogTitleInput.value.trim() : '';
            var ogDescription = ogDescriptionInput ? ogDescriptionInput.value.trim() : '';
            var rawContent = contentInput ? contentInput.value : '';
            var analysis = analyzeContent(rawContent, editorContainer);
            var plainText = analysis.plainText;
            var resolvedTitle = resolveMetaTitle(metaTitle, title, config.siteName, config.siteTitleFormat, config.titleSeparator);
            var resolvedDesc = metaDesc || extractFirstParagraph(analysis);
            var words = wordCount(plainText);
            var density = focusPhrase ? (countPhrase(plainText, focusPhrase) / Math.max(words, 1)) * 100 : 0;
            var intro = plainText.slice(0, Math.max(120, Math.round(plainText.length * 0.1)));
            var sentences = splitSentences(plainText);
            var paragraphs = analysis.paragraphs.length ? analysis.paragraphs : splitParagraphs(plainText);
            var links = analysis.links;
            var images = analysis.images;
            var transitionCount = countTransitionWords(plainText);
            var passiveCount = countPassive(plainText);
            var longSentences = countLongSentences(sentences, config.maxSentenceWords || 24);
            var longParagraphs = countLongParagraphs(paragraphs, config.maxParagraphWords || 120);
            var previewHref = (function () {
                var template = String(config.previewUrlTemplate || '');
                var placeholderSlug = String(config.previewPlaceholderSlug || 'beitrag');
                if (template !== '' && template.indexOf('{slug}') !== -1) {
                    return template.replace(/\{slug\}/g, slug ? slug : placeholderSlug);
                }

                return (config.previewBaseUrl || '') + (slug ? slug : '…');
            })();
            var ogImage = (ogImageInput && ogImageInput.value.trim()) || (twitterImageInput && twitterImageInput.value.trim()) || (featuredInput && featuredInput.value.trim()) || '';
            var socialResolvedTitle = ogTitle || (twitterTitleInput && twitterTitleInput.value.trim()) || resolvedTitle || config.siteName;
            var socialResolvedDesc = ogDescription || (twitterDescriptionInput && twitterDescriptionInput.value.trim()) || resolvedDesc || 'Social Preview';

            var rules = [
                createRule('meta_title', 'Meta-Titel', resolvedTitle.length >= 30 && resolvedTitle.length <= 60, resolvedTitle.length + ' Zeichen', 10),
                createRule('meta_description', 'Meta-Beschreibung', resolvedDesc.length >= 120 && resolvedDesc.length <= 155, resolvedDesc.length + ' Zeichen', 10),
                createRule('focus', 'Fokus-Keyphrase', !!focusPhrase, focusPhrase || 'fehlt', 10),
                createRule('title', 'Keyphrase im Titel', focusPhrase ? containsPhrase(title, focusPhrase) : false, focusPhrase ? 'Titel geprüft' : 'ohne Keyphrase', 8),
                createRule('slug', 'Keyphrase im Slug', focusPhrase ? containsPhrase(slug.replace(/-/g, ' '), focusPhrase) : false, slug || 'leer', 8),
                createRule('intro', 'Keyphrase in Einleitung', focusPhrase ? containsPhrase(intro, focusPhrase) : false, intro ? 'Einleitung geprüft' : 'kein Text', 6),
                createRule('density', 'Keyphrase-Dichte', focusPhrase ? density >= 0.5 && density <= 3.5 : false, density.toFixed(2) + '%', 8),
                createRule('length', 'Textlänge', words >= (config.minWords || 300), words + ' Wörter', 8),
                createRule('internal', 'Interne Links', links.internal >= 1, links.internal + ' intern', 6),
                createRule('external', 'Externe Links', links.external >= 1, links.external + ' extern', 6),
                createRule('alt', 'Bild-Alt-Texte', images.count === 0 || images.missing === 0, images.count === 0 ? 'keine Bilder' : images.missing + ' ohne Alt', 6),
                createRule('sentences', 'Satzlänge', longSentences <= 3, longSentences + ' lange Sätze', 6),
                createRule('paragraphs', 'Absatzlänge', longParagraphs <= 2, longParagraphs + ' lange Absätze', 6),
                createRule('passive', 'Passive Formulierungen', passiveCount <= 3, passiveCount + ' Marker', 4),
                createRule('transition', 'Signalwörter', transitionCount >= 2, transitionCount + ' Wörter', 4),
                createRule('og', 'OG-/Social-Bild', !!ogImage, ogImage ? 'vorhanden' : 'fehlt', 10)
            ];

            var totalWeight = rules.reduce(function (sum, rule) { return sum + rule.weight; }, 0) || 1;
            var passedWeight = rules.reduce(function (sum, rule) { return sum + (rule.passed ? rule.weight : 0); }, 0);
            var score = Math.round((passedWeight / totalWeight) * 100);
            var status = score >= 80 ? 'green' : (score >= 55 ? 'orange' : 'red');

            if (previewTitle) {
                previewTitle.textContent = resolvedTitle || config.siteName;
            }
            if (previewUrl) {
                previewUrl.textContent = previewHref;
            }
            if (previewDesc) {
                previewDesc.textContent = resolvedDesc || 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.';
            }
            if (socialTitle) {
                socialTitle.textContent = socialResolvedTitle;
            }
            if (socialDesc) {
                socialDesc.textContent = socialResolvedDesc;
            }
            if (socialImage) {
                socialImage.src = ogImage || config.fallbackImage || '';
                socialImage.style.display = socialImage.src ? 'block' : 'none';
            }
            if (progressBar) {
                progressBar.style.width = score + '%';
                progressBar.className = 'progress-bar bg-' + (status === 'green' ? 'success' : (status === 'orange' ? 'warning' : 'danger'));
            }
            if (scoreLabel) {
                scoreLabel.textContent = String(score);
            }
            if (scoreBadge) {
                scoreBadge.className = 'badge ' + (status === 'green' ? 'bg-success-lt text-success' : (status === 'orange' ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger'));
                scoreBadge.textContent = status === 'green' ? 'Grün' : (status === 'orange' ? 'Orange' : 'Rot');
            }
            if (rulesList) {
                renderRuleList(rulesList, rules);
            }
            if (slugState) {
                var slugValid = slug === '' || /^[a-z0-9\-]+$/.test(slug);
                slugState.className = 'badge ' + (slugValid ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger');
                slugState.textContent = slugValid ? 'Slug gültig' : 'Slug ungültig';
            }
            if (wordCountLabel) {
                wordCountLabel.textContent = String(words);
            }
            if (densityLabel) {
                densityLabel.textContent = density.toFixed(2) + '%';
            }
            if (internalLinksLabel) {
                internalLinksLabel.textContent = String(links.internal);
            }
            if (externalLinksLabel) {
                externalLinksLabel.textContent = String(links.external);
            }
            if (transitionWordsLabel) {
                transitionWordsLabel.textContent = String(transitionCount);
            }
            if (longSentencesLabel) {
                longSentencesLabel.textContent = String(longSentences);
            }
            if (longParagraphsLabel) {
                longParagraphsLabel.textContent = String(longParagraphs);
            }
            if (missingAltLabel) {
                missingAltLabel.textContent = String(images.missing);
            }
            if (readabilityBadge) {
                var readabilityGood = longSentences <= 3 && longParagraphs <= 2 && passiveCount <= 3 && transitionCount >= 2;
                var readabilityWarn = !readabilityGood && (longSentences <= 5 && longParagraphs <= 4);
                readabilityBadge.className = 'badge ' + (readabilityGood ? 'bg-success-lt text-success' : (readabilityWarn ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger'));
                readabilityBadge.textContent = readabilityGood ? 'Gut lesbar' : (readabilityWarn ? 'Optimierbar' : 'Kritisch');
            }
            if (readabilitySummary) {
                readabilitySummary.textContent = words + ' Wörter · ' + longSentences + ' lange Sätze · ' + longParagraphs + ' lange Absätze';
            }
            if (warningBox) {
                var requiredMissing = [];
                if (!metaTitle) { requiredMissing.push('Meta-Titel'); }
                if (!metaDesc) { requiredMissing.push('Meta-Beschreibung'); }
                if (!ogImage) { requiredMissing.push('OG-Bild'); }
                warningBox.textContent = requiredMissing.length ? 'Vor Veröffentlichung fehlen: ' + requiredMissing.join(', ') : 'SEO-Pflichtfelder für die Veröffentlichung sind gesetzt.';
                warningBox.className = 'alert ' + (requiredMissing.length ? 'alert-warning' : 'alert-success');
                warningBox.dataset.missingRequired = requiredMissing.length ? '1' : '0';
            }

            form.dataset.seoScore = String(score);
        };

        [titleInput, slugInput, metaTitleInput, metaDescInput, focusInput, ogTitleInput, ogDescriptionInput, ogImageInput, twitterTitleInput, twitterDescriptionInput, twitterImageInput, featuredInput, statusInput, contentInput].forEach(function (el) {
            if (!el) {
                return;
            }
            el.addEventListener('input', update);
            el.addEventListener('change', update);
        });

        if (editorContainer && window.MutationObserver) {
            new MutationObserver(update).observe(editorContainer, { childList: true, subtree: true, characterData: true });
        }

        form.addEventListener('submit', function () {
            update();
        });

        update();
    }

    window.cmsSeoEditor = { init: init };
})();
