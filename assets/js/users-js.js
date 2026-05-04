window.initUsersPage = function () {

    var tbody = document.getElementById('users-tbody');
    var countBadge = document.getElementById('user-count');
    var searchInput = document.getElementById('user-search');
    var veriFilter = document.getElementById('user-verify-filter');

    if (!tbody) return;

    /* ── Modal instances ─────────────────────── */
    var viewModal = _modal('viewModal');

    /* ── Count badge ─────────────────────────── */
    function updateCount() {
        var visible = tbody.querySelectorAll('tr.user-row:not([style*="display: none"])').length;
        if (countBadge) {
            countBadge.textContent = visible + ' user' + (visible !== 1 ? 's' : '');
        }
    }
    updateCount();

    /* ── Search + filter ─────────────────────── */
    function applyFilters() {
        var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var veri = veriFilter ? veriFilter.value : '';

        tbody.querySelectorAll('tr.user-row').forEach(function (row) {
            var matchQ = !q
                || (row.dataset.fullname || '').toLowerCase().includes(q)
                || (row.dataset.email || '').toLowerCase().includes(q)
                || (row.dataset.contact || '').toLowerCase().includes(q);
            var matchV = !veri || (row.dataset.verifyStatus || '') === veri;
            row.style.display = matchQ && matchV ? '' : 'none';
        });
        updateCount();
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (veriFilter) veriFilter.addEventListener('change', applyFilters);

    /* ── Row highlight ───────────────────────── */
    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.row-actions')) return;
        var row = e.target.closest('tr.user-row');
        if (!row) return;
        tbody.querySelectorAll('tr.user-row.selected').forEach(function (r) {
            r.classList.remove('selected');
        });
        row.classList.add('selected');
    });

    /* ── VIEW: open modal + load docs ───────── */
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.icon-btn.view');
        if (!btn || !viewModal) return;
        e.stopPropagation();

        document.getElementById('view-fullname').textContent = btn.dataset.fullname || '—';
        document.getElementById('view-email').textContent = btn.dataset.email || '—';
        document.getElementById('view-contact').textContent = btn.dataset.contact || 'N/A';
        document.getElementById('view-birthdate').textContent = btn.dataset.birthdate
            ? new Date(btn.dataset.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            : 'N/A';

        var docsContainer = document.getElementById('udv-docs-container');
        docsContainer.innerHTML = '<p class="text-muted-sm">Loading documents…</p>';

        viewModal.show();
        
        fetch('../../controllers/UsersController.php?action=get-docs&user_id=' + encodeURIComponent(btn.dataset.id))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    docsContainer.innerHTML = '<p class="text-muted-sm">' + (data.message || 'Error loading documents.') + '</p>';
                    return;
                }
                _renderDocs(docsContainer, data.documents || []);
            })
            .catch(() => {
                docsContainer.innerHTML = '<p class="text-muted-sm">Network error.</p>';
            });
    });

    /* ── Render document list ────────────────── */
    function _renderDocs(container, docs) {
        if (!docs.length) {
            container.innerHTML =
                '<div class="udv-empty-docs"><i class="fas fa-inbox"></i><p>No documents submitted</p></div>';
            return;
        }

        var html = '<div class="udv-docs-list">';
        docs.forEach(function (doc) {
            var status = _esc(doc.status || 'pending');
            var docType = _esc(doc.document_type || 'N/A');
            var submitted = doc.submitted_at
                ? new Date(doc.submitted_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
                : 'N/A';

            html += '<div class="udv-doc-item">';

            html += '  <div class="udv-doc-header">';
            html += '    <div class="udv-doc-info">';
            html += '      <span class="udv-doc-type">' + docType + '</span>';
            html += '      <span class="badge ' + status + '">' + _ucFirst(status) + '</span>';
            html += '    </div>';
            html += '    <span class="udv-doc-date">Submitted: ' + submitted + '</span>';
            html += '  </div>';

            /* Document preview area */
            if (doc.file_path) {
                html += '<div class="udv-doc-preview-area" data-file="' + _esc(doc.file_path) + '" data-type="' + _esc(doc.document_type || '') + '">';
                html += '  <div class="udv-preview-placeholder">';
                html += '    <i class="fas fa-file"></i>';
                html += '    <p>Click to preview</p>';
                html += '  </div>';
                html += '</div>';
            }

            /* Action buttons - only if status is pending */
            if (doc.status === 'pending') {
                html += '<div class="udv-doc-actions">';
                html += '  <button class="udv-btn-small approve" data-doc-id="' + _esc(doc.document_id_pk) + '"><i class="fas fa-check"></i> Approve</button>';
                html += '  <button class="udv-btn-small reject" data-doc-id="' + _esc(doc.document_id_pk) + '"><i class="fas fa-times"></i> Reject</button>';
                html += '</div>';
            }

            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;

        /* Attach preview handlers */
        container.querySelectorAll('.udv-doc-preview-area').forEach(function (el) {
            el.addEventListener('click', function () {
                var filePath = el.dataset.file;
                var docType = el.dataset.type;
                _showDocumentPreview(filePath, docType);
            });
        });

        /* Attach approval handlers */
        container.querySelectorAll('.udv-btn-small').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var docId = btn.dataset.docId;
                var status = btn.classList.contains('approve') ? 'approved' : 'rejected';
                _updateDocStatus(docId, status);
            });
        });
    }

    /* ── Document preview modal ──────────────── */
    function _showDocumentPreview(filePath, docType) {
        var ext = filePath.split('.').pop().toLowerCase();
        var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        var isPdf = ext === 'pdf';

        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.setAttribute('id', 'doc-preview-modal-' + Date.now());
        modal.setAttribute('tabindex', '-1');

        var content = '<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">';
        content += '<div class="modal-header"><h6 class="modal-title">' + _esc(docType) + ' - Preview</h6>';
        content += '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>';
        content += '<div class="modal-body" style="text-align: center; max-height: 70vh; overflow-y: auto;">';

        if (isImage) {
            content += '<img src="../../uploads/documents/' + _esc(filePath) + '" style="max-width: 100%; max-height: 100%;">';
        } else if (isPdf) {
            content += '<iframe src="../../uploads/documents/' + _esc(filePath) + '" style="width: 100%; height: 600px;"></iframe>';
        } else {
            content += '<p><i class="fas fa-file"></i> <strong>' + _esc(filePath) + '</strong></p>';
            content += '<p><a href="../../uploads/documents/' + _esc(filePath) + '" download class="btn btn-sm btn-primary"><i class="fas fa-download"></i> Download</a></p>';
        }

        content += '</div></div></div>';
        modal.innerHTML = content;
        document.body.appendChild(modal);

        var previewModal = bootstrap.Modal.getOrCreateInstance(modal);
        previewModal.show();

        modal.addEventListener('hidden.bs.modal', function () {
            document.body.removeChild(modal);
        });
    }

    function _updateDocStatus(docId, status) {
        _post('../../controllers/UsersController.php?action=update-doc', {
            document_id: docId,
            status: status,
            csrf_token: _csrf()
        }).then(function (d) {
            if (d.success) {
                Swal.fire({ icon: 'success', title: 'Done', text: d.message })
                    .then(function () { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.message });
            }
        }).catch(function () { Swal.fire('Error', 'Network error.', 'error'); });
    }

    /* ── Helpers ─────────────────────────────── */
    function _modal(id) {
        var el = document.getElementById(id);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    function _val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    function _csrf() {
        var el = document.getElementById('page-csrf-token');
        return el ? el.value : '';
    }

    function _post(url, data) {
        var body = Object.keys(data).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
        }).join('&');
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function (r) { return r.json(); });
    }

    function _esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function _ucFirst(str) {
        return String(str).charAt(0).toUpperCase() + String(str).slice(1);
    }

    function _resetAddForm() {
        ['add-fullname', 'add-email', 'add-contact', 'add-birthdate'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
    }
};

document.addEventListener('DOMContentLoaded', window.initUsersPage);