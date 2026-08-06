<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_library_dashboard')) ?></h5>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="text-muted small" id="library-dashboard-updated">Updated just now</span>
        <a href="<?= e(url('library/issue')) ?>" class="btn btn-primary btn-sm"><i
                class="bi bi-box-arrow-right me-1"></i>Issue Book</a>
        <a href="<?= e(url('library/books/create')) ?>" class="btn btn-outline-primary btn-sm"><i
                class="bi bi-plus-lg me-1"></i>Add Book</a>
    </div>
</div>

<?php include __DIR__ . '/_dashboard_body.php'; ?>

<style>
/* Print: only the section the "Print" button was clicked inside of. */
@media print {
    body>*:not(.printing-now) {
        display: none !important;
    }

    .printing-now,
    .printing-now * {
        visibility: visible !important;
    }

    .printing-now {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}

.stat-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, .08);
    transition: box-shadow .15s;
}

#library-dashboard-body.is-refreshing {
    opacity: .55;
    pointer-events: none;
    transition: opacity .15s;
}
</style>

<script>
(function() {
    var container = document.getElementById('library-dashboard-body');
    var updatedLabel = document.getElementById('library-dashboard-updated');
    var refreshUrl = '<?= e(url('library/dashboard-data')) ?>';
    var REFRESH_MS = 150000;

    function initDataTables(scope) {
        if (!(window.jQuery && jQuery.fn.DataTable)) {
            return;
        }
        jQuery(scope).find('.data-table').each(function() {
            if (jQuery.fn.DataTable.isDataTable(this)) {
                jQuery(this).DataTable().destroy();
            }
            jQuery(this).DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: []
            });
        });
    }

    function bindPrintButtons(scope) {
        scope.querySelectorAll('.btn-print-table').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var card = btn.closest('.card');
                if (!card) {
                    return;
                }
                document.querySelectorAll('.printing-now').forEach(function(el) {
                    el.classList.remove('printing-now');
                });
                card.classList.add('printing-now');
                window.print();
                setTimeout(function() {
                    card.classList.remove('printing-now');
                }, 500);
            });
        });
    }

    function bindPayFineForms(scope) {
        scope.querySelectorAll('.pay-fine-form').forEach(function(form) {
            form.addEventListener('submit', function() {
                // Server records the receipt; nothing else needed client-side.
            });
        });
    }

    function wireUp(scope) {
        initDataTables(scope);
        bindPrintButtons(scope);
        bindPayFineForms(scope);
    }

    wireUp(document);

    function refresh() {
        if (!container) {
            return;
        }
        container.classList.add('is-refreshing');
        fetch(refreshUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                return res.ok ? res.text() : Promise.reject();
            })
            .then(function(html) {
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                var fresh = wrapper.querySelector('#library-dashboard-body');
                if (fresh && container.parentNode) {
                    container.parentNode.replaceChild(fresh, container);
                    container = fresh;
                    wireUp(container);
                    if (updatedLabel) {
                        updatedLabel.textContent = 'Updated just now';
                    }
                }
            })
            .catch(function() {
                /* silent — keep showing the last good data */
            })
            .finally(function() {
                if (container) {
                    container.classList.remove('is-refreshing');
                }
            });
    }

    setInterval(refresh, REFRESH_MS);
})();
</script>