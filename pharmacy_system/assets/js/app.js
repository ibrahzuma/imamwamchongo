// PharmaCare Plus - Application JavaScript
console.log('PharmaCare Plus loaded');

// Auto-dismiss flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.alert.alert-dismissible').forEach(function(alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        });
    }, 5000);

    // Confirm before deletes (catch-all for forms with data-confirm)
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
});

/* ------------------------------------------------------------------
 * DataTables auto-init.
 * Any <table class="js-datatable"> gets search + sort + pagination
 * + Excel/CSV/PDF/Print/Copy export buttons.
 *
 * Per-table tweaks via data attributes:
 *   data-no-sort="0,4"      -> column indices that should NOT be orderable
 *   data-no-export="0,4"    -> column indices to exclude from exports
 *   data-page-length="50"   -> rows per page (default 25)
 *   data-order='[[1,"desc"]]' -> initial sort
 *
 * Action columns: add  class="dt-no-export dt-no-sort"  to the <th>
 * to drop them from sorting and from spreadsheet exports.
 * ----------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') return;

    jQuery('table.js-datatable').each(function() {
        const $t = jQuery(this);
        if ($.fn.DataTable.isDataTable(this)) return;

        // Resolve which columns are excluded from sort / export.
        // Two ways: header class (.dt-no-sort, .dt-no-export) or data-* on <table>.
        const noSortByClass   = [];
        const noExportByClass = [];
        $t.find('thead th').each(function(i) {
            if (this.classList.contains('dt-no-sort'))   noSortByClass.push(i);
            if (this.classList.contains('dt-no-export')) noExportByClass.push(i);
        });
        const noSortAttr   = ($t.data('no-sort')   + '').split(',').map(s => parseInt(s, 10)).filter(Number.isFinite);
        const noExportAttr = ($t.data('no-export') + '').split(',').map(s => parseInt(s, 10)).filter(Number.isFinite);
        const noSort   = Array.from(new Set([...noSortByClass,   ...noSortAttr]));
        const noExport = Array.from(new Set([...noExportByClass, ...noExportAttr]));

        const exportColumns = noExport.length
            ? ':visible:not(' + noExport.map(i => ':eq(' + i + ')').join(',') + ')'
            : ':visible';

        const columnDefs = [];
        if (noSort.length) columnDefs.push({ orderable: false, targets: noSort });

        const opts = {
            pageLength: parseInt($t.data('page-length'), 10) || 25,
            lengthMenu: [10, 25, 50, 100, 200],
            order:      $t.data('order') || [],
            columnDefs: columnDefs,
            // Row 1: length selector (left)  |  export buttons (right, compact)
            // Row 2: search box
            // Row 3: table
            // Row 4: info | pagination
            dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6 text-sm-end dt-toolbar'B>>" +
                 "<'row mb-2'<'col-12'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                    className: 'btn btn-sm btn-primary',
                    exportOptions: { columns: exportColumns, stripHtml: true }
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="bi bi-file-earmark-text"></i> CSV',
                    className: 'btn btn-sm btn-primary',
                    exportOptions: { columns: exportColumns, stripHtml: true }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-primary',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: exportColumns, stripHtml: true }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-primary',
                    exportOptions: { columns: exportColumns }
                },
                {
                    extend: 'copyHtml5',
                    text: '<i class="bi bi-clipboard"></i> Copy',
                    className: 'btn btn-sm btn-primary',
                    exportOptions: { columns: exportColumns, stripHtml: true }
                }
            ]
        };

        $t.DataTable(opts);
    });
});

// Utility: format currency
function formatMoney(amount) {
    const cur = window.CURRENCY || 'TZS';
    return cur + ' ' + parseFloat(amount).toFixed(2);
}

// Utility: escape HTML
function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' };
    return String(s).replace(/[&<>"']/g, m => map[m]);
}
