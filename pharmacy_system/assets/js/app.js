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
