document.addEventListener("DOMContentLoaded", function() {
    const sidebarLinks = document.querySelectorAll("#sidebar .nav-link");
    sidebarLinks.forEach(link => {
        if (link.getAttribute("href") === "/admin") {
            link.classList.remove("text-white-50");
            link.classList.add("active");
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {

    // ── Selection state ────────────────────────────────────────────────────────
    const selectedIds = new Set();

    function updateDeleteButton() {
        document.getElementById("btn-delete").disabled = selectedIds.size === 0;
    }

    // ── Pending delete (supports both bulk and row-level) ──────────────────────
    let pendingDeleteIds = [];

    // ── Status filter state ────────────────────────────────────────────────────
    let statusFilter = "";

    const postsTable = new DataTable("#posts-table", {

        // ── Layout & UI ────────────────────────────────────────────────────────
        autoWidth:    true,
        info:         true,
        lengthChange: true,
        ordering:     true,
        paging:       true,
        searching:    true,
        orderMulti:   true,
        orderClasses: true,
        pagingType:   "simple_numbers",
        pageLength:   25,
        lengthMenu:   [10, 25, 50, 100],

        // ── Default sort ───────────────────────────────────────────────────────
        order: [[6, "desc"]],

        // ── Performance ────────────────────────────────────────────────────────
        processing: true,
        serverSide: true,
        stateSave:  false,

        // ── Data source ────────────────────────────────────────────────────────
        ajax: {
            url: "/admin/posts/datatable",
            data: function(d) {
                d.status_filter = statusFilter;
            },
        },

        // ── Column definitions ─────────────────────────────────────────────────
        columns: [
            {
                // Column 0 — Checkbox
                data:           null,
                title:          '<input type="checkbox" id="select-all-checkbox" class="form-check-input" aria-label="Select all rows on this page">',
                orderable:      false,
                searchable:     false,
                visible:        true,
                width:          "2rem",
                className:      "text-center",
                defaultContent: '<input type="checkbox" class="row-select form-check-input" aria-label="Select row">',
            },
            {
                // Column 1 — ID
                name:       "id",
                data:       "id",
                title:      "#",
                type:       "num",
                orderable:  true,
                searchable: false,
                visible:    true,
                width:      "3rem",
                className:  "text-end",
            },
            {
                // Column 2 — Title
                name:       "title",
                data:       "title",
                title:      "Title",
                type:       "html",
                orderable:  true,
                searchable: true,
                visible:    true,
                width:      "",
            },
            {
                // Column 3 — Status
                name:       "status",
                data:       "status",
                title:      "Status",
                type:       "html",
                orderable:  true,
                searchable: true,
                visible:    true,
                width:      "6rem",
                className:  "text-center",
            },
            {
                // Column 4 — Tags
                name:       "tags",
                data:       "tags",
                title:      "Tags",
                type:       "html",
                orderable:  false,
                searchable: true,
                visible:    true,
                width:      "",
            },
            {
                // Column 5 — Views
                name:       "hitcounter",
                data:       "hitcounter",
                title:      "Views",
                type:       "num",
                orderable:  true,
                searchable: false,
                visible:    true,
                width:      "5rem",
                className:  "text-end",
            },
            {
                // Column 6 — Published date
                name:       "published_at",
                data:       "published_at",
                title:      "Published",
                type:       "html",
                orderable:  true,
                searchable: false,
                visible:    true,
                width:      "8rem",
                className:  "text-end text-nowrap",
            },
            {
                // Column 7 — Actions
                data:       null,
                title:      "",
                orderable:  false,
                searchable: false,
                visible:    true,
                width:      "6rem",
                className:  "text-center text-nowrap",
                render: function(data, type, row) {
                    return '<a href="/admin/posts/' + row.id + '/edit" class="btn btn-outline-secondary btn-sm btn-row-edit me-1" title="Edit">'
                         + '<i class="bi bi-pencil-fill"></i></a>'
                         + '<button class="btn btn-outline-danger btn-sm btn-row-delete" data-id="' + row.id + '" title="Delete">'
                         + '<i class="bi bi-trash3-fill"></i></button>';
                },
            },
        ],
    });

    // ── Select all checkbox ────────────────────────────────────────────────────
    document.querySelector("#posts-table").addEventListener("change", function(e) {
        if (e.target.id === "select-all-checkbox") {
            const checkboxes = document.querySelectorAll("#posts-table .row-select");
            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
                const id = parseInt(cb.closest("tr").dataset.id, 10);
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            });
            updateDeleteButton();
        }
    });

    // ── Row checkbox ──────────────────────────────────────────────────────────
    document.querySelector("#posts-table tbody").addEventListener("change", function(e) {
        if (e.target.classList.contains("row-select")) {
            const row  = postsTable.row(e.target.closest("tr"));
            const data = row.data();
            if (e.target.checked) {
                selectedIds.add(data.id);
            } else {
                selectedIds.delete(data.id);
            }
            updateDeleteButton();
        }
    });

    // Reset checkboxes on table redraw
    postsTable.on("draw", function() {
        selectedIds.clear();
        updateDeleteButton();
        const selectAll = document.getElementById("select-all-checkbox");
        if (selectAll) { selectAll.checked = false; }
    });

    // ── Status filter ─────────────────────────────────────────────────────────
    document.querySelectorAll(".status-filter-item").forEach(item => {
        item.addEventListener("click", function(e) {
            e.preventDefault();
            document.querySelectorAll(".status-filter-item").forEach(i => i.classList.remove("active"));
            this.classList.add("active");
            statusFilter = this.dataset.value;
            const label = this.dataset.value
                ? "Status: " + this.dataset.value.charAt(0).toUpperCase() + this.dataset.value.slice(1)
                : "Status: All";
            document.getElementById("btn-status-filter").innerHTML =
                '<i class="bi bi-funnel-fill"></i><span class="d-none d-lg-inline"> ' + label + "</span>";
            postsTable.ajax.reload();
        });
    });

    // ── Refresh button ────────────────────────────────────────────────────────
    document.getElementById("btn-datatable-refresh").addEventListener("click", function() {
        postsTable.ajax.reload();
    });

    // ── Stats refresh ─────────────────────────────────────────────────────────
    function formatCompact(value) {
        if (value >= 1_000_000) {
            return parseFloat((value / 1_000_000).toFixed(1)) + 'm';
        }
        if (value >= 1_000) {
            return parseFloat((value / 1_000).toFixed(1)) + 'k';
        }
        return value.toLocaleString();
    }

    function refreshStats() {
        fetch("/admin/stats")
            .then(res => res.json())
            .then(data => {
                Object.entries(data).forEach(([key, value]) => {
                    const el = document.querySelector('[data-stat="' + key + '"]');
                    if (el) { el.textContent = key === 'total_views' ? formatCompact(value) : value.toLocaleString(); }
                });
            });
    }

    // ── Delete modal ──────────────────────────────────────────────────────────
    const deleteModal = new bootstrap.Modal(document.getElementById("modal-delete-confirm"));

    function getRawStatusById(id) {
        let status = null;
        postsTable.rows().every(function() {
            if (this.data().id === id) { status = this.data().raw_status; }
        });
        return status;
    }

    function buildDeleteModalMessage(ids) {
        const trashedIds = ids.filter(id => getRawStatusById(id) === 'trashed');
        const toTrash    = ids.length - trashedIds.length;
        const parts      = [];
        if (toTrash > 0) {
            parts.push(toTrash + ' post' + (toTrash > 1 ? 's' : '') + ' will be moved to Trash.');
        }
        if (trashedIds.length > 0) {
            parts.push(trashedIds.length + ' already-trashed post' + (trashedIds.length > 1 ? 's' : '') + ' will be permanently deleted.');
        }
        return parts.join(' ');
    }

    // Bulk delete button → queue all selected IDs
    document.getElementById("btn-delete").addEventListener("click", function() {
        if (selectedIds.size === 0) { return; }
        pendingDeleteIds = Array.from(selectedIds);
        const countEl = document.getElementById("delete-modal-count");
        const bodyEl  = document.getElementById("delete-modal-body");
        if (countEl) { countEl.textContent = pendingDeleteIds.length; }
        if (bodyEl)  { bodyEl.textContent  = buildDeleteModalMessage(pendingDeleteIds); }
        deleteModal.show();
    });

    // Row-level delete button → queue just this row's ID
    document.querySelector("#posts-table tbody").addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-row-delete");
        if (!btn) { return; }
        const id = parseInt(btn.dataset.id, 10);
        pendingDeleteIds = [id];
        const countEl = document.getElementById("delete-modal-count");
        const bodyEl  = document.getElementById("delete-modal-body");
        if (countEl) { countEl.textContent = 1; }
        if (bodyEl)  { bodyEl.textContent  = buildDeleteModalMessage(pendingDeleteIds); }
        deleteModal.show();
    });

    // ── Confirm delete ────────────────────────────────────────────────────────
    document.getElementById("btn-delete-confirm").addEventListener("click", function() {
        const ids = pendingDeleteIds.slice();

        fetch("/admin/posts/delete", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ids }),
        })
            .then(res => res.json())
            .then(data => {
                deleteModal.hide();
                pendingDeleteIds = [];
                if (data.status === "success") {
                    selectedIds.clear();
                    updateDeleteButton();
                    postsTable.ajax.reload();
                    refreshStats();
                }
            });
    });
});

