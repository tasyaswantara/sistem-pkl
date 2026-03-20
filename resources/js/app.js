import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import 'alpinejs';

const LOADER_CLASS = 'page-loading';
const LOADER_DELAY_MS = 500;

let loaderTimeoutId = null;
let loaderRequestCount = 0;

const setPageLoading = (loading) => {
    if (!document.body) {
        return;
    }

    document.body.classList.toggle(LOADER_CLASS, loading);
};

window.showPageLoader = () => {
    loaderRequestCount += 1;

    if (document.body?.classList.contains(LOADER_CLASS) || loaderTimeoutId !== null) {
        return;
    }

    loaderTimeoutId = window.setTimeout(() => {
        loaderTimeoutId = null;

        if (loaderRequestCount > 0) {
            setPageLoading(true);
        }
    }, LOADER_DELAY_MS);
};

window.hidePageLoader = () => {
    loaderRequestCount = Math.max(0, loaderRequestCount - 1);

    if (loaderRequestCount > 0) {
        return;
    }

    if (loaderTimeoutId !== null) {
        window.clearTimeout(loaderTimeoutId);
        loaderTimeoutId = null;
    }

    setPageLoading(false);
};

const shouldHandleLink = (link) => {
    if (!link || !link.href) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download') || link.dataset.noLoader === 'true') {
        return false;
    }

    const href = link.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
        return false;
    }

    try {
        const url = new URL(link.href, window.location.origin);
        return url.origin === window.location.origin;
    } catch {
        return false;
    }
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a');
    if (!shouldHandleLink(link)) {
        return;
    }

    window.showPageLoader();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.noLoader === 'true') {
        return;
    }

    window.showPageLoader();
});

window.addEventListener('pageshow', () => {
    window.hidePageLoader();
});

window.addEventListener('load', () => {
    window.hidePageLoader();
});
