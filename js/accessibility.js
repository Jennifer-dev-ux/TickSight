// js/accessibility.js

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('contrastToggle');
    if (!toggleBtn) return;

    const STORAGE_KEY = 'highContrastMode';

    function applyState(isOn) {
        if (isOn) {
            document.body.classList.add('high-contrast');
            toggleBtn.setAttribute('aria-pressed', 'true');
            toggleBtn.textContent = 'Low Contrast';
        } else {
            document.body.classList.remove('high-contrast');
            toggleBtn.setAttribute('aria-pressed', 'false');
            toggleBtn.textContent = 'High Contrast';
        }
    }

    // 1. Load saved preference
    const saved = localStorage.getItem(STORAGE_KEY);
    const isOn = saved === 'on';
    applyState(isOn);

    // 2. Toggle on click
    toggleBtn.addEventListener('click', () => {
        const currentlyOn = document.body.classList.contains('high-contrast');
        const next = !currentlyOn;
        applyState(next);
        localStorage.setItem(STORAGE_KEY, next ? 'on' : 'off');
    });
});
