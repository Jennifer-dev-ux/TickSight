// js/main.js

document.addEventListener('DOMContentLoaded', () => {
    const sidebarButton = document.getElementById('reportFromSidebar');
    if (sidebarButton) {
        sidebarButton.addEventListener('click', () => {
            window.location.href = 'index.php?page=report';
        });
    }
});
