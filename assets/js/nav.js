// =============================================================
//  nav.js  — Admin shell UI
//  Handles: sidebar toggle, profile dropdown, notif dropdown,
//           and active sidebar link highlighting.
//
//  No AJAX/SPA — pages are server-rendered via PHP includes.
// =============================================================

document.addEventListener('DOMContentLoaded', () => {

    // ── ELEMENTS ──────────────────────────────────────────────
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebar-overlay');
    const burgerBtn  = document.getElementById('burger-btn');

    const notifToggle    = document.getElementById('notif-toggle');
    const notifDropdown  = document.getElementById('notif-dropdown');
    const notifDot       = document.getElementById('notif-dot');
    const markAllRead    = document.getElementById('mark-all-read');

    const profileToggle  = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    const profileCaret   = document.getElementById('profile-caret');


    // ── SIDEBAR TOGGLE ────────────────────────────────────────
    function openSidebar() {
        sidebar.classList.add('open');
        overlay?.classList.add('active');
        burgerBtn?.classList.add('open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay?.classList.remove('active');
        burgerBtn?.classList.remove('open');
    }

    burgerBtn?.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    // Close sidebar when clicking the overlay (mobile)
    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar on mobile when a menu link is clicked
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });


    // ── ACTIVE SIDEBAR LINK ───────────────────────────────────
    // Compare each link's href against the current URL path.
    // Works with both relative hrefs and full paths.
    const currentPath = window.location.pathname.replace(/\/$/, '').toLowerCase();

    document.querySelectorAll('.menu-btn[href]').forEach(btn => {
        const linkPath = btn.getAttribute('href').replace(/\/$/, '').toLowerCase();
        if (currentPath.endsWith(linkPath)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });


    // ── NOTIFICATION DROPDOWN ─────────────────────────────────
    notifToggle?.addEventListener('click', e => {
        e.stopPropagation();
        profileDropdown?.classList.remove('open');
        profileCaret?.classList.remove('open');
        notifDropdown?.classList.toggle('open');
    });

    markAllRead?.addEventListener('click', () => {
        document.querySelectorAll('.notif-item.unread')
            .forEach(item => item.classList.remove('unread'));
        if (notifDot) notifDot.style.display = 'none';
    });

    // Prevent clicks inside the dropdown from closing it
    notifDropdown?.addEventListener('click', e => e.stopPropagation());


    // ── PROFILE DROPDOWN ──────────────────────────────────────
    profileToggle?.addEventListener('click', e => {
        e.stopPropagation();
        notifDropdown?.classList.remove('open');
        profileDropdown?.classList.toggle('open');
        profileCaret?.classList.toggle('open');
    });

    // Prevent clicks inside the dropdown from closing it
    profileDropdown?.addEventListener('click', e => e.stopPropagation());


    // ── CLOSE ALL DROPDOWNS on outside click ──────────────────
    document.addEventListener('click', () => {
        notifDropdown?.classList.remove('open');
        profileDropdown?.classList.remove('open');
        profileCaret?.classList.remove('open');
    });

});