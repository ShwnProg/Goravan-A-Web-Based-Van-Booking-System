// =============================================================
//  nav.js  — Core SPA shell
//  Handles: sidebar, AJAX navigation, active link, page dispatch
//  Load this file ONCE in your main layout (e.g. base.php)
//  It is NEVER reloaded — it survives every page navigation.
// =============================================================

// ─────────────────────────────────────────────────────────────
//  PAGE REGISTRY
//  Map a unique element ID (that only exists on one page) to the
//  name of that page's init function on the window object.
//
//  To add a new page:
//    1. Create   window.initMyPage = function() { ... }  in your page script
//    2. Add      'unique-element-id': 'initMyPage'       here
// ─────────────────────────────────────────────────────────────
const PAGE_REGISTRY = {
    'route-search'      : 'initRoutesPage',
    'bookings-table'    : 'initBookingsPage',
    'drivers-table'     : 'initDriversPage',
    'dashboard-stats'   : 'initDashboardPage',
    // add more pages here as you build them
};


// ─────────────────────────────────────────────────────────────
//  ACTIVE SIDEBAR  —  runs on every navigation + on first load
//
//  Strategy: compare each .menu-btn's href against the current
//  window.location.pathname.  Works after AJAX nav AND after a
//  hard redirect (e.g. after form submit + header('Location:...')).
// ─────────────────────────────────────────────────────────────
function setActiveSidebarLink() {
    // Normalize: strip trailing slash, lowercase for safe compare
    const currentPath = window.location.pathname.replace(/\/$/, '').toLowerCase();

    document.querySelectorAll('.menu-btn').forEach(btn => {
        const href = btn.getAttribute('href');
        if (!href) return;

        // Normalize the link href the same way
        const linkPath = href.replace(/\/$/, '').toLowerCase();

        // Mark active if the current URL ends with this link's path.
        // Using endsWith() instead of === so relative paths still match.
        // e.g. href="pages/routes.php" matches "/admin/pages/routes.php"
        if (currentPath.endsWith(linkPath)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}


// ─────────────────────────────────────────────────────────────
//  PAGE DISPATCHER  —  detects which page is loaded and runs its init
// ─────────────────────────────────────────────────────────────
function dispatchPageInit() {
    for (const [elementId, fnName] of Object.entries(PAGE_REGISTRY)) {
        if (document.getElementById(elementId)) {
            if (typeof window[fnName] === 'function') {
                window[fnName]();
            } else {
                console.warn(`[SPA] Found #${elementId} but ${fnName}() is not defined yet.`);
            }
            return; // only one page can match at a time — stop checking
        }
    }
}


// ─────────────────────────────────────────────────────────────
//  PAGE LOADER BAR  (thin animated bar at the top during fetch)
// ─────────────────────────────────────────────────────────────
function showLoader() {
    document.querySelector('.page-loader')?.remove();
    const bar = document.createElement('div');
    bar.className = 'page-loader';
    document.body.appendChild(bar);
    setTimeout(() => bar.remove(), 700);
}


// ─────────────────────────────────────────────────────────────
//  LOAD PAGE  —  the heart of the SPA
//
//  Steps every time a link is clicked (or back/forward pressed):
//    1. Show loading bar
//    2. Fetch the full HTML of the target page
//    3. Parse it, pull out just the #page-content inner HTML
//    4. Inject it into the current page's #page-content
//    5. Re-execute any <script> tags that came with the new HTML
//    6. Update the browser URL (pushState)
//    7. Fix the sidebar active link
//    8. Run the correct page init function
// ─────────────────────────────────────────────────────────────
const pageContent = document.getElementById('page-content');

async function loadPage(url, pushState = true) {
    try {
        showLoader();

        // Fade out while loading
        pageContent.style.opacity    = '0.4';
        pageContent.style.transition = 'opacity 0.15s ease';

        const res  = await fetch(url);
        const html = await res.text();

        // Parse the fetched HTML as a document so we can query it
        const doc        = new DOMParser().parseFromString(html, 'text/html');
        const newContent = doc.getElementById('page-content');
        const newTitle   = doc.querySelector('.page-title');

        if (!newContent) {
            // The fetched page has no #page-content — do a hard redirect
            window.location.href = url;
            return;
        }

        // Inject the new page's content
        pageContent.innerHTML = newContent.innerHTML;

        // Update page title in the topbar (if your layout has one)
        const titleEl = document.querySelector('.page-title');
        if (titleEl && newTitle) titleEl.textContent = newTitle.textContent;

        // Fade back in
        pageContent.style.opacity = '1';

        // Push the new URL into the browser history so back/forward work
        if (pushState) history.pushState({ url }, '', url);

        // ── Re-execute <script> tags injected with the HTML ──────
        //  When you set innerHTML, browsers do NOT auto-execute scripts.
        //  We must recreate each script node manually.
        pageContent.querySelectorAll('script').forEach(old => {
            const s = document.createElement('script');

            if (old.src) {
                // External script (src="...") — copy the src
                s.src   = old.src;
                s.async = false;
            } else {
                // Inline script — copy the text content
                s.textContent = old.textContent;
            }

            old.replaceWith(s);
        });

        // ── Update sidebar + run page JS ─────────────────────────
        setActiveSidebarLink();
        dispatchPageInit();

    } catch (err) {
        console.error('[SPA] loadPage failed:', err);
        window.location.href = url; // hard fallback — never leave user stuck
    }
}


// ─────────────────────────────────────────────────────────────
//  BOOT  — everything below runs once when the shell loads
// ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // ── SIDEBAR TOGGLE (burger menu) ─────────────────────────
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebar-overlay');
    const burgerBtn = document.getElementById('burger-btn');

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

    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar on mobile when any menu link is clicked
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });

    // ── INTERCEPT SIDEBAR LINK CLICKS ────────────────────────
    //  Instead of letting the browser follow the <a href>, we
    //  catch the click and load the page via AJAX.
    document.querySelectorAll('.menu-btn').forEach(btn => {
        const href = btn.getAttribute('href');

        // Skip: logout links, external URLs, empty hrefs
        if (!href || href.includes('logout') || href.startsWith('http')) return;

        btn.addEventListener('click', e => {
            e.preventDefault();     // stop normal browser navigation
            loadPage(href);         // load via AJAX instead
        });
    });

    // ── BROWSER BACK / FORWARD ───────────────────────────────
    window.addEventListener('popstate', e => {
        if (e.state?.url) {
            loadPage(e.state.url, false); // false = don't pushState again
        }
    });

    // ── NOTIFICATION DROPDOWN ────────────────────────────────
    const notifToggle   = document.getElementById('notif-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifDot      = document.getElementById('notif-dot');
    const markAllRead   = document.getElementById('mark-all-read');

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

    notifDropdown?.addEventListener('click', e => e.stopPropagation());

    // ── PROFILE DROPDOWN ─────────────────────────────────────
    const profileToggle  = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    const profileCaret   = document.getElementById('profile-caret');

    profileToggle?.addEventListener('click', e => {
        e.stopPropagation();
        notifDropdown?.classList.remove('open');
        profileDropdown?.classList.toggle('open');
        profileCaret?.classList.toggle('open');
    });

    profileDropdown?.addEventListener('click', e => e.stopPropagation());

    // Close all dropdowns when clicking anywhere else on the page
    document.addEventListener('click', () => {
        notifDropdown?.classList.remove('open');
        profileDropdown?.classList.remove('open');
        profileCaret?.classList.remove('open');
    });

    // ── FIRST-LOAD: set active link + run current page's JS ──
    //  The page HTML is already in the DOM (no fetch needed),
    //  so we just dispatch immediately.
    setActiveSidebarLink();
    dispatchPageInit();
});