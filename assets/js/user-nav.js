/* ═══════════════════════════════════════════════════
   SEARCHABLE-SELECT WIDGET
   Registered once globally. buildSearchableSelects(root) can be called
   multiple times safely — it skips already-built selects.
══════════════════════════════════════════════════════════════════════════ */
if (!window._ssWidgetReady) {
    window._ssWidgetReady = true;

    (function () {

        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open').forEach(function (p) {
                p.classList.remove('is-open');
            });
            document.querySelectorAll('.ss-btn.is-open').forEach(function (b) {
                b.classList.remove('is-open');
            });
        }

        function buildSS(select) {
            if (select._ssBuilt) return; // already built — skip
            select._ssBuilt = true;

            var ph   = select.dataset.placeholder || '— Select —';
            var wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            /* Button */
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ss-btn';

            var txt = document.createElement('span');
            txt.className = 'ss-btn-txt';

            var cur = select.options[select.selectedIndex];
            if (cur && cur.value) {
                txt.textContent = cur.text;
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            btn.appendChild(txt);

            var arr = document.createElement('i');
            arr.className = 'fas fa-chevron-down ss-btn-arr';
            btn.appendChild(arr);
            wrap.insertBefore(btn, select);

            /* Panel */
            var panel = document.createElement('div');
            panel.className = 'ss-panel';

            var search = document.createElement('input');
            search.type        = 'text';
            search.placeholder = 'Search...';
            search.className   = 'ss-search';
            panel.appendChild(search);

            var ul = document.createElement('ul');
            ul.className = 'ss-list';
            panel.appendChild(ul);
            wrap.appendChild(panel);

            /* Options */
            Array.from(select.options).forEach(function (opt) {
                if (opt.value === '') return; // skip placeholder option
                var li         = document.createElement('li');
                li.className   = 'ss-item';
                li.dataset.val = opt.value;
                li.textContent = opt.text;
                if (select.value && select.value === opt.value) {
                    li.classList.add('is-sel');
                }
                li.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    select.value = opt.value;
                    syncSS(select, opt.value);
                    closeAll();
                });
                ul.appendChild(li);
            });

            /* Search filter */
            search.addEventListener('input', function () {
                var q = search.value.toLowerCase();
                ul.querySelectorAll('.ss-item').forEach(function (li) {
                    li.style.display = li.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });

            /* Open / close */
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var wasOpen = panel.classList.contains('is-open');
                closeAll();
                if (!wasOpen) {
                    btn.classList.add('is-open');
                    panel.classList.add('is-open');
                    search.value = '';
                    ul.querySelectorAll('.ss-item').forEach(function (li) {
                        li.style.display = '';
                    });
                    setTimeout(function () { search.focus(); }, 50);
                }
            });
        }

        /* Global helpers */
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });

        /**
         * Build SS widgets inside a root element.
         * Safe to call multiple times — skips already-built selects.
         */
        window.buildSearchableSelects = function (root) {
            (root || document).querySelectorAll('select.ss').forEach(buildSS);
        };

        /**
         * Sync the SS button text & highlighted item to a new value.
         * Call this whenever you programmatically change select.value.
         */
        window.syncSS = function (selectEl, value) {
            if (!selectEl || !selectEl._ssBuilt) return;
            selectEl.value = value;
            var wrap = selectEl.closest('.ss-wrap');
            if (!wrap) return;
            var btn  = wrap.querySelector('.ss-btn');
            var txt  = wrap.querySelector('.ss-btn-txt');
            var ul   = wrap.querySelector('.ss-list');
            var ph   = selectEl.dataset.placeholder || '— Select —';
            var opt  = selectEl.options[selectEl.selectedIndex];
            if (opt && opt.value) {
                txt.textContent = opt.text;
                btn.classList.remove('is-placeholder');
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            if (ul) {
                ul.querySelectorAll('.ss-item').forEach(function (li) {
                    li.classList.toggle('is-sel', li.dataset.val === String(value) && value !== '');
                });
            }
        };

        /* Expose close helper */
        window.closeAllSS = closeAll;

    })();
}

/* ═══════════════════════════════════════════════════
   USER NAVIGATION
══════════════════════════════════════════════════════════════════════════ */
(function () {
    /* ── Dark mode ── */
    var body = document.getElementById('userBody');
    var themeBtn = document.getElementById('themeToggle');
    var themeIcon = document.getElementById('themeIcon');

    // Persist preference
    var saved = localStorage.getItem('gv-theme');
    if (saved === 'dark') applyDark(true);

    themeBtn && themeBtn.addEventListener('click', function () {
        var isDark = body.classList.contains('dark');
        applyDark(!isDark);
        localStorage.setItem('gv-theme', isDark ? 'light' : 'dark');
    });

    function applyDark(on) {
        body.classList.toggle('dark', on);
        if (themeIcon) {
            themeIcon.className = on ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        // Switch logo for dark mode
        var logoImg = document.getElementById('logoImg');
        if (logoImg) {
            logoImg.src = on ? '../../images/logo_white.png' : '../../images/logo.png';
        }
    }

    /* ── Profile dropdown ── */
    var chip     = document.getElementById('profileChip');
    var dropdown = document.getElementById('profileDropdown');
    var caret    = document.getElementById('profileCaret');

    chip && chip.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = dropdown.classList.toggle('open');
        caret && caret.classList.toggle('open', open);
    });

    document.addEventListener('click', function () {
        dropdown && dropdown.classList.remove('open');
        caret && caret.classList.remove('open');
    });

    /* ── Filter tabs (Bookings page) ── */
    document.querySelectorAll('.u-ftab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.u-ftab').forEach(function (t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    /* ── Build searchable selects on page load ── */
    if (window.buildSearchableSelects) {
        window.buildSearchableSelects(document);
    }
})();