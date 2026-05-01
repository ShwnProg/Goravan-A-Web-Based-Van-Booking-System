//STATIC COORDS Southern Leyte
var COORDS = {
    "Maasin City": [10.1322, 124.8426],
    "Bontoc": [10.2167, 124.8833],
    "Sogod": [10.3833, 124.9833],
    "Malitbog": [10.1667, 124.8167],
    "Padre Burgos": [10.0167, 125.0167],
    "Limasawa": [9.9000, 125.1000],
    "Liloan": [10.1000, 124.7167],
    "Macrohon": [10.0667, 124.9167],
    "San Juan": [10.2333, 125.1667],
    "Silago": [10.5167, 125.1833],
    "Hinunangan": [10.4000, 125.2000],
    "Hinundayan": [10.3667, 125.1333],
    "St. Bernard": [10.4833, 125.1333],
    "San Ricardo": [10.2667, 125.2167],
    "Tomas Oppus": [10.2500, 124.9833],
    "San Francisco": [10.2000, 125.0167],
    "Libagon": [10.1500, 124.9667],
    "Anahawan": [10.1000, 125.0333],
    "Bato": [10.3333, 124.9667],
    "Pintuyan": [10.0833, 125.1833],
};

//  DYNAMIC STOPS CONFIG 
var MAX_STOPS = 3;

function buildLocationOptions() {
    var html = '<option value="">None</option>';
    Object.keys(COORDS).forEach(function (name) {
        html += '<option value="' + name + '">' + name + '</option>';
    });
    return html;
}

// Add a stop row 
function addStopRow(container, addBtn, counterEl, value) {
    var current = container.querySelectorAll('.stop-row').length;
    if (current >= MAX_STOPS) return;

    var idx = current + 1;
    var row = document.createElement('div');
    row.className = 'stop-row dynamic-stop-row';
    row.innerHTML =
        '<span class="stop-num">' + idx + '</span>' +
        '<select name="stops[]" class="ss stop-ss" data-placeholder="Select stop">' +
        buildLocationOptions() +
        '</select>' +
        '<button type="button" class="btn-remove-stop" title="Remove stop">' +
        '<i class="fas fa-times"></i>' +
        '</button>';

    container.appendChild(row);
    var sel = row.querySelector('select.ss');
    window.buildSearchableSelects(row);


    if (value) {
        window.syncSS(sel, value);
    }

    updateStopsUI(container, addBtn, counterEl);

    row.querySelector('.btn-remove-stop').addEventListener('click', function () {
        row.remove();
        renumberStops(container);
        updateStopsUI(container, addBtn, counterEl);
    });
}

function renumberStops(container) {
    container.querySelectorAll('.stop-row .stop-num').forEach(function (el, i) {
        el.textContent = i + 1;
    });
}

function updateStopsUI(container, addBtn, counterEl) {
    var count = container.querySelectorAll('.stop-row').length;
    if (counterEl) counterEl.textContent = count + ' / ' + MAX_STOPS;
    if (addBtn) {
        if (count >= MAX_STOPS) {
            addBtn.classList.add('btn-add-stop--disabled');
            addBtn.disabled = true;
        } else {
            addBtn.classList.remove('btn-add-stop--disabled');
            addBtn.disabled = false;
        }
    }
}

function clearStops(container, addBtn, counterEl) {
    container.querySelectorAll('.stop-row').forEach(function (r) { r.remove(); });
    updateStopsUI(container, addBtn, counterEl);
}

//  MAP VAR
var map = null;
var markers = [];
var polyline = null;

function initMap() {
    if (map !== null) return;
    map = L.map('route-map', { attributionControl: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    map.setView([10.2, 124.95], 10);
}

function clearLayers() {
    for (var i = 0; i < markers.length; i++) map.removeLayer(markers[i]);
    markers = [];
    if (polyline) { map.removeLayer(polyline); polyline = null; }
}

//  SHOW ROUTE 
function showRoute(origin, destination, stops) {
    var mapEl = document.getElementById('route-map');
    var emptyEl = document.getElementById('map-empty');

    emptyEl.style.display = 'none';
    mapEl.style.display = 'block';

    initMap();

    setTimeout(function () {
        map.invalidateSize();
        clearLayers();

        document.getElementById('map-label').innerHTML = `
    <span style="display:inline-flex; align-items:center; gap:6px; white-space:nowrap;">
        <span>${origin}</span>
        <i class="fas fa-arrow-right" style="font-size:10px; opacity:.6;"></i>
        <span>${destination}</span>
    </span>`;
    
        document.getElementById('map-origin-label').textContent = origin;
        document.getElementById('map-dest-label').textContent = destination;

        var stopsListEl = document.getElementById('map-stops-list');
        stopsListEl.innerHTML = '';
        for (var i = 0; i < stops.length; i++) {
            stopsListEl.innerHTML +=
                '<div class="map-stop">' +
                '<div class="stop-dot via"></div>' +
                '<div>' +
                '<div class="stop-label">Via</div>' +
                '<span>' + stops[i] + '</span>' +
                '</div>' +
                '</div>' +
                '<div style="margin-left:4px"><div class="stop-connector"></div></div>';
        }
        document.getElementById('map-route-info').classList.add('visible');

        var allPoints = [origin].concat(stops).concat([destination]);
        var latLngs = [];
        var missing = [];

        for (var j = 0; j < allPoints.length; j++) {
            var name = allPoints[j];
            if (COORDS[name]) {
                latLngs.push(COORDS[name]);
            } else {
                missing.push(name);
            }
        }

        if (missing.length > 0) {
            console.warn('No coordinates for: ' + missing.join(', '));
            return;
        }

        for (var k = 0; k < allPoints.length; k++) {
            var color, label;
            if (k === 0) {
                color = '#16a34a';
                label = '<b>From:</b> ' + allPoints[k];
            } else if (k === allPoints.length - 1) {
                color = '#ef4444';
                label = '<b>To:</b> ' + allPoints[k];
            } else {
                color = '#3b82f6';
                label = '<b>Via:</b> ' + allPoints[k];
            }

            var icon = L.divIcon({
                className: '',
                html: '<div style="width:13px;height:13px;background:' + color + ';border:2.5px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',
                iconAnchor: [6, 6]
            });

            var marker = L.marker(latLngs[k], { icon: icon })
                .addTo(map)
                .bindPopup(label);
            markers.push(marker);
        }

        polyline = L.polyline(latLngs, {
            color: '#F97316',
            weight: 4,
            opacity: 0.85,
            lineJoin: 'round'
        }).addTo(map);

        map.fitBounds(polyline.getBounds(), { padding: [40, 40] });

    }, 80);
}


// SEARCHABLE SELECT

(function () {

    function buildSS(select) {
        if (select._ssBuilt) return;
        select._ssBuilt = true;

        var placeholder = select.dataset.placeholder || '— Select —';

        var wrap = document.createElement('div');
        wrap.className = 'ss-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ss-btn';

        var txt = document.createElement('span');
        txt.className = 'ss-btn-txt';

        var selOpt = select.options[select.selectedIndex];
        if (selOpt && selOpt.value) {
            txt.textContent = selOpt.text;
        } else {
            txt.textContent = placeholder;
            btn.classList.add('is-placeholder');
        }

        var arr = document.createElement('i');
        arr.className = 'fas fa-chevron-down ss-btn-arr';

        btn.appendChild(txt);
        btn.appendChild(arr);
        wrap.insertBefore(btn, select);

        var panel = document.createElement('div');
        panel.className = 'ss-panel';

        var showSearch = select.options.length > 5;
        var sinput = null;

        if (showSearch) {
            var searchWrap = document.createElement('div');
            searchWrap.className = 'ss-search-wrap';

            var si = document.createElement('i');
            si.className = 'fas fa-search';

            sinput = document.createElement('input');
            sinput.type = 'text';
            sinput.className = 'ss-search';
            sinput.placeholder = 'Type to search…';
            sinput.autocomplete = 'off';

            searchWrap.appendChild(si);
            searchWrap.appendChild(sinput);
            panel.appendChild(searchWrap);
        }

        var ul = document.createElement('ul');
        ul.className = 'ss-list';

        var noResults = document.createElement('li');
        noResults.className = 'ss-no-results';
        noResults.textContent = 'No results found';
        noResults.style.display = 'none';
        ul.appendChild(noResults);

        Array.from(select.options).forEach(function (opt) {
            var li = document.createElement('li');
            li.className = 'ss-item' + (!opt.value ? ' is-placeholder' : '') + (opt.selected && opt.value ? ' is-sel' : '');
            li.dataset.val = opt.value;
            li.dataset.text = opt.text;
            li.textContent = opt.text;
            ul.appendChild(li);
        });

        panel.appendChild(ul);
        wrap.insertBefore(panel, select);

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = panel.classList.contains('is-open');
            closeAll();
            if (!isOpen) {
                panel.classList.add('is-open');
                btn.classList.add('is-open');
                if (sinput) {
                    sinput.value = '';
                    filterList('');
                    setTimeout(function () { sinput.focus(); }, 20);
                }
            }
        });

        if (sinput) {
            sinput.addEventListener('input', function () {
                filterList(this.value.toLowerCase());
            });
            sinput.addEventListener('click', function (e) { e.stopPropagation(); });
        }

        ul.addEventListener('click', function (e) {
            var li = e.target.closest('.ss-item');
            if (!li) return;

            select.value = li.dataset.val;
            if (li.dataset.val) {
                txt.textContent = li.dataset.text;
                btn.classList.remove('is-placeholder');
            } else {
                txt.textContent = placeholder;
                btn.classList.add('is-placeholder');
            }

            ul.querySelectorAll('.ss-item.is-sel').forEach(function (x) { x.classList.remove('is-sel'); });
            if (li.dataset.val) li.classList.add('is-sel');

            closeAll();
        });

        function filterList(q) {
            var hasVisible = false;
            ul.querySelectorAll('.ss-item').forEach(function (li) {
                if (li.classList.contains('is-placeholder')) {
                    li.style.display = q ? 'none' : '';
                    return;
                }
                var match = !q || li.dataset.text.toLowerCase().includes(q);
                li.style.display = match ? '' : 'none';
                if (match) hasVisible = true;
            });
            noResults.style.display = (q && !hasVisible) ? '' : 'none';
        }
    }

    function closeAll() {
        document.querySelectorAll('.ss-panel.is-open').forEach(function (p) { p.classList.remove('is-open'); });
        document.querySelectorAll('.ss-btn.is-open').forEach(function (b) { b.classList.remove('is-open'); });
    }

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });

    window.buildSearchableSelects = function (root) {
        (root || document).querySelectorAll('select.ss').forEach(buildSS);
    };

    window.syncSS = function (selectEl, value) {
        if (!selectEl._ssBuilt) return;
        selectEl.value = value;
        var wrap = selectEl.closest('.ss-wrap');
        if (!wrap) return;
        var btn = wrap.querySelector('.ss-btn');
        var txt = wrap.querySelector('.ss-btn-txt');
        var ul = wrap.querySelector('.ss-list');
        var ph = selectEl.dataset.placeholder || '— Select —';

        var selOpt = selectEl.options[selectEl.selectedIndex];
        if (selOpt && selOpt.value) {
            txt.textContent = selOpt.text;
            btn.classList.remove('is-placeholder');
        } else {
            txt.textContent = ph;
            btn.classList.add('is-placeholder');
        }
        if (ul) {
            ul.querySelectorAll('.ss-item').forEach(function (li) {
                li.classList.toggle('is-sel', li.dataset.val === value && value !== '');
            });
        }
    };

})();

document.addEventListener('DOMContentLoaded', function () {


    window.buildSearchableSelects(document);
    document.addEventListener('shown.bs.modal', function (e) {
        window.buildSearchableSelects(e.target);
    });

    //  ROUTE COUNT BADGE 
    var rows = document.querySelectorAll('.route-row');
    var countEl = document.getElementById('route-count');
    if (countEl) {
        countEl.textContent = rows.length + ' route' + (rows.length !== 1 ? 's' : '');
    }

    //  SEARCH FILTER 
    var searchInput = document.getElementById('route-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            rows.forEach(function (row) {
                var match = row.dataset.origin.toLowerCase().includes(q) ||
                    row.dataset.destination.toLowerCase().includes(q);
                row.style.display = match ? '' : 'none';
            });
        });
    }

    //  CLICK ROW 
    rows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.row-actions')) return;

            rows.forEach(function (r) { r.classList.remove('selected'); });
            this.classList.add('selected');

            var origin = this.dataset.origin;
            var destination = this.dataset.destination;
            var stops = this.dataset.stops ? JSON.parse(this.dataset.stops) : [];

            showRoute(origin, destination, stops);
        });
    });

    // ADD MODAL 
    var addModal = new bootstrap.Modal(document.getElementById('addModal'));
    var addContainer = document.getElementById('stops-container');
    var addBtn = document.getElementById('add-stop-btn');
    var addCounter = document.getElementById('add-stops-counter');

    document.getElementById('open-add-modal').addEventListener('click', function () {
        clearStops(addContainer, addBtn, addCounter);
        addModal.show();
    });

    addBtn.addEventListener('click', function () {
        addStopRow(addContainer, addBtn, addCounter, '');
    });

    //  EDIT MODAL 
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    var editContainer = document.getElementById('edit-stops-container');
    var editAddBtn = document.getElementById('edit-add-stop-btn');
    var editCounter = document.getElementById('edit-stops-counter');
    var editBtns = document.querySelectorAll('.icon-btn.edit');

    editAddBtn.addEventListener('click', function () {
        addStopRow(editContainer, editAddBtn, editCounter, '');
    });

    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            document.getElementById('edit-id').value = this.dataset.id;

            var originSel = document.getElementById('edit-origin');
            var destSel = document.getElementById('edit-destination');
            var fareInput = document.getElementById('edit-fare');
            var statusSel = document.getElementById('edit-status');

            window.syncSS(originSel, this.dataset.origin);
            window.syncSS(destSel, this.dataset.destination);

            fareInput.value = this.dataset.fare;

            window.syncSS(statusSel, this.dataset.active);

            clearStops(editContainer, editAddBtn, editCounter);
            var stops = this.dataset.stops ? JSON.parse(this.dataset.stops) : [];
            stops.forEach(function (stop) {
                addStopRow(editContainer, editAddBtn, editCounter, stop);
            });

            editModal.show();
        });
    });

    //  DELETE MODAL 
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".delete");
        if (!btn) return;

        const routeId = btn.dataset.id;
        const routeName = btn.dataset.route;

        Swal.fire({
            title: "Delete Route?",
            text: routeName,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "Yes, delete",
            cancelButtonText: "Cancel",
            reverseButtons: true
        }).then((result) => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: "Deleting...",
                    text: "Please wait",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                setTimeout(() => {

                    const form = document.createElement("form");
                    form.method = "POST";
                    form.action = "../../controllers/routes/DeleteRoute.php";

                    // CSRF
                    const csrf = document.createElement("input");
                    csrf.type = "hidden";
                    csrf.name = "csrf_token";
                    csrf.value = document.querySelector('input[name="csrf_token"]').value;

                    // route id
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "route_id";
                    input.value = routeId;

                    form.appendChild(csrf);
                    form.appendChild(input);

                    document.body.appendChild(form);
                    form.submit();

                }, 700);

            }
        });
    });

    //  TOGGLE STATUS 
    var toggleBtns = document.querySelectorAll('.icon-btn.toggle');
    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../controllers/routes/ToggleRoute.php';
            form.innerHTML =
                '<input type="hidden" name="route_id"  value="' + this.dataset.id + '">' +
                '<input type="hidden" name="is_active" value="' + (this.dataset.active == 1 ? 0 : 1) + '">';
            document.body.appendChild(form);
            form.submit();
        });
    });

});