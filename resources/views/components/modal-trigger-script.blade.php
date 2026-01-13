<script>
document.addEventListener('DOMContentLoaded', function() {
    // Listen for user menu clicks
    document.addEventListener('click', function(e) {
        const target = e.target.closest('a[href="javascript:void(0)"]');
        if (!target) return;

        const menuItem = target.textContent.trim();

        // Open appropriate modal based on menu text
        if (menuItem === 'Dokumentasi') {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'documentation' }));
        } else if (menuItem === 'Manual Book') {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manual-book' }));
        } else if (menuItem === 'Technical Documentation') {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'technical-documentation' }));
        }

        e.preventDefault();
    });
});
</script>
