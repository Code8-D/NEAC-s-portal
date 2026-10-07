(function () {
    const cards = document.querySelectorAll('.three-d-card, .three-d-tile, .classroom-card, .classwork-card, .person-card, .stream-card');
    const maxTilt = 6;
    cards.forEach((card) => {
        let rect = null;
        card.addEventListener('mouseenter', () => { rect = card.getBoundingClientRect(); });
        card.addEventListener('mousemove', (event) => {
            if (!rect) rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `translateZ(14px) rotateX(${(-y * maxTilt).toFixed(2)}deg) rotateY(${(x * maxTilt).toFixed(2)}deg)`;
        });
        card.addEventListener('mouseleave', () => {
            rect = null;
            card.style.transform = '';
        });
    });

    const rows = document.getElementById('rubric-rows');
    const addBtn = document.getElementById('add-rubric');
    if (rows && addBtn) {
        addBtn.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'rubric-row';
            row.innerHTML = `
                <input type="text" name="rubric_label[]" placeholder="Criterion (e.g. Accuracy)" required>
                <input type="number" name="rubric_max[]" placeholder="Pts" value="10" min="1" step="0.5" required>
                <input type="text" name="rubric_desc[]" placeholder="Description (optional)">
                <button type="button" class="icon-btn" data-remove-rubric><i class="fa-solid fa-trash"></i></button>
            `;
            rows.appendChild(row);
        });
        rows.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-remove-rubric]');
            if (!removeBtn) return;
            const row = removeBtn.closest('.rubric-row');
            if (row && rows.children.length > 1) {
                row.remove();
            }
        });
    }

    const workType = document.getElementById('cw-type');
    const rubricBlock = document.querySelector('.rubric-block');
    if (workType && rubricBlock) {
        const sync = () => {
            rubricBlock.style.display = workType.value === 'assignment' ? '' : 'none';
        };
        workType.addEventListener('change', sync);
        sync();
    }
})();
