document.addEventListener('DOMContentLoaded', function () {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.topnav a').forEach((link) => {
        const href = (link.getAttribute('href') || '').split('/').pop();
        if (href && href === currentPath) {
            link.setAttribute('aria-current', 'page');
        }
    });

    const navToggle = document.getElementById('nav-toggle');
    const topnav = document.getElementById('topnav');
    if (navToggle && topnav) {
        const closeNav = () => {
            topnav.classList.remove('topnav--open');
            navToggle.setAttribute('aria-expanded', 'false');
        };
        navToggle.addEventListener('click', () => {
            const open = topnav.classList.toggle('topnav--open');
            navToggle.setAttribute('aria-expanded', String(open));
        });
        topnav.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeNav));
        document.addEventListener('click', (event) => {
            if (!topnav.classList.contains('topnav--open')) return;
            if (!topnav.contains(event.target) && !navToggle.contains(event.target)) closeNav();
        });
    }

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const sidebarClose = document.getElementById('sidebar-close');
    if (sidebarToggle && sidebar) {
        const openSidebar = () => {
            sidebar.classList.add('sidebar--open');
            sidebarToggle.setAttribute('aria-expanded', 'true');
            if (sidebarBackdrop) sidebarBackdrop.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const closeSidebar = () => {
            sidebar.classList.remove('sidebar--open');
            sidebarToggle.setAttribute('aria-expanded', 'false');
            if (sidebarBackdrop) sidebarBackdrop.hidden = true;
            document.body.style.overflow = '';
        };
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.contains('sidebar--open') ? closeSidebar() : openSidebar();
        });
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
    }
});
