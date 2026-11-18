<script>
    (function () {
        const rootSelector = document.body.getAttribute('data-ajax-root') || 'body';
        let isNavigating = false;
        const pendingForms = new WeakSet();
        const activeFormRequests = new WeakMap();

        function getRoot(scopeDocument) {
            return scopeDocument.querySelector(rootSelector);
        }

        function shouldSkipForm(form) {
            if (!form || form.hasAttribute('data-no-ajax')) {
                return true;
            }

            return form.target && form.target.toLowerCase() === '_blank';
        }

        function setFormSubmittingState(form, submitter, submitting) {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (submitting) {
                form.setAttribute('data-ajax-submitting', '1');
            } else {
                form.removeAttribute('data-ajax-submitting');
            }

            const elementsToToggle = [];

            if (submitter && 'disabled' in submitter) {
                elementsToToggle.push(submitter);
            }

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((element) => {
                elementsToToggle.push(element);
            });

            elementsToToggle.forEach((element) => {
                if (!element) {
                    return;
                }

                if (submitting) {
                    if (!element.hasAttribute('data-was-disabled')) {
                        element.setAttribute('data-was-disabled', element.disabled ? '1' : '0');
                    }
                    element.disabled = true;
                } else {
                    const wasDisabled = element.getAttribute('data-was-disabled') === '1';
                    element.disabled = wasDisabled;
                    element.removeAttribute('data-was-disabled');
                }
            });
        }

        function executeScripts(container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach((oldScript) => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach((attribute) => {
                    newScript.setAttribute(attribute.name, attribute.value);
                });
                newScript.textContent = oldScript.textContent;
                oldScript.replaceWith(newScript);
            });
        }

        function getCsrfToken(form) {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken && metaToken.content) {
                return metaToken.content;
            }

            const formTokenInput = form.querySelector('input[name="_token"]');
            if (formTokenInput && formTokenInput.value) {
                return formTokenInput.value;
            }

            return null;
        }

        function isJsonResponse(response) {
            const contentType = response.headers.get('content-type') || '';
            return contentType.includes('application/json');
        }

        async function handleJsonNavigationResponse(response, fallbackUrl) {
            const payload = await response.json();
            const targetUrl = payload.redirect || payload.url || response.url || fallbackUrl || window.location.href;

            if (payload.html && typeof payload.html === 'string') {
                replaceFromHtml(payload.html, targetUrl, true);
                return;
            }

            if (targetUrl) {
                const followUp = await fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin'
                });

                if (!followUp.ok) {
                    throw new Error(`Follow-up HTML request failed with status ${followUp.status}`);
                }

                const html = await followUp.text();
                if (!html || html.trim().length === 0) {
                    throw new Error('Empty HTML during JSON follow-up.');
                }

                replaceFromHtml(html, followUp.url || targetUrl, true);
                return;
            }

            throw new Error('JSON response did not include renderable data.');
        }

        function replaceFromHtml(html, nextUrl, shouldPushState) {
            const parser = new DOMParser();
            const nextDocument = parser.parseFromString(html, 'text/html');
            const nextRoot = getRoot(nextDocument);
            const currentRoot = getRoot(document);

            if (!nextRoot || !currentRoot) {
                window.location.assign(nextUrl || window.location.href);
                return;
            }

            currentRoot.innerHTML = nextRoot.innerHTML;
            document.title = nextDocument.title || document.title;

            if (shouldPushState && nextUrl) {
                window.history.pushState({}, '', nextUrl);
            }

            executeScripts(currentRoot);
            window.dispatchEvent(new CustomEvent('ajax-layout-updated'));
        }

        function buildRequestUrl(form, formData, method) {
            const action = form.getAttribute('action') || window.location.href;
            const url = new URL(action, window.location.origin);

            if (method === 'GET') {
                formData.forEach((value, key) => {
                    url.searchParams.set(key, value.toString());
                });
            }

            return url.toString();
        }

        async function handleAjaxFormSubmit(form, submitter) {
            if (isNavigating || pendingForms.has(form) || form.getAttribute('data-ajax-submitting') === '1') {
                console.debug('Prevented duplicate AJAX submission.', form);
                return;
            }

            const method = ((form.getAttribute('method') || 'GET').toUpperCase());
            const formData = new FormData(form, submitter);
            const requestUrl = buildRequestUrl(form, formData, method);

            const options = {
                method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html'
                },
                credentials: 'same-origin'
            };

            const csrfToken = getCsrfToken(form);
            if (csrfToken) {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            if (method !== 'GET') {
                options.body = formData;
            }

            if (activeFormRequests.has(form)) {
                const previousController = activeFormRequests.get(form);
                previousController.abort();
                activeFormRequests.delete(form);
            }

            const requestController = new AbortController();
            activeFormRequests.set(form, requestController);
            options.signal = requestController.signal;

            isNavigating = true;
            pendingForms.add(form);
            setFormSubmittingState(form, submitter, true);
            window.dispatchEvent(new CustomEvent('ajax-start', { detail: { form, requestUrl, method } }));

            try {
                const response = await fetch(requestUrl, options);
                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                if (isJsonResponse(response)) {
                    await handleJsonNavigationResponse(response, requestUrl);
                } else {
                    const html = await response.text();
                    if (!html || html.trim().length === 0) {
                        throw new Error('Empty HTML response from server.');
                    }
                    replaceFromHtml(html, response.url || requestUrl, true);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.debug('Aborted stale AJAX form request:', requestUrl);
                    return;
                }
                console.error('AJAX form submission failed:', error);
                window.location.assign(requestUrl);
            } finally {
                isNavigating = false;
                pendingForms.delete(form);
                activeFormRequests.delete(form);
                setFormSubmittingState(form, submitter, false);
                window.dispatchEvent(new CustomEvent('ajax-end', { detail: { form, requestUrl, method } }));
            }
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || shouldSkipForm(form)) {
                return;
            }

            event.preventDefault();
            const submitter = event.submitter || null;
            handleAjaxFormSubmit(form, submitter);
        });

        window.addEventListener('popstate', async function () {
            if (isNavigating) {
                return;
            }

            try {
                isNavigating = true;
                window.dispatchEvent(new Event('ajax-start'));
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`History request failed with status ${response.status}`);
                }
                const html = await response.text();
                replaceFromHtml(html, window.location.href, false);
            } catch (error) {
                console.error('AJAX history navigation failed:', error);
                window.location.reload();
            } finally {
                isNavigating = false;
                window.dispatchEvent(new Event('ajax-end'));
            }
        });

        if (document.body.getAttribute('data-ajax-nav-links') === '1') {
            document.addEventListener('click', async function (event) {
                const anchor = event.target.closest('a.sidebar-link, .page-body nav a');
                if (!anchor || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
                    return;
                }

                const href = anchor.getAttribute('href');
                if (!href || href.startsWith('#')) {
                    return;
                }

                let url;
                try {
                    url = new URL(href, window.location.origin);
                } catch (_) {
                    return;
                }

                if (url.origin !== window.location.origin || !url.pathname.startsWith('/employee')) {
                    return;
                }

                event.preventDefault();

                if (isNavigating) {
                    return;
                }

                try {
                    isNavigating = true;
                    window.dispatchEvent(new Event('ajax-start'));
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error(`Navigation failed with status ${response.status}`);
                    }

                    const html = await response.text();
                    replaceFromHtml(html, response.url || url.toString(), true);
                } catch (error) {
                    console.error('AJAX link navigation failed:', error);
                    window.location.assign(url.toString());
                } finally {
                    isNavigating = false;
                    window.dispatchEvent(new Event('ajax-end'));
                }
            });
        }
    })();
</script>
