const excludedSelector = '.sheet-backdrop, .confirm-backdrop, [role="dialog"], [role="alertdialog"], [aria-hidden="true"]';
const revealSelector = [
    '.page-header', '.home-hero', '.home-metric', '.home-next', '.home-payment',
    '.task-summary', '.task-filters', '.task-list__title', '.task-row',
    '.journey-header', '.journey-summary', '.milestone-row',
    '.needs-head', '.need-summary', '.need-category-summary', '.need-row',
    '.guest-summary', '.guest-toolbar', '.guest-row',
    '.event-card', '.event-detail-card', '.event-preparation-card', '.event-agenda-section', '.event-needs-section',
    '.need-detail-card', '.need-detail-summary', '.account-card', '.goal-card', '.budget-card', '.goal-progress-card', '.budget-detail-summary',
    '.transaction-row', '.obligation-row', '.member-row', '.guest-metrics', '.guest-table-card',
    '.finance-metrics', '.library-hero', '.library-heading', '.theme-card', '.workspace-summary',
    '.guests-header', '.events-head', '.finance-home__head', '.records-head', '.settings-head',
    '.invitation-card', '.invitation-template-card', '.onboarding-card', '.privacy-card', 'article',
    '.settings-section', '.settings-panel',
].join(', ');

export function usePageReveal() {
    let revealObserver = null;
    let contentObserver = null;

    function stop() {
        revealObserver?.disconnect();
        contentObserver?.disconnect();
        revealObserver = null;
        contentObserver = null;
    }

    function start(frame) {
        stop();
        if (!frame || typeof window === 'undefined') return;

        const roots = [...frame.children].filter((element) => !element.matches(excludedSelector));
        const scope = roots.length === 1 ? roots[0] : frame;
        if (!scope) return;

        revealObserver = 'IntersectionObserver' in window
            ? new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' })
            : null;

        const reveal = (element, index) => {
            if (!(element instanceof HTMLElement) || element.dataset.pageReveal === 'true') return;
            if (element.matches(excludedSelector) || getComputedStyle(element).display === 'none') return;

            element.dataset.pageReveal = 'true';
            element.style.setProperty('--page-reveal-index', String(Math.min(index, 5)));
            element.classList.add('page-reveal-item');
            if (revealObserver) revealObserver.observe(element);
            else element.classList.add('is-revealed');
        };

        const initialTargets = [...scope.children, ...scope.querySelectorAll(revealSelector)];
        initialTargets.forEach((element, index) => reveal(element, index));

        contentObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof HTMLElement)) return;
                    const matchingTargets = [
                        ...(node.matches(revealSelector) ? [node] : []),
                        ...node.querySelectorAll(revealSelector),
                    ];
                    if (matchingTargets.length) {
                        const existingCount = scope.querySelectorAll(revealSelector).length - matchingTargets.length;
                        matchingTargets.forEach((element, index) => reveal(element, existingCount + index));
                        return;
                    }
                    let topLevel = node;
                    while (topLevel.parentElement && topLevel.parentElement !== scope) topLevel = topLevel.parentElement;
                    if (topLevel.parentElement === scope) reveal(topLevel, [...scope.children].indexOf(topLevel));
                });
            });
        });
        contentObserver.observe(scope, { childList: true, subtree: true });
    }

    return { start, stop };
}
