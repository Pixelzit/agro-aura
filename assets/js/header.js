/**
 * Header and Mobile Side Drawer interactions
 */
document.addEventListener('DOMContentLoaded', function () {
    // Dismiss Top Announcement Header Bar
    const topHeaderCloseBtn = document.querySelector('.site-top-header-close');
    if (topHeaderCloseBtn) {
        topHeaderCloseBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const topHeader = this.closest('.site-top-header');
            if (topHeader) {
                topHeader.style.transition = 'opacity 0.2s ease, max-height 0.25s ease, padding 0.25s ease';
                topHeader.style.overflow = 'hidden';
                topHeader.style.maxHeight = topHeader.offsetHeight + 'px';
                requestAnimationFrame(function () {
                    topHeader.style.opacity = '0';
                    topHeader.style.maxHeight = '0';
                    topHeader.style.paddingTop = '0';
                    topHeader.style.paddingBottom = '0';
                });
                setTimeout(function () {
                    topHeader.remove();
                }, 250);
            }
        });
    }

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
