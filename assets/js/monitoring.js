(function () {
    if (navigator.doNotTrack === '1' || window.doNotTrack === '1') return;
    const script = document.currentScript;
    if (!script) return;
    const scriptUrl = new URL(script.src, window.location.href);
    const marker = '/assets/js/monitoring.js';
    const markerIndex = scriptUrl.pathname.indexOf(marker);
    if (markerIndex < 0) return;
    const endpoint = scriptUrl.origin + scriptUrl.pathname.slice(0, markerIndex) + '/api/telemetry.php';
    const payload = JSON.stringify({ path: window.location.pathname });
    if (navigator.sendBeacon) {
        navigator.sendBeacon(endpoint, new Blob([payload], { type: 'application/json' }));
        return;
    }
    fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: { 'Content-Type': 'application/json' },
        body: payload
    }).catch(function () { /* Monitoring must never interrupt the page. */ });
}());
