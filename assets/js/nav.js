document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const burgerBtn = document.getElementById('burger-btn');
    const pageContent = document.getElementById('page-content');

    //  BURGER TOGGLE 
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        burgerBtn.classList.add('open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        burgerBtn.classList.remove('open');
    }

    burgerBtn?.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    // Close sidebar on mobile when a link is clicked
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });

    //  AJAX NAVIGATION 
    function showLoader() {
        document.querySelector('.page-loader')?.remove();
        const bar = document.createElement('div');
        bar.className = 'page-loader';
        document.body.appendChild(bar);
        setTimeout(() => bar.remove(), 700);
    }

    function setActiveLink(href) {
        document.querySelectorAll('.menu-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('href') === href) btn.classList.add('active');
        });
    }

    async function loadPage(url, pushState = true) {
        try {
            showLoader();
            pageContent.style.opacity = '0.4';
            pageContent.style.transition = 'opacity 0.15s ease';

            const res = await fetch(url);
            const html = await res.text();

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newContent = doc.getElementById('page-content');
            const newTitle = doc.querySelector('.page-title');
            const newGreeting = doc.querySelector('.topbar-greeting');

            if (newContent) pageContent.innerHTML = newContent.innerHTML;
            if (newTitle) document.querySelector('.page-title').textContent = newTitle.textContent;

            pageContent.style.opacity = '1';

            if (pushState) history.pushState({ url }, '', url);

            // Re-run inline scripts
            pageContent.querySelectorAll('script').forEach(old => {
                const s = document.createElement('script');
                s.textContent = old.textContent;
                old.replaceWith(s);
            });

        } catch {
            window.location.href = url; // fallback
        }
    }

    document.querySelectorAll('.menu-btn').forEach(btn => {
        const href = btn.getAttribute('href');
        if (!href || href.includes('Logout') || href.startsWith('http')) return;

        btn.addEventListener('click', e => {
            e.preventDefault();
            setActiveLink(href);
            loadPage(href);
        });
    });

    window.addEventListener('popstate', e => {
        if (e.state?.url) {
            setActiveLink(e.state.url.split('/').pop());
            loadPage(e.state.url, false);
        }
    });

    // ── NOTIFICATION DROPDOWN ─────────────────────
    const notifToggle = document.getElementById('notif-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifDot = document.getElementById('notif-dot');
    const markAllRead = document.getElementById('mark-all-read');

    notifToggle?.addEventListener('click', (e) => {
        e.stopPropagation();

        // close profile dropdown if open
        profileDropdown?.classList.remove('open');
        profileCaret?.classList.remove('open');

        notifDropdown.classList.toggle('open');
    });

    // Mark all as read
    markAllRead?.addEventListener('click', () => {
        document.querySelectorAll('.notif-item.unread')
            .forEach(item => item.classList.remove('unread'));
        notifDot.style.display = 'none'; // hide the dot
    });

    // Close when clicking outside
    document.addEventListener('click', () => {
        notifDropdown?.classList.remove('open');
    });

    notifDropdown?.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // ── PROFILE DROPDOWN ─────────────────────────
    const profileToggle = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    const profileCaret = document.getElementById('profile-caret');

    // inside the existing profile toggle listener
    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();

        // close notif if open
        notifDropdown?.classList.remove('open'); // ← add this

        profileDropdown.classList.toggle('open');
        profileCaret.classList.toggle('open');
    });

    // Close when clicking outside
    document.addEventListener('click', () => {
        profileDropdown?.classList.remove('open');
        profileCaret?.classList.remove('open');
    });

    // Prevent closing when clicking inside dropdown
    profileDropdown?.addEventListener('click', (e) => {
        e.stopPropagation();
    });
});