<script>
    if (typeof window.setupAjaxFilter !== 'function') {
        window.setupAjaxFilter = function setupAjaxFilter(config) {
            const form = document.getElementById(config.formId);
            const target = document.getElementById(config.targetId);

            if (!form || !target) {
                return;
            }

            if (form.dataset.ajaxFilterBound === '1') {
                return;
            }

            form.dataset.ajaxFilterBound = '1';

            let typingTimer;

            const buildUrl = (overrideUrl = null) => {
                if (overrideUrl) {
                    return overrideUrl;
                }

                const params = new URLSearchParams(new FormData(form));
                const queryString = params.toString();

                return queryString ? `${form.action}?${queryString}` : form.action;
            };

            const sendRequest = async (overrideUrl = null) => {
                const requestUrl = buildUrl(overrideUrl);
                const currentTarget = document.getElementById(config.targetId);

                if (currentTarget) {
                    currentTarget.classList.add('opacity-60', 'pointer-events-none');
                }

                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const replacement = doc.getElementById(config.targetId);
                    const liveTarget = document.getElementById(config.targetId);

                    if (!replacement || !liveTarget) {
                        throw new Error('Target container not found in AJAX response');
                    }

                    liveTarget.replaceWith(replacement);
                    window.history.replaceState({}, '', requestUrl);

                    if (window.Alpine) {
                        window.Alpine.initTree(replacement);
                    }

                    if (config.afterReplace && typeof window[config.afterReplace] === 'function') {
                        window[config.afterReplace](replacement);
                    }

                    window.setupAjaxFilter(config);
                } catch (error) {
                    window.location.assign(requestUrl);
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                sendRequest();
            });

            form.addEventListener('change', (event) => {
                const field = event.target;

                if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                    return;
                }

                if (field.matches('input[type="text"], input[type="search"], textarea')) {
                    return;
                }

                sendRequest();
            });

            form.addEventListener('input', (event) => {
                const field = event.target;

                if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
                    return;
                }

                if (!field.matches('input[type="text"], input[type="search"], textarea')) {
                    return;
                }

                window.clearTimeout(typingTimer);
                typingTimer = window.setTimeout(() => {
                    sendRequest();
                }, config.debounce ?? 400);
            });
        };
    }
</script>
