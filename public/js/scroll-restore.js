const scrollStorageKey = 'praktiki-scroll-position';

window.addEventListener('pageshow', () => {
    const saved = sessionStorage.getItem(scrollStorageKey);

    if (!saved) {
        return;
    }

    try {
        const position = JSON.parse(saved);
        const currentUrl = `${window.location.pathname}${window.location.search}`;

        if (Date.now() - position.savedAt >= 600000) {
            sessionStorage.removeItem(scrollStorageKey);
        } else if (position.url === currentUrl) {
            sessionStorage.removeItem(scrollStorageKey);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => window.scrollTo({ top: position.y, behavior: 'auto' }));
            });
        }
    } catch {
        sessionStorage.removeItem(scrollStorageKey);
        // Ignore invalid or obsolete browser data.
    }
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-scroll-return]');

    if (!link) {
        return;
    }

    saveScrollPosition();
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (event.defaultPrevented || !(form instanceof HTMLFormElement)) {
        return;
    }

    const method = (form.getAttribute('method') || 'GET').toUpperCase();

    if (method === 'GET' || form.target === '_blank') {
        return;
    }

    const saved = sessionStorage.getItem(scrollStorageKey);

    if (saved) {
        try {
            const position = JSON.parse(saved);
            const currentUrl = `${window.location.pathname}${window.location.search}`;

            if (position.url !== currentUrl && Date.now() - position.savedAt < 600000) {
                return;
            }
        } catch {
            // Replace invalid browser data below.
        }
    }

    saveScrollPosition();
});

function saveScrollPosition() {
    sessionStorage.setItem(scrollStorageKey, JSON.stringify({
        url: `${window.location.pathname}${window.location.search}`,
        y: window.scrollY,
        savedAt: Date.now(),
    }));
}
