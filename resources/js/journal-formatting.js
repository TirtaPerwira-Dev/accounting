// Format input nominal untuk jurnal
document.addEventListener('DOMContentLoaded', function() {
    // Format angka dengan pemisah ribuan
    function formatNumber(value) {
        if (!value) return '';

        // Remove all non-numeric characters
        const cleanValue = value.toString().replace(/[^\d]/g, '');

        // Add thousand separators
        return cleanValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Clean number for processing
    function cleanNumber(value) {
        if (!value) return 0;
        return parseFloat(value.toString().replace(/[^\d]/g, '')) || 0;
    }

    // Handle amount input formatting
    function handleAmountInput(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            let cursorPosition = e.target.selectionStart;

            // Get length before formatting
            let beforeLength = value.length;

            // Format the value
            let formatted = formatNumber(value);

            // Set the formatted value
            e.target.value = formatted;

            // Adjust cursor position based on length change
            let afterLength = formatted.length;
            let lengthDiff = afterLength - beforeLength;

            // Set cursor position
            e.target.setSelectionRange(
                cursorPosition + lengthDiff,
                cursorPosition + lengthDiff
            );
        });

        // Handle paste events
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            let pasteData = (e.clipboardData || window.clipboardData).getData('text');
            let formatted = formatNumber(pasteData);
            e.target.value = formatted;

            // Trigger input event to update any bindings
            e.target.dispatchEvent(new Event('input'));
        });
    }

    // Auto-apply formatting to amount inputs
    function initializeAmountInputs() {
        const amountInputs = document.querySelectorAll('input[name*="amount"]');
        amountInputs.forEach(handleAmountInput);
    }

    // Initialize on load
    initializeAmountInputs();

    // Re-initialize when new repeater items are added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const newAmountInputs = node.querySelectorAll('input[name*="amount"]');
                        newAmountInputs.forEach(handleAmountInput);
                    }
                });
            }
        });
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Balance check helper
    window.checkJournalBalance = function() {
        const debitInputs = document.querySelectorAll('select[name*="account_type"][value="debit"]');
        const creditInputs = document.querySelectorAll('select[name*="account_type"][value="credit"]');

        let totalDebit = 0;
        let totalCredit = 0;

        debitInputs.forEach(function(select) {
            const container = select.closest('[data-repeater-item]');
            const amountInput = container?.querySelector('input[name*="amount"]');
            if (amountInput) {
                totalDebit += cleanNumber(amountInput.value);
            }
        });

        creditInputs.forEach(function(select) {
            const container = select.closest('[data-repeater-item]');
            const amountInput = container?.querySelector('input[name*="amount"]');
            if (amountInput) {
                totalCredit += cleanNumber(amountInput.value);
            }
        });

        return {
            debit: totalDebit,
            credit: totalCredit,
            balanced: Math.abs(totalDebit - totalCredit) < 1
        };
    };
});
