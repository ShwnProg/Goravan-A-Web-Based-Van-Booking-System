if (!window._ssReady) {
    window._ssReady = true;

    (function buildSearchableSelectSystem() {

        function closeAll() {
            document.querySelectorAll('.ss-panel.is-open')
                .forEach(p => p.classList.remove('is-open'));
            document.querySelectorAll('.ss-btn.is-open')
                .forEach(b => b.classList.remove('is-open'));
        }

        function buildSS(select) {
            if (select._ssBuilt) return;
            select._ssBuilt = true;

            const ph = select.dataset.placeholder || '— Select —';

            const wrap = document.createElement('div');
            wrap.className = 'ss-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'ss-btn';

            const txt = document.createElement('span');
            txt.className = 'ss-btn-txt';
            const current = select.options[select.selectedIndex];
            if (current?.value) {
                txt.textContent = current.text;
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            btn.appendChild(txt);
            btn.appendChild(Object.assign(document.createElement('i'), {
                className: 'fas fa-chevron-down ss-btn-arr'
            }));
            wrap.insertBefore(btn, select);

            const panel = document.createElement('div');
            panel.className = 'ss-panel';

            let sinput = null;
            if (select.options.length > 5) {
                const sw = document.createElement('div');
                sw.className = 'ss-search-wrap';
                sw.appendChild(Object.assign(document.createElement('i'), { className: 'fas fa-search' }));
                sinput = Object.assign(document.createElement('input'), {
                    type: 'text', className: 'ss-search',
                    placeholder: 'Type to search…', autocomplete: 'off'
                });
                sw.appendChild(sinput);
                panel.appendChild(sw);
            }

            const ul = document.createElement('ul');
            ul.className = 'ss-list';

            const noResults = Object.assign(document.createElement('li'), {
                className: 'ss-no-results', textContent: 'No results found'
            });
            noResults.style.display = 'none';
            ul.appendChild(noResults);

            Array.from(select.options).forEach(opt => {
                const li = Object.assign(document.createElement('li'), {
                    className: 'ss-item' +
                        (!opt.value ? ' is-placeholder' : '') +
                        (opt.selected && opt.value ? ' is-sel' : ''),
                    textContent: opt.text
                });
                li.dataset.val  = opt.value;
                li.dataset.text = opt.text;
                ul.appendChild(li);
            });

            panel.appendChild(ul);
            wrap.insertBefore(panel, select);

            btn.addEventListener('click', e => {
                e.stopPropagation();
                const wasOpen = panel.classList.contains('is-open');
                closeAll();
                if (!wasOpen) {
                    panel.classList.add('is-open');
                    btn.classList.add('is-open');
                    if (sinput) {
                        sinput.value = '';
                        filterList('');
                        setTimeout(() => sinput.focus(), 20);
                    }
                }
            });

            sinput?.addEventListener('input',  function () { filterList(this.value.toLowerCase()); });
            sinput?.addEventListener('click',  e => e.stopPropagation());

            ul.addEventListener('click', e => {
                const li = e.target.closest('.ss-item');
                if (!li) return;
                select.value = li.dataset.val;
                txt.textContent = li.dataset.val ? li.dataset.text : ph;
                btn.classList.toggle('is-placeholder', !li.dataset.val);
                ul.querySelectorAll('.ss-item.is-sel').forEach(x => x.classList.remove('is-sel'));
                if (li.dataset.val) li.classList.add('is-sel');
                closeAll();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            function filterList(q) {
                let hasVisible = false;
                ul.querySelectorAll('.ss-item').forEach(li => {
                    if (li.classList.contains('is-placeholder')) {
                        li.style.display = q ? 'none' : '';
                        return;
                    }
                    const match = !q || li.dataset.text.toLowerCase().includes(q);
                    li.style.display = match ? '' : 'none';
                    if (match) hasVisible = true;
                });
                noResults.style.display = (q && !hasVisible) ? '' : 'none';
            }
        }

        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });

        window.buildSearchableSelects = function (root) {
            (root || document).querySelectorAll('select.ss').forEach(buildSS);
        };

        window.syncSS = function (selectEl, value) {
            if (!selectEl?._ssBuilt) return;
            selectEl.value = value;
            const wrap = selectEl.closest('.ss-wrap');
            if (!wrap) return;
            const btn = wrap.querySelector('.ss-btn');
            const txt = wrap.querySelector('.ss-btn-txt');
            const ul  = wrap.querySelector('.ss-list');
            const ph  = selectEl.dataset.placeholder || '— Select —';
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt?.value) {
                txt.textContent = opt.text;
                btn.classList.remove('is-placeholder');
            } else {
                txt.textContent = ph;
                btn.classList.add('is-placeholder');
            }
            ul?.querySelectorAll('.ss-item').forEach(li => {
                li.classList.toggle('is-sel', li.dataset.val === value && value !== '');
            });
        };

    })();
}



//  STATIC COORDINATES

const ROUTE_COORDS = {
    "Maasin City"  : [10.1322, 124.8426],
    "Bontoc"       : [10.2167, 124.8833],
    "Sogod"        : [10.3833, 124.9833],
    "Malitbog"     : [10.1667, 124.8167],
    "Padre Burgos" : [10.0167, 125.0167],
    "Limasawa"     : [9.9000,  125.1000],
    "Liloan"       : [10.1000, 124.7167],
    "Macrohon"     : [10.0667, 124.9167],
    "San Juan"     : [10.2333, 125.1667],
    "Silago"       : [10.5167, 125.1833],
    "Hinunangan"   : [10.4000, 125.2000],
    "Hinundayan"   : [10.3667, 125.1333],
    "St. Bernard"  : [10.4833, 125.1333],
    "San Ricardo"  : [10.2667, 125.2167],
    "Tomas Oppus"  : [10.2500, 124.9833],
    "San Francisco": [10.2000, 125.0167],
    "Libagon"      : [10.1500, 124.9667],
    "Anahawan"     : [10.1000, 125.0333],
    "Bato"         : [10.3333, 124.9667],
    "Pintuyan"     : [10.0833, 125.1833],
};

const MAX_STOPS = 3;

//  MAP STATE
let _map      = null;
let _markers  = [];
let _polyline = null;

function destroyMap() {
    if (_map) {
        _map.remove();
        _map      = null;
        _markers  = [];
        _polyline = null;
    }
}

function initMap() {
    destroyMap();
    _map = L.map('route-map', { attributionControl: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(_map);
    _map.setView([10.2, 124.95], 10);
}

function clearLayers() {
    _markers.forEach(m => _map.removeLayer(m));
    _markers  = [];
    if (_polyline) { _map.removeLayer(_polyline); _polyline = null; }
}

function showRoute(origin, destination, stops) {
    const mapEl   = document.getElementById('route-map');
    const emptyEl = document.getElementById('map-empty');
    if (!mapEl || !emptyEl) return;

    emptyEl.style.display = 'none';
    mapEl.style.display   = 'block';

    if (!_map) initMap();

    setTimeout(() => {
        _map.invalidateSize();
        clearLayers();

        const mapLabel = document.getElementById('map-label');
        if (mapLabel) {
            mapLabel.innerHTML =
                `<span style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <span>${origin}</span>
                    <i class="fas fa-arrow-right" style="font-size:10px;opacity:.6;"></i>
                    <span>${destination}</span>
                 </span>`;
        }

        const originLbl = document.getElementById('map-origin-label');
        const destLbl   = document.getElementById('map-dest-label');
        if (originLbl) originLbl.textContent = origin;
        if (destLbl)   destLbl.textContent   = destination;

        const stopsList = document.getElementById('map-stops-list');
        if (stopsList) {
            stopsList.innerHTML = stops.map(stop =>
                `<div class="map-stop">
                    <div class="stop-dot via"></div>
                    <div><div class="stop-label">Via</div><span>${stop}</span></div>
                 </div>
                 <div style="margin-left:4px"><div class="stop-connector"></div></div>`
            ).join('');
        }

        document.getElementById('map-route-info')?.classList.add('visible');

        const allPoints = [origin, ...stops, destination];
        const latLngs   = [];
        const missing   = [];

        allPoints.forEach(name => {
            if (ROUTE_COORDS[name]) latLngs.push(ROUTE_COORDS[name]);
            else missing.push(name);
        });

        if (missing.length) {
            console.warn('[Routes] No coordinates for:', missing.join(', '));
            return;
        }

        allPoints.forEach((name, i) => {
            const isFirst = i === 0;
            const isLast  = i === allPoints.length - 1;
            const color   = isFirst ? '#16a34a' : isLast ? '#ef4444' : '#3b82f6';
            const label   = isFirst ? `<b>From:</b> ${name}` :
                            isLast  ? `<b>To:</b> ${name}`   : `<b>Via:</b> ${name}`;

            const icon = L.divIcon({
                className: '',
                html: `<div style="width:13px;height:13px;background:${color};
                              border:2.5px solid #fff;border-radius:50%;
                              box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>`,
                iconAnchor: [6, 6]
            });

            _markers.push(
                L.marker(latLngs[i], { icon }).addTo(_map).bindPopup(label)
            );
        });

        _polyline = L.polyline(latLngs, {
            color: '#F97316', weight: 4, opacity: 0.85, lineJoin: 'round'
        }).addTo(_map);

        _map.fitBounds(_polyline.getBounds(), { padding: [40, 40] });
    }, 80);
}


//  STOP ROW HELPERS
function buildLocationOptions() {
    return '<option value="">None</option>' +
        Object.keys(ROUTE_COORDS)
            .map(name => `<option value="${name}">${name}</option>`)
            .join('');
}

function addStopRow(container, addBtn, counterEl, value = '') {
    if (container.querySelectorAll('.stop-row').length >= MAX_STOPS) return;

    const idx = container.querySelectorAll('.stop-row').length + 1;
    const row = document.createElement('div');
    row.className = 'stop-row dynamic-stop-row';
    row.innerHTML =
        `<span class="stop-num">${idx}</span>
         <select name="stops[]" class="ss stop-ss" data-placeholder="Select stop">
             ${buildLocationOptions()}
         </select>
         <button type="button" class="btn-remove-stop" title="Remove stop">
             <i class="fas fa-times"></i>
         </button>`;

    container.appendChild(row);
    window.buildSearchableSelects(row);
    if (value) window.syncSS(row.querySelector('select.ss'), value);

    updateStopsUI(container, addBtn, counterEl);

    row.querySelector('.btn-remove-stop').addEventListener('click', () => {
        row.remove();
        renumberStops(container);
        updateStopsUI(container, addBtn, counterEl);
    });
}

function renumberStops(container) {
    container.querySelectorAll('.stop-row .stop-num')
        .forEach((el, i) => { el.textContent = i + 1; });
}

function updateStopsUI(container, addBtn, counterEl) {
    const count = container.querySelectorAll('.stop-row').length;
    if (counterEl) counterEl.textContent = `${count} / ${MAX_STOPS}`;
    if (addBtn) {
        addBtn.disabled = count >= MAX_STOPS;
        addBtn.classList.toggle('btn-add-stop--disabled', count >= MAX_STOPS);
    }
}

function clearStops(container, addBtn, counterEl) {
    container.querySelectorAll('.stop-row').forEach(r => r.remove());
    updateStopsUI(container, addBtn, counterEl);
}

function getModal(id) {
    const el = document.getElementById(id);
    if (!el) return null;
    return bootstrap.Modal.getOrCreateInstance(el);
}

window.initRoutesPage = function () {

    if (!document.getElementById('route-search')) return;

    const content = document.getElementById('page-content');
    if (content && !content._routesListenerAttached) {
        content._routesListenerAttached = true;
        content.addEventListener('click', handleContentClick);
    }

    destroyMap();
    window.buildSearchableSelects(document);

    // ROUTE COUNT BADGE 
    const rows    = document.querySelectorAll('.route-row');
    const countEl = document.getElementById('route-count');
    if (countEl) {
        countEl.textContent = `${rows.length} route${rows.length !== 1 ? 's' : ''}`;
    }

    // SEARCH 
    document.getElementById('route-search')?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        rows.forEach(row => {
            const match =
                row.dataset.origin.toLowerCase().includes(q) ||
                row.dataset.destination.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
        });
    });

    // sCLICK ROW to SHOW MAP 
    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.row-actions')) return;
            rows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            const stops = this.dataset.stops ? JSON.parse(this.dataset.stops) : [];
            showRoute(this.dataset.origin, this.dataset.destination, stops);
        });
    });

    // ADD MODAL
    const addContainer = document.getElementById('stops-container');
    const addBtn       = document.getElementById('add-stop-btn');
    const addCounter   = document.getElementById('add-stops-counter');

    document.getElementById('open-add-modal')?.addEventListener('click', () => {
        clearStops(addContainer, addBtn, addCounter);
        getModal('addModal')?.show();
    });

    addBtn?.addEventListener('click', () => addStopRow(addContainer, addBtn, addCounter));

    // EDIT MODAL 
    const editContainer = document.getElementById('edit-stops-container');
    const editAddBtn    = document.getElementById('edit-add-stop-btn');
    const editCounter   = document.getElementById('edit-stops-counter');

    editAddBtn?.addEventListener('click', () => addStopRow(editContainer, editAddBtn, editCounter));

};  


function handleContentClick(e) {

    // Edit 
    const editBtn = e.target.closest('.icon-btn.edit');
    if (editBtn) {
        e.stopPropagation();

        const editContainer = document.getElementById('edit-stops-container');
        const editAddBtn    = document.getElementById('edit-add-stop-btn');
        const editCounter   = document.getElementById('edit-stops-counter');

        document.getElementById('edit-id').value = editBtn.dataset.id;
        window.syncSS(document.getElementById('edit-origin'),      editBtn.dataset.origin);
        window.syncSS(document.getElementById('edit-destination'),  editBtn.dataset.destination);
        window.syncSS(document.getElementById('edit-status'),       editBtn.dataset.active);
        document.getElementById('edit-fare').value = editBtn.dataset.fare;

        clearStops(editContainer, editAddBtn, editCounter);
        (editBtn.dataset.stops ? JSON.parse(editBtn.dataset.stops) : [])
            .forEach(stop => addStopRow(editContainer, editAddBtn, editCounter, stop));

        getModal('editModal')?.show();
        return;
    }

    // Delete 
    const delBtn = e.target.closest('.icon-btn.delete');
    if (delBtn) {
        Swal.fire({
            title: 'Delete Route?',  text: delBtn.dataset.route,
            icon: 'warning',         showCancelButton: true,
            confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(result => {
            if (!result.isConfirmed) return;
            Swal.fire({
                title: 'Deleting…', text: 'Please wait',
                allowOutsideClick: false, allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            setTimeout(() => {
                const csrf = document.querySelector('input[name="csrf_token"]');
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../controllers/routes/DeleteRoute.php';
                form.innerHTML =
                    `<input type="hidden" name="csrf_token" value="${csrf?.value ?? ''}">
                     <input type="hidden" name="route_id"   value="${delBtn.dataset.id}">`;
                document.body.appendChild(form);
                form.submit();
            }, 700);
        });
        return;
    }


    const toggleBtn = e.target.closest('.icon-btn.toggle');
    if (toggleBtn) {
        e.stopPropagation();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../controllers/routes/ToggleRoute.php';
        form.innerHTML =
            `<input type="hidden" name="route_id"  value="${toggleBtn.dataset.id}">
             <input type="hidden" name="is_active" value="${toggleBtn.dataset.active == 1 ? 0 : 1}">`;
        document.body.appendChild(form);
        form.submit();
    }
}