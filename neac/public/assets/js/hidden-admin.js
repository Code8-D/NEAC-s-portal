(function () {
    const TRIGGER = '[data-admin-trigger]';
    const MODAL_ID = 'admin-modal';
    const CLICK_THRESHOLD = 5;
    const CLICK_WINDOW_MS = 3000;
    const HINT_ID = 'admin-hint';

    let clickTimes = [];

    function openModal() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return;
        modal.hidden = false;
        modal.classList.add('is-open');
        const codeInput = document.getElementById('admin-code');
        if (codeInput) {
            setTimeout(() => codeInput.focus(), 60);
        }
        document.body.classList.add('is-modal-open');
        document.addEventListener('keydown', escClose);
    }

    function closeModal() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.hidden = true;
        document.body.classList.remove('is-modal-open');
        document.removeEventListener('keydown', escClose);
    }

    function escClose(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    }

    function handleTrigger(event) {
        const target = event.target.closest(TRIGGER);
        if (!target) return;
        event.preventDefault();
        const now = Date.now();
        clickTimes = clickTimes.filter((t) => now - t <= CLICK_WINDOW_MS);
        clickTimes.push(now);
        if (clickTimes.length >= CLICK_THRESHOLD) {
            clickTimes = [];
            openModal();
        }
    }

    document.addEventListener('click', handleTrigger);

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-admin-close]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.shiftKey && (event.key === 'A' || event.key === 'a')) {
            event.preventDefault();
            openModal();
        }
    });

    const hint = document.getElementById(HINT_ID);
    if (hint) {
        let count = 0;
        document.addEventListener('click', (event) => {
            const target = event.target.closest(TRIGGER);
            if (!target) return;
            count++;
            if (count >= 3 && count < CLICK_THRESHOLD) {
                hint.textContent = `Tip: ${CLICK_THRESHOLD - count} more click${CLICK_THRESHOLD - count === 1 ? '' : 's'} on the NEAC logo opens the admin panel.`;
            } else if (count >= CLICK_THRESHOLD) {
                count = 0;
            }
        });
    }
})();
