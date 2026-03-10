(function () {
    'use strict';

    var config = window.CMS_WEB_VITALS_CONFIG || {};
    if (!config.endpoint) {
        return;
    }

    var sampleRate = Number(config.sampleRate || 100);
    if (!Number.isFinite(sampleRate) || sampleRate <= 0) {
        return;
    }

    if (sampleRate < 100 && Math.random() * 100 > sampleRate) {
        return;
    }

    var state = {
        cls: 0,
        inp: null,
        lcp: null,
        ttfb: null,
        sent: false
    };

    function trackTtfb() {
        if (!performance || typeof performance.getEntriesByType !== 'function') {
            return;
        }

        var nav = performance.getEntriesByType('navigation')[0];
        if (nav && Number.isFinite(nav.responseStart) && nav.responseStart > 0) {
            state.ttfb = Math.round(nav.responseStart);
        }
    }

    function observeLcp() {
        if (!('PerformanceObserver' in window)) {
            return;
        }

        try {
            var observer = new PerformanceObserver(function (list) {
                var entries = list.getEntries();
                var lastEntry = entries[entries.length - 1];
                if (lastEntry && Number.isFinite(lastEntry.startTime)) {
                    state.lcp = Math.round(lastEntry.startTime);
                }
            });

            observer.observe({ type: 'largest-contentful-paint', buffered: true });
            window.addEventListener('pagehide', function () {
                observer.disconnect();
            }, { once: true });
        } catch (error) {
            // Browser unterstützt den Observer nicht sauber – still ignorieren.
        }
    }

    function observeCls() {
        if (!('PerformanceObserver' in window)) {
            return;
        }

        try {
            var sessionValue = 0;
            var sessionEntries = [];
            var observer = new PerformanceObserver(function (list) {
                list.getEntries().forEach(function (entry) {
                    if (entry.hadRecentInput) {
                        return;
                    }

                    var firstEntry = sessionEntries[0];
                    var previousEntry = sessionEntries[sessionEntries.length - 1];
                    var withinSessionWindow = previousEntry
                        && entry.startTime - previousEntry.startTime < 1000
                        && firstEntry
                        && entry.startTime - firstEntry.startTime < 5000;

                    if (withinSessionWindow) {
                        sessionEntries.push(entry);
                        sessionValue += entry.value;
                    } else {
                        sessionEntries = [entry];
                        sessionValue = entry.value;
                    }

                    state.cls = Math.max(state.cls, Number(sessionValue.toFixed(3)));
                });
            });

            observer.observe({ type: 'layout-shift', buffered: true });
            window.addEventListener('pagehide', function () {
                observer.disconnect();
            }, { once: true });
        } catch (error) {
            // Ignorieren – CLS bleibt dann leer.
        }
    }

    function observeInp() {
        if (!('PerformanceObserver' in window)) {
            return;
        }

        try {
            var eventObserver = new PerformanceObserver(function (list) {
                list.getEntries().forEach(function (entry) {
                    if (!Number.isFinite(entry.duration)) {
                        return;
                    }

                    var duration = Math.round(entry.duration);
                    if (state.inp === null || duration > state.inp) {
                        state.inp = duration;
                    }
                });
            });
            eventObserver.observe({ type: 'event', buffered: true, durationThreshold: 40 });

            window.addEventListener('pagehide', function () {
                eventObserver.disconnect();
            }, { once: true });
        } catch (error) {
            // Event Timing nicht verfügbar – fallback unten greift ggf.
        }

        try {
            var firstInputObserver = new PerformanceObserver(function (list) {
                list.getEntries().forEach(function (entry) {
                    if (!Number.isFinite(entry.processingStart) || !Number.isFinite(entry.startTime)) {
                        return;
                    }

                    var delay = Math.round(entry.processingStart - entry.startTime);
                    if (delay > 0 && (state.inp === null || delay > state.inp)) {
                        state.inp = delay;
                    }
                });
            });
            firstInputObserver.observe({ type: 'first-input', buffered: true });

            window.addEventListener('pagehide', function () {
                firstInputObserver.disconnect();
            }, { once: true });
        } catch (error) {
            // Kein First-Input-Observer verfügbar.
        }
    }

    function getNavigationType() {
        if (!performance || typeof performance.getEntriesByType !== 'function') {
            return '';
        }

        var nav = performance.getEntriesByType('navigation')[0];
        return nav && typeof nav.type === 'string' ? nav.type : '';
    }

    function buildPayload() {
        var hasMetric = state.ttfb !== null || state.lcp !== null || state.inp !== null || state.cls > 0;
        if (!hasMetric) {
            return null;
        }

        return {
            path: window.location.pathname,
            title: document.title || '',
            ttfb: state.ttfb,
            lcp: state.lcp,
            inp: state.inp,
            cls: state.cls > 0 ? Number(state.cls.toFixed(3)) : null,
            effective_type: navigator.connection && navigator.connection.effectiveType ? navigator.connection.effectiveType : '',
            navigation_type: getNavigationType(),
            viewport_width: window.innerWidth || null,
            viewport_height: window.innerHeight || null
        };
    }

    function sendPayload() {
        if (state.sent) {
            return;
        }

        var payload = buildPayload();
        if (!payload) {
            return;
        }

        state.sent = true;
        var body = JSON.stringify(payload);

        try {
            if (navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                navigator.sendBeacon(config.endpoint, blob);
                return;
            }
        } catch (error) {
            // Fallback via fetch.
        }

        if (window.fetch) {
            fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: body,
                keepalive: true,
                credentials: 'same-origin'
            }).catch(function () {
                // Beacons dürfen lautlos scheitern.
            });
        }
    }

    trackTtfb();
    observeLcp();
    observeCls();
    observeInp();

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            sendPayload();
        }
    });

    window.addEventListener('pagehide', sendPayload, { once: true });
})();
