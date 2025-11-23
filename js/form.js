// js/form.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.report-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        const requiredIds = ['date', 'time', 'location', 'species'];
        let hasError = false;

        requiredIds.forEach((id) => {
            const field = document.getElementById(id);
            if (!field) return;
            if (!field.value.trim()) {
                field.classList.add('field-error');
                hasError = true;
            } else {
                field.classList.remove('field-error');
            }
        });

        if (hasError) {
            alert('Please fill in all required fields before submitting.');
            // Optional: uncomment next line to prevent submit if you want client-side blocking:
            // event.preventDefault();
        }
    });
});
