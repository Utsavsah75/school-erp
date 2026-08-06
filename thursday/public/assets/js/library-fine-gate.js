/**
 * Overdue Library Fine collection gate.
 *
 * Any <form class="fine-gate-form"> whose submit must first collect an
 * outstanding library fine gets intercepted here. The fine amount shown is
 * always the one the server rendered into data-fine-amount — it is never
 * editable, and the client never invents or recalculates it. The server
 * (LibraryController::returnBook()/renewBook()/payFine()) independently
 * recomputes the true fine from the loan's due date + the configured
 * fine-per-day rate before accepting anything, so this UI gate can only make
 * the flow *safer* to use — it is not itself the source of truth.
 *
 * Required form structure:
 *   <form class="fine-gate-form"
 *         data-fine-amount="123.45"
 *         data-days-overdue="7"
 *         data-fine-action-label="renew this book">
 *     <input type="hidden" name="payment_mode" value="">
 *     <input type="hidden" name="reference_number" value="">
 *   </form>
 */
(function () {
    'use strict';

    var PAYMENT_MODES = window.LIBRARY_PAYMENT_MODES || {};
    // Modes where a reference/transaction number is required — mirrors
    // LibraryController::FINE_MODES_REQUIRING_REFERENCE. Cash is the only
    // exception because there's nothing to reference.
    var MODES_REQUIRING_REFERENCE = ['upi', 'bank_transfer', 'online', 'cheque', 'card'];

    function currency(amount) {
        return (window.LIBRARY_CURRENCY_PREFIX || '') + Number(amount).toFixed(2);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target.closest ? e.target.closest('form.fine-gate-form') : null;
        if (!form) {
            return;
        }
        // Delegated on `document` (rather than bound per-form) specifically so
        // this still works after the Library Dashboard's 8-second AJAX
        // auto-refresh swaps in a brand-new #library-dashboard-body — those
        // freshly-injected Renew/Return/Collect-Fine forms are never
        // individually re-scanned, but a document-level submit listener
        // catches them regardless of when they were added to the DOM.
        if (form.getAttribute('data-fine-collected') === '1') {
            return; // already confirmed once — let it submit for real
        }

        var amount = parseFloat(form.dataset.fineAmount || '0') || 0;

        // Nothing owed — there is nothing to validate or collect, so the
        // gate has no reason to appear. Let the action through as-is,
        // exactly like a normal on-time renew/return.
        if (amount <= 0) {
            return;
        }

        e.preventDefault();

        var days = form.dataset.daysOverdue || '0';
        var actionLabel = form.dataset.fineActionLabel || 'continue';

        var modeOptions = Object.keys(PAYMENT_MODES).map(function (key) {
            return '<option value="' + key + '">' + PAYMENT_MODES[key] + '</option>';
        }).join('');

        var overdueLine = (parseInt(days, 10) > 0)
            ? 'This book is ' + days + ' day(s) overdue. '
            : '';

        (window.SA ? SA.fire({
            icon: 'warning',
            title: 'Collect Overdue Fine',
            html:
                '<p class="mb-3">' + overdueLine +
                'A fine of <strong id="fine-gate-amount-display">' + currency(amount) + '</strong> must be collected before you can ' + actionLabel + '.</p>' +
                '<div class="mb-2 text-start">' +
                '<label class="form-label small text-muted mb-1">Fine Amount (calculated automatically — not editable)</label>' +
                '<input type="text" class="form-control" value="' + currency(amount) + '" readonly disabled>' +
                '</div>' +
                '<div class="mb-2 text-start">' +
                '<label class="form-label small text-muted mb-1">Payment Mode</label>' +
                '<select id="fine-gate-mode" class="form-select">' + modeOptions + '</select>' +
                '</div>' +
                '<div class="text-start">' +
                '<label class="form-label small text-muted mb-1" id="fine-gate-reference-label">Reference No.</label>' +
                '<input id="fine-gate-reference" type="text" class="form-control" placeholder="Cheque/UPI/txn ref">' +
                '<div class="form-text text-danger d-none" id="fine-gate-reference-hint">A reference/transaction number is required for this payment mode.</div>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: 'Collect Fine & Continue',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            // Disabled until the currently-selected payment mode's requirements are met —
            // the person must actively enter valid payment info before this can be clicked.
            didOpen: function () {
                var modeEl = document.getElementById('fine-gate-mode');
                var refEl = document.getElementById('fine-gate-reference');
                var refLabel = document.getElementById('fine-gate-reference-label');
                var refHint = document.getElementById('fine-gate-reference-hint');
                var confirmBtn = window.Swal ? Swal.getConfirmButton() : null;

                function refRequired() {
                    return MODES_REQUIRING_REFERENCE.indexOf(modeEl.value) !== -1;
                }

                function revalidate() {
                    var required = refRequired();
                    refLabel.textContent = required ? 'Reference No. (required)' : 'Reference No. (optional)';
                    var valid = modeEl.value !== '' && (!required || refEl.value.trim() !== '');
                    if (confirmBtn) {
                        confirmBtn.disabled = !valid;
                    }
                    refHint.classList.toggle('d-none', !(required && refEl.value.trim() === '' && refEl.dataset.touched === '1'));
                }

                modeEl.addEventListener('change', revalidate);
                refEl.addEventListener('input', function () {
                    refEl.dataset.touched = '1';
                    revalidate();
                });
                revalidate();
            },
            preConfirm: function () {
                var mode = document.getElementById('fine-gate-mode').value;
                var reference = document.getElementById('fine-gate-reference').value.trim();
                if (!mode) {
                    Swal.showValidationMessage('Select a payment mode.');
                    return false;
                }
                if (MODES_REQUIRING_REFERENCE.indexOf(mode) !== -1 && reference === '') {
                    Swal.showValidationMessage('A reference/transaction number is required for this payment mode.');
                    return false;
                }
                return { mode: mode, reference: reference };
            },
        }) : Promise.resolve({ isConfirmed: true, value: { mode: 'cash', reference: '' } }))
            .then(function (result) {
                if (!result || !result.isConfirmed) {
                    return;
                }
                var value = result.value || { mode: 'cash', reference: '' };
                form.querySelector('input[name="payment_mode"]').value = value.mode;
                form.querySelector('input[name="reference_number"]').value = value.reference || '';
                form.setAttribute('data-fine-collected', '1');
                form.submit();
            });
    });
})();
