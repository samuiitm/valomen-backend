document.addEventListener('DOMContentLoaded', () => {
    const ajaxBox = document.getElementById('matchesAjaxBox');

    if (!ajaxBox) {
        return;
    }

    const fragmentUrl = ajaxBox.dataset.fragmentUrl;
    let activeRequest = null;

    function setLoadingState(isLoading) {
        ajaxBox.classList.toggle('is-loading', isLoading);
    }

    async function loadMatches(url, updateHistory = true) {
        const pageUrl = new URL(url, window.location.origin);
        const requestUrl = new URL(fragmentUrl, window.location.origin);

        requestUrl.search = pageUrl.search;

        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();
        setLoadingState(true);

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeRequest.signal
            });

            if (!response.ok) {
                throw new Error('Error carregant els partits');
            }

            const html = await response.text();
            ajaxBox.innerHTML = html;

            if (updateHistory) {
                history.pushState({}, '', pageUrl.toString());
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        } finally {
            setLoadingState(false);
        }
    }

    document.addEventListener('click', (event) => {
        const ajaxLink = event.target.closest('.js-matches-ajax-link');

        if (ajaxLink) {
            if (ajaxLink.classList.contains('is-disabled')) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            loadMatches(ajaxLink.href);
            return;
        }

        const deleteBtn = event.target.closest('.js-delete-match');

        if (deleteBtn) {
            event.preventDefault();

            const matchName = deleteBtn.dataset.matchLabel || 'this match';

            if (confirm(`Are you sure you want to delete ${matchName}?`)) {
                window.location.href = deleteBtn.href;
            }
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (form.id !== 'matchesSearchForm' && form.id !== 'matchesFiltersForm') {
            return;
        }

        event.preventDefault();

        const formData = new FormData(form);
        const pageUrl = new URL(form.action, window.location.origin);
        pageUrl.search = new URLSearchParams(formData).toString();

        loadMatches(pageUrl.toString());
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('#matchesFiltersForm select');

        if (!select) {
            return;
        }

        const form = document.getElementById('matchesFiltersForm');

        if (!form) {
            return;
        }

        const formData = new FormData(form);
        const pageUrl = new URL(form.action, window.location.origin);
        pageUrl.search = new URLSearchParams(formData).toString();

        loadMatches(pageUrl.toString());
    });

    window.addEventListener('popstate', () => {
        loadMatches(window.location.href, false);
    });
});