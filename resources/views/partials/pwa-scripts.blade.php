{{-- Progressive web app: service worker, offline/online status, updates and install prompt. --}}
<style>
    .pwa-toast {
        position: fixed; left: 50%; bottom: 16px; transform: translate(-50%, 150%); z-index: 9999;
        display: flex; align-items: center; gap: 12px; max-width: calc(100vw - 32px); width: max-content;
        padding: 10px 12px 10px 16px; border-radius: 12px; font: 500 14px/1.4 system-ui, -apple-system, "Segoe UI", sans-serif;
        background: var(--color-primary); color: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .2); transition: transform .3s ease;
    }
    .pwa-toast.is-visible { transform: translate(-50%, 0); }
    .pwa-toast--offline { background: #92400e; }
    .pwa-toast--online { background: #166534; }
    .pwa-toast button {
        border: 0; border-radius: 8px; padding: 6px 12px; cursor: pointer; white-space: nowrap;
        font: 600 13px system-ui, -apple-system, "Segoe UI", sans-serif;
    }
    .pwa-toast .pwa-primary { background: var(--color-accent); color: #fff; }
    .pwa-toast .pwa-dismiss { background: transparent; color: rgba(255, 255, 255, .8); padding: 6px 8px; }
    @media (prefers-reduced-motion: reduce) { .pwa-toast { transition: none; } }
</style>
<div id="pwa-toast" class="pwa-toast" role="status" aria-live="polite" hidden></div>

<script>
(function () {
    var toastEl = document.getElementById('pwa-toast');
    var hideTimer;

    // Show a small message at the bottom of the screen, optionally with buttons.
    function toast(message, options) {
        options = options || {};
        clearTimeout(hideTimer);
        toastEl.className = 'pwa-toast' + (options.variant ? ' pwa-toast--' + options.variant : '');
        toastEl.innerHTML = '';
        var text = document.createElement('span');
        text.textContent = message;
        toastEl.appendChild(text);
        (options.actions || []).forEach(function (action) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = action.label;
            button.className = action.primary ? 'pwa-primary' : 'pwa-dismiss';
            button.addEventListener('click', function () { action.onClick(); hide(); });
            toastEl.appendChild(button);
        });
        toastEl.hidden = false;
        requestAnimationFrame(function () { toastEl.classList.add('is-visible'); });
        if (options.duration) hideTimer = setTimeout(hide, options.duration);
    }
    function hide() {
        toastEl.classList.remove('is-visible');
        hideTimer = setTimeout(function () { toastEl.hidden = true; }, 300);
    }

    // --- Connection status ---------------------------------------------------
    function showOffline() {
        toast("You're offline. Saved pages still work; changes can't be sent until you reconnect.", { variant: 'offline' });
    }
    window.addEventListener('offline', showOffline);
    window.addEventListener('online', function () { toast('Back online', { variant: 'online', duration: 2500 }); });
    if (!navigator.onLine) showOffline();

    // Keep what the user typed instead of losing it to a failed submission.
    document.addEventListener('submit', function (event) {
        if (navigator.onLine) return;
        event.preventDefault();
        toast("You're offline, so this wasn't sent. Your entries are kept; try again when you're connected.", { variant: 'offline', duration: 6000 });
    }, true);

    if (!('serviceWorker' in navigator)) return;

    // --- Clear saved pages when someone logs out --------------------------------
    document.addEventListener('submit', function (event) {
        var action = event.target && event.target.getAttribute('action') || '';
        if (/\/logout\/?$/.test(action) && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_USER_DATA' });
        }
    });

    // --- Register and offer updates -----------------------------------------------
    // Reload only when a new version replaces an old one, not on the very first install.
    var refreshing = false;
    var hadController = !!navigator.serviceWorker.controller;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
        if (!hadController) { hadController = true; return; }
        if (refreshing) return;
        refreshing = true;
        window.location.reload();
    });

    function offerUpdate(worker) {
        toast('A new version of BookTala is available.', {
            actions: [
                { label: 'Refresh', primary: true, onClick: function () { worker.postMessage({ type: 'SKIP_WAITING' }); } },
                { label: 'Later', onClick: function () {} }
            ]
        });
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register(@json(asset('sw.js'))).then(function (registration) {
            // A new version is ready while an older one controls the page: offer it.
            function watch(worker) {
                if (!worker) return;
                if (worker.state === 'installed' && navigator.serviceWorker.controller) return offerUpdate(worker);
                worker.addEventListener('statechange', function () {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) offerUpdate(worker);
                });
            }
            watch(registration.waiting);
            watch(registration.installing); // an update may already be underway
            registration.addEventListener('updatefound', function () { watch(registration.installing); });
            // Look for a new version when the user comes back to the app.
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') registration.update().catch(function () {});
            });
        }).catch(function (error) { console.warn('Service worker registration failed:', error); });
    });

    // --- App Badging API ----------------------------------------------------
    function updateAppBadge() {
        if (!('setAppBadge' in navigator)) return;
        var count = 0;
        var badgeEl = document.querySelector('[data-unread-count]');
        if (badgeEl) {
            count = parseInt(badgeEl.getAttribute('data-unread-count') || '0', 10);
        } else {
            var match = document.title.match(/^\((\d+)\)/);
            if (match) count = parseInt(match[1], 10);
        }
        if (count > 0) {
            navigator.setAppBadge(count).catch(function () {});
        } else {
            navigator.clearAppBadge().catch(function () {});
        }
    }
    updateAppBadge();

    // --- Install prompt (public site only) -----------------------------------------------
    @if(!empty($pwaInstallPrompt))
    var DISMISS_KEY = 'wp-install-dismissed-at';
    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    function recentlyDismissed() {
        try { return Date.now() - Number(localStorage.getItem(DISMISS_KEY) || 0) < 30 * 24 * 3600 * 1000; } catch (e) { return false; }
    }
    function rememberDismissal() {
        try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch (e) {}
    }

    window.triggerPwaInstall = function() {
        if (window.deferredPwaPrompt) {
            window.deferredPwaPrompt.prompt();
            window.deferredPwaPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    document.querySelectorAll('.pwa-install-btn').forEach(function(el) { el.classList.add('hidden'); });
                }
                window.deferredPwaPrompt = null;
            });
        } else {
            toast('To install, tap your browser menu and choose "Add to Home Screen" or "Install App".', { duration: 6000 });
        }
    };

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.pwa-install-btn, [data-pwa-install]');
        if (trigger) {
            e.preventDefault();
            window.triggerPwaInstall();
        }
    });

    if (!standalone) {
        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            window.deferredPwaPrompt = event;
            document.querySelectorAll('.pwa-install-btn').forEach(function(el) {
                el.classList.remove('hidden');
            });

            if (!recentlyDismissed()) {
                setTimeout(function () {
                    toast('Install the BookTala app for quick access, even offline.', {
                        actions: [
                            { label: 'Install', primary: true, onClick: function () { 
                                event.prompt(); 
                                event.userChoice.finally(rememberDismissal); 
                            } },
                            { label: 'Not now', onClick: rememberDismissal }
                        ]
                    });
                }, 4000);
            }
        });

        // iPhone and iPad have no install prompt: explain how to add it.
        var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
        if (isIos && !recentlyDismissed()) {
            setTimeout(function () {
                toast('Install BookTala: tap Share, then "Add to Home Screen".', {
                    actions: [{ label: 'Got it', onClick: rememberDismissal }]
                });
            }, 6000);
        }
    }
    @endif
})();
</script>
