(() => {
    const readMeta = (name) => {
        const tag = document.querySelector(`meta[name="${name}"]`);
        return tag ? tag.content : '';
    };

    const swUrl = readMeta('pwa-sw') || 'service-worker.js';
    const offlineUrl = readMeta('pwa-offline') || 'offline.html';

    const isStandalone = () => {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    };

    const shouldShowInstall = () => {
        if (isStandalone()) {
            return false;
        }
        const dismissedAt = Number(localStorage.getItem('pwaInstallDismissedAt') || '0');
        if (!dismissedAt) {
            return true;
        }
        const now = Date.now();
        const days = 7 * 24 * 60 * 60 * 1000;
        return now - dismissedAt > days;
    };

    const createToast = (message, type = 'info') => {
        const toast = document.createElement('div');
        toast.className = `pwa-toast pwa-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(() => toast.classList.add('show'), 50);
        window.setTimeout(() => {
            toast.classList.remove('show');
            window.setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    const ensureStyles = () => {
        if (document.getElementById('pwa-styles')) {
            return;
        }
        const style = document.createElement('style');
        style.id = 'pwa-styles';
        style.textContent = `
            .pwa-banner {
                position: fixed;
                inset-inline: 16px;
                bottom: 16px;
                z-index: 9999;
                background: #111827;
                color: #f9fafb;
                border-radius: 16px;
                box-shadow: 0 18px 40px rgba(17, 24, 39, 0.4);
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 520px;
            }
            .pwa-banner h4 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
            }
            .pwa-banner p {
                margin: 0;
                color: #d1d5db;
                font-size: 14px;
                line-height: 1.4;
            }
            .pwa-banner .actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .pwa-banner button {
                border: 0;
                border-radius: 999px;
                padding: 10px 18px;
                font-weight: 600;
                cursor: pointer;
            }
            .pwa-banner .install {
                background: linear-gradient(90deg, #d4af37, #f59e0b);
                color: #111827;
            }
            .pwa-banner .dismiss {
                background: transparent;
                color: #e5e7eb;
                border: 1px solid #374151;
            }
            .pwa-banner .notify {
                background: #1f2937;
                color: #f9fafb;
                border: 1px solid #4b5563;
            }
            .pwa-toast {
                position: fixed;
                top: 18px;
                inset-inline: 50%;
                transform: translateX(-50%) translateY(-12px);
                background: #111827;
                color: #f9fafb;
                border-radius: 999px;
                padding: 8px 16px;
                font-size: 13px;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
                z-index: 9999;
            }
            .pwa-toast.show {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            .pwa-toast-success { background: #065f46; }
            .pwa-toast-error { background: #7f1d1d; }
        `;
        document.head.appendChild(style);
    };

    let deferredPrompt = null;
    let installBanner = null;

    const showInstallBanner = () => {
        if (!deferredPrompt || installBanner || !shouldShowInstall()) {
            return;
        }
        ensureStyles();

        installBanner = document.createElement('div');
        installBanner.className = 'pwa-banner';
        installBanner.innerHTML = `
            <h4>Install Bloom & Vine</h4>
            <p>Get a faster, offline-ready experience with quick access from your home screen.</p>
            <div class="actions">
                <button class="install">Install App</button>
                <button class="notify">Enable Alerts</button>
                <button class="dismiss">Not Now</button>
            </div>
        `;

        const installButton = installBanner.querySelector('.install');
        const dismissButton = installBanner.querySelector('.dismiss');
        const notifyButton = installBanner.querySelector('.notify');

        installButton.addEventListener('click', async () => {
            installButton.disabled = true;
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (choice.outcome === 'accepted') {
                createToast('App installation started.', 'success');
            }
            installBanner.remove();
        });

        dismissButton.addEventListener('click', () => {
            localStorage.setItem('pwaInstallDismissedAt', String(Date.now()));
            installBanner.remove();
        });

        notifyButton.addEventListener('click', () => {
            enablePushNotifications();
        });

        document.body.appendChild(installBanner);
    };

    const enablePushNotifications = async () => {
        if (!('Notification' in window)) {
            createToast('Notifications are not supported in this browser.', 'error');
            return;
        }
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            createToast('Notification permission denied.', 'error');
            return;
        }
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            createToast('Push notifications are not supported.', 'error');
            return;
        }
        const registration = await navigator.serviceWorker.ready;
        const publicKey = window.PWA_VAPID_PUBLIC_KEY || '';
        if (!publicKey) {
            createToast('Add a VAPID public key to enable push.', 'error');
            console.warn('Set window.PWA_VAPID_PUBLIC_KEY before calling enablePushNotifications.');
            return;
        }
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey)
        });
        console.info('Push subscription created:', subscription);
        createToast('Notifications enabled. Send the subscription to your server.', 'success');
    };

    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i++) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        showInstallBanner();
    });

    window.addEventListener('online', () => createToast('You are back online.', 'success'));
    window.addEventListener('offline', () => createToast('You are offline. Some features are limited.', 'error'));

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register(swUrl, { scope: './' });
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (!newWorker) {
                        return;
                    }
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            showUpdateBanner(registration);
                        }
                    });
                });
            } catch (error) {
                console.warn('Service worker registration failed:', error);
            }
        });
    }

    const showUpdateBanner = (registration) => {
        ensureStyles();
        if (document.getElementById('pwa-update')) {
            return;
        }
        const updateBanner = document.createElement('div');
        updateBanner.id = 'pwa-update';
        updateBanner.className = 'pwa-banner';
        updateBanner.innerHTML = `
            <h4>Update available</h4>
            <p>Refresh to get the latest features and content.</p>
            <div class="actions">
                <button class="install">Refresh</button>
                <button class="dismiss">Later</button>
            </div>
        `;
        updateBanner.querySelector('.install').addEventListener('click', () => {
            if (registration.waiting) {
                registration.waiting.postMessage('SKIP_WAITING');
            }
            window.location.reload();
        });
        updateBanner.querySelector('.dismiss').addEventListener('click', () => updateBanner.remove());
        document.body.appendChild(updateBanner);
    };

    window.PWA = {
        enablePushNotifications
    };

    if (offlineUrl) {
        window.PWA_OFFLINE_URL = offlineUrl;
    }
})();
