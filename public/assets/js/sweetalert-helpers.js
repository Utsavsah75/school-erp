/**
 * SweetAlert2 helper layer for School ERP.
 *
 * This is the single place that knows how to talk to SweetAlert2. Every
 * other script (app.js, inline view scripts) should call into the `SA`
 * object below instead of calling `Swal` directly, `alert()`, or
 * `confirm()`. Keeping it centralized means the whole app gets a
 * consistent theme, timing, and behaviour, and it can be changed in one
 * place.
 *
 * Requires SweetAlert2 (https://sweetalert2.github.io/) to be loaded
 * before this file.
 */
(function (window, document) {
    'use strict';

    if (typeof window.Swal === 'undefined') {
        // SweetAlert2 didn't load (offline CDN, blocked script, etc). Fail
        // soft: define no-op-ish fallbacks so the rest of the app doesn't
        // throw, and native dialogs still work for anything that hasn't
        // been converted.
        console.warn('[SweetAlert2] Swal is not defined — the SweetAlert2 CDN script did not load. ' +
            'Falling back to native browser confirm()/alert(). Check your network/console for a blocked ' +
            'or 404 request to cdn.jsdelivr.net/npm/sweetalert2@11.');
        window.SA = {
            toast: function () {},
            fire: function () { return Promise.resolve({ isConfirmed: true }); },
            success: function () {},
            error: function () {},
            warning: function () {},
            info: function () {},
            question: function () {},
            loading: function () {},
            close: function () {},
            confirmDelete: function () { return Promise.resolve(window.confirm('Are you sure?')); },
            confirmAction: function () { return Promise.resolve(window.confirm('Are you sure?')); },
        };
        return;
    }
    console.log('[SweetAlert2] sweetalert-helpers.js loaded OK. Try SA.success("Test","It works!") in this console to verify.');

    // ---------------------------------------------------------------
    // Theme constants — keep every alert visually consistent.
    // ---------------------------------------------------------------
    var COLORS = {
        primary: '#4e73df',
        success: '#1cc88a',
        danger: '#e74a3b',
        warning: '#f6c23e',
        info: '#36b9cc',
        gray: '#858796',
    };

    var BASE = {
        customClass: {
            popup: 'sa-popup',
            confirmButton: 'btn btn-sa-confirm',
            cancelButton: 'btn btn-sa-cancel',
            denyButton: 'btn btn-sa-deny',
        },
        buttonsStyling: false,
        reverseButtons: true,
        heightAuto: false,
        showClass: { popup: 'swal2-show sa-animate-in' },
        hideClass: { popup: 'swal2-hide sa-animate-out' },
    };

    /** Merge N plain objects into a new one (shallow). */
    function merge() {
        var out = {};
        for (var i = 0; i < arguments.length; i++) {
            var src = arguments[i] || {};
            for (var k in src) {
                if (Object.prototype.hasOwnProperty.call(src, k)) {
                    out[k] = src[k];
                }
            }
        }
        return out;
    }

    // ---------------------------------------------------------------
    // Core primitives
    // ---------------------------------------------------------------

    /** Fire an arbitrary SweetAlert2 modal with the house style pre-merged in. */
    function fire(options) {
        return Swal.fire(merge(BASE, options));
    }

    /**
     * Top-end toast. Auto-closes in 5s, shows a progress bar, pauses
     * on hover — matches every "toast notification" requirement in the
     * spec (save/update/login/logout/copy/download).
     */
    function toast(icon, title, opts) {
        return Swal.fire(merge({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            didOpen: function (el) {
                el.addEventListener('mouseenter', Swal.stopTimer);
                el.addEventListener('mouseleave', Swal.resumeTimer);
            },
            customClass: { popup: 'sa-toast' },
        }, opts));
    }

    /** Full modal success. Icon: success, Title: Success!, Text: Action completed successfully. */
    function success(title, text, opts) {
        return fire(merge({
            icon: 'success',
            title: title || 'Success!',
            text: text || 'Action completed successfully.',
            confirmButtonText: 'OK',
            iconColor: COLORS.success,
        }, opts));
    }

    /** Full modal error. Icon: error, Title: Error!, Text: Something went wrong. */
    function error(title, text, opts) {
        return fire(merge({
            icon: 'error',
            title: title || 'Error!',
            text: text || 'Something went wrong. Please try again.',
            confirmButtonText: 'OK',
            iconColor: COLORS.danger,
        }, opts));
    }

    /** Full modal warning, e.g. "Required fields are missing." */
    function warning(title, text, opts) {
        return fire(merge({
            icon: 'warning',
            title: title || 'Warning',
            text: text,
            confirmButtonText: 'OK',
            iconColor: COLORS.warning,
        }, opts));
    }

    function info(title, text, opts) {
        return fire(merge({
            icon: 'info',
            title: title || 'Information',
            text: text,
            confirmButtonText: 'OK',
            iconColor: COLORS.info,
        }, opts));
    }

    function question(title, text, opts) {
        return fire(merge({
            icon: 'question',
            title: title,
            text: text,
            iconColor: COLORS.primary,
        }, opts));
    }

    /** Loading / processing spinner. Call SA.close() (or fire a new alert) when the work is done. */
    function loading(title) {
        return fire({
            title: title || 'Processing...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            },
        });
    }

    function close() {
        Swal.close();
    }

    // ---------------------------------------------------------------
    // Confirmations
    // ---------------------------------------------------------------

    /**
     * The standard destructive-action confirmation used throughout the
     * spec: "Are you sure?" / "This action cannot be undone." with a red
     * Delete button and a gray Cancel button.
     */
    function confirmDelete(opts) {
        opts = opts || {};
        return fire({
            icon: 'warning',
            iconColor: COLORS.danger,
            title: opts.title || 'Are you sure?',
            text: opts.text || 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: opts.confirmButtonText || '<i class="bi bi-trash-fill me-1"></i> Delete',
            cancelButtonText: opts.cancelButtonText || 'Cancel',
            customClass: merge(BASE.customClass, {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary',
            }),
        }).then(function (result) {
            return !!result.isConfirmed;
        });
    }

    /** Generic (non-destructive) confirmation, e.g. logout, publish, restore, promote. */
    function confirmAction(opts) {
        opts = opts || {};
        return fire({
            icon: opts.icon || 'question',
            iconColor: opts.iconColor || COLORS.primary,
            title: opts.title || 'Are you sure?',
            text: opts.text || '',
            showCancelButton: true,
            confirmButtonText: opts.confirmButtonText || 'Yes, continue',
            cancelButtonText: opts.cancelButtonText || 'Cancel',
            customClass: merge(BASE.customClass, {
                confirmButton: 'btn btn-' + (opts.confirmVariant || 'primary'),
                cancelButton: 'btn btn-secondary',
            }),
        }).then(function (result) {
            return !!result.isConfirmed;
        });
    }

    // ---------------------------------------------------------------
    // DOM wiring: forms/buttons opt in via data attributes so no page
    // needs its own JS to get a confirmation dialog.
    //
    //   <form class="confirm-delete" data-confirm-message="Delete this?">
    //   <form data-confirm data-confirm-title="Log out?" data-confirm-icon="question"
    //         data-confirm-message="..." data-confirm-variant="primary"
    //         data-confirm-button="Yes, log out">
    // ---------------------------------------------------------------

    function isSubmitting(form) {
        return form.getAttribute('data-sa-submitting') === '1';
    }
    function markSubmitting(form) {
        form.setAttribute('data-sa-submitting', '1');
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        var isDelete = form.classList.contains('confirm-delete');
        var isGeneric = form.hasAttribute('data-confirm');
        if (!isDelete && !isGeneric) {
            return;
        }
        if (isSubmitting(form)) {
            // Already confirmed once — let it through.
            return;
        }
        e.preventDefault();

        var promise = isDelete
            ? confirmDelete({
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmMessage || 'This action cannot be undone.',
            })
            : confirmAction({
                icon: form.dataset.confirmIcon || 'question',
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmMessage || '',
                confirmButtonText: form.dataset.confirmButton || 'Yes, continue',
                confirmVariant: form.dataset.confirmVariant || 'primary',
            });

        promise.then(function (confirmed) {
            if (!confirmed) {
                return;
            }
            markSubmitting(form);
            var loadingText = form.dataset.loadingText;
            if (loadingText !== undefined) {
                loading(loadingText || 'Processing...');
            }
            // HTMLFormElement.submit() does not re-trigger the 'submit'
            // event, so this won't loop back into this handler.
            form.submit();
        });
    }, true);

    // Forms that just want a spinner while a real (non-AJAX) submit is
    // in flight — no confirmation needed, e.g. big imports/exports,
    // report generation, file uploads.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (!form.classList.contains('sa-loading-submit')) {
            return;
        }
        if (form.classList.contains('confirm-delete') || form.hasAttribute('data-confirm')) {
            return; // handled by the confirmation flow above, which also shows loading
        }
        loading(form.dataset.loadingText || 'Processing...');
    });

    // ---------------------------------------------------------------
    // Flash-message bridge: app.php's flash_alerts() helper prints the
    // queued server-side flash messages (success/error/warning/info) as
    // a small JSON script block instead of Bootstrap markup. Turn each
    // one into the appropriate SweetAlert2 toast or modal on load.
    // ---------------------------------------------------------------
    function initFlashMessages() {
        var node = document.getElementById('sa-flash-data');
        if (!node) {
            console.log('[SweetAlert2] No #sa-flash-data element on this page — no server flash message was queued (this is normal for pages you just navigated to without performing an action).');
            return;
        }
        var messages;
        try {
            messages = JSON.parse(node.textContent || '[]');
        } catch (err) {
            console.warn('[SweetAlert2] Found #sa-flash-data but could not parse its JSON:', err, node.textContent);
            return;
        }
        console.log('[SweetAlert2] Flash messages found on this page:', messages);
        messages.forEach(function (m, idx) {
            // Stagger multiple flashes slightly so toasts don't stack
            // instantly on top of one another.
            setTimeout(function () {
                switch (m.type) {
                    case 'success':
                        toast('success', m.message);
                        break;
                    case 'info':
                        toast('info', m.message);
                        break;
                    case 'warning':
                        warning('Warning', m.message);
                        break;
                    case 'error':
                    default:
                        error('Error!', m.message);
                        break;
                }
            }, idx * 250);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlashMessages);
    } else {
        initFlashMessages();
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------
    window.SA = {
        fire: fire,
        toast: toast,
        success: success,
        error: error,
        warning: warning,
        info: info,
        question: question,
        loading: loading,
        close: close,
        confirmDelete: confirmDelete,
        confirmAction: confirmAction,

        // ---- Domain-specific convenience wrappers -------------------
        // Thin, named wrappers so call sites read as intent ("what is
        // happening") rather than raw icon/text plumbing. All of them
        // funnel through the primitives above, so there is exactly one
        // place that owns styling/timing.
        auth: {
            loginSuccess: function (name) { return toast('success', name ? ('Welcome back, ' + name + '!') : 'Login successful!'); },
            loginFailed: function (msg) { return error('Login Failed', msg || 'Invalid email or password.'); },
            invalidCredentials: function () { return error('Invalid Credentials', 'The email or password you entered is incorrect.'); },
            sessionExpired: function () { return warning('Session Expired', 'Your session has expired. Please log in again.'); },
            passwordChanged: function () { return success('Password Changed', 'Your password has been updated successfully.'); },
            forgotPasswordSent: function () { return success('Email Sent', 'If that account exists, a password reset link has been sent.'); },
            logoutConfirm: function () {
                return confirmAction({
                    icon: 'question',
                    title: 'Log out?',
                    text: 'You will need to sign in again to access your dashboard.',
                    confirmButtonText: 'Yes, log out',
                    confirmVariant: 'danger',
                });
            },
        },
        crud: {
            added: function (what) { return toast('success', (what || 'Record') + ' added successfully!'); },
            updated: function (what) { return toast('success', (what || 'Record') + ' updated successfully!'); },
            deleted: function (what) { return toast('success', (what || 'Record') + ' deleted successfully!'); },
            saved: function (what) { return toast('success', (what || 'Record') + ' saved successfully!'); },
            failed: function (what) { return error('Error!', 'Could not save ' + (what || 'this record') + '. Please try again.'); },
        },
        validation: {
            missingFields: function () { return warning('Warning', 'Required fields are missing.'); },
            invalidEmail: function () { return warning('Invalid Email', 'Please enter a valid email address.'); },
            invalidPhone: function () { return warning('Invalid Phone Number', 'Please enter a valid phone number.'); },
            duplicate: function (what) { return warning('Duplicate Record', (what || 'This record') + ' already exists.'); },
            invalidIsbn: function () { return warning('Invalid ISBN', 'Please enter a valid ISBN.'); },
            invalidDate: function () { return warning('Invalid Date', 'Please check the dates you entered.'); },
        },
        fileUpload: {
            started: function () { return loading('Uploading...'); },
            success: function () { close(); return toast('success', 'File uploaded successfully!'); },
            failed: function (msg) { close(); return error('Upload Failed', msg || 'Could not upload the file. Please try again.'); },
            invalidType: function () { return warning('Invalid File Type', 'Please choose a supported file type.'); },
            tooLarge: function () { return warning('File Too Large', 'Please choose a smaller file.'); },
        },
        importExport: {
            importing: function () { return loading('Importing records...'); },
            exporting: function () { return loading('Exporting records...'); },
            importComplete: function (count) { close(); return success('Import Completed', count ? (count + ' record(s) imported successfully.') : 'Records imported successfully.'); },
            exportComplete: function () { close(); return toast('success', 'Export completed!'); },
            validationErrors: function (msg) { close(); return error('Validation Errors', msg || 'Some rows could not be imported. Please review and try again.'); },
        },
        payments: {
            processing: function () { return loading('Processing payment...'); },
            success: function () { close(); return success('Payment Successful', 'The payment has been recorded successfully.'); },
            failed: function (msg) { close(); return error('Payment Failed', msg || 'The payment could not be processed. Please try again.'); },
            receiptPrinted: function () { return toast('success', 'Receipt sent to printer!'); },
        },
        inventory: {
            lowStock: function (item) { return warning('Stock Low', (item || 'An item') + ' is running low on stock.'); },
            outOfStock: function (item) { return error('Out of Stock', (item || 'This item') + ' is out of stock.'); },
        },
        library: {
            issued: function () { return toast('success', 'Book issued successfully!'); },
            returned: function () { return toast('success', 'Book returned successfully!'); },
            renewed: function () { return toast('success', 'Book renewed successfully!'); },
            lost: function () { return warning('Book Marked as Lost', 'A fine may apply for this item.'); },
            fineGenerated: function (amount) { return info('Fine Generated', amount ? ('A fine of ' + amount + ' has been generated.') : 'A fine has been generated for this transaction.'); },
            reserved: function () { return toast('success', 'Book reserved successfully!'); },
            reservationCancelled: function () { return toast('info', 'Reservation cancelled.'); },
            isbnGenerated: function () { return toast('info', 'ISBN generated.'); },
        },
        clipboard: {
            copied: function () { return toast('success', 'Copied to clipboard!'); },
        },
        download: {
            complete: function () { return toast('success', 'Download complete!'); },
        },
        reports: {
            generating: function () { return loading('Generating report...'); },
            ready: function () { close(); return toast('success', 'Report generated!'); },
        },
    };
})(window, document);
