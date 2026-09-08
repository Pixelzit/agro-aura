/**
 * Header and Mobile Side Drawer interactions
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.querySelector('.agro-mobile-menu-toggle');
    const closeBtn = document.querySelector('.drawer-close');
    const drawer = document.getElementById('agro-side-drawer');
    const backdrop = document.querySelector('.agro-drawer-backdrop');

    if (!drawer || !toggleBtn) return;

    function openDrawer() {
        drawer.classList.add('is-open');
        if (backdrop) backdrop.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        if (backdrop) backdrop.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('drawer-open');
    }

    toggleBtn.addEventListener('click', openDrawer);

    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeDrawer);
    }

    // Close when pressing Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    // Automatically close drawer if resized above 1024px
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024 && drawer.classList.contains('is-open')) {
            closeDrawer();
        }
    });
});
