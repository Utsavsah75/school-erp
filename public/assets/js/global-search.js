/**
 * Topbar Global Search — searchable across every row/column the current
 * role has access to (Students, Teachers, Parents, Classes, Sections,
 * Subjects, Library Books, Payments, Fee Types, Notices).
 *
 * As the person types, this debounces and calls GET /search/live?q=... for
 * a grouped-by-category JSON payload and renders it as a keyboard-navigable
 * dropdown (same pattern as the Student Selector combobox in
 * app/Views/attendance/index.php). Pressing Enter with nothing selected, or
 * clicking "View all results", submits the form to the full /search page.
 */
(function () {
    'use strict';

    var form = document.getElementById('globalSearchForm');
    var input = document.getElementById('globalSearchInput');
    var box = document.getElementById('globalSearchResults');
    if (!form || !input || !box) {
        return;
    }

    var liveUrl = form.getAttribute('action').replace(/\/search(?:\/.*)?$/, '/search/live');
    var debounceTimer = null;
    var activeIndex = -1;
    var flatItems = []; // flattened [{url}, ...] in the same order they're rendered, for keyboard nav

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function openDropdown() {
        box.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        box.hidden = true;
        box.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        flatItems = [];
    }

    function render(data) {
        var categories = data.categories || [];
        flatItems = [];

        if (!categories.length) {
            box.innerHTML = '<div class="gs-empty">No matches for "' + escapeHtml(data.term) + '".</div>';
            openDropdown();
            return;
        }

        var html = '';
        categories.forEach(function (cat) {
            html += '<div class="gs-group-label"><i class="bi ' + escapeHtml(cat.icon) + ' me-1"></i>' + escapeHtml(cat.label) + '</div>';
            cat.results.forEach(function (r) {
                var idx = flatItems.length;
                flatItems.push(r);
                html += '<a href="' + escapeHtml(r.url) + '" class="gs-option" role="option" id="gs-opt-' + idx + '" data-idx="' + idx + '">'
                    + '<span class="gs-option-title">' + escapeHtml(r.title) + '</span>'
                    + (r.subtitle ? '<span class="gs-option-subtitle">' + escapeHtml(r.subtitle) + '</span>' : '')
                    + '</a>';
            });
        });
        html += '<a href="#" class="gs-view-all" id="gsViewAll">View all ' + data.total + ' result' + (data.total === 1 ? '' : 's') + '</a>';

        box.innerHTML = html;
        activeIndex = -1;
        openDropdown();

        var viewAll = document.getElementById('gsViewAll');
        if (viewAll) {
            viewAll.addEventListener('click', function (e) {
                e.preventDefault();
                form.submit();
            });
        }
    }

    function highlight() {
        box.querySelectorAll('.gs-option').forEach(function (el, i) {
            el.classList.toggle('gs-active', i === activeIndex);
            if (i === activeIndex) {
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    input.addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 2) {
            closeDropdown();
            return;
        }
        debounceTimer = setTimeout(function () {
            box.innerHTML = '<div class="gs-loading">Searching...</div>';
            openDropdown();
            fetch(liveUrl + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function () {
                    box.innerHTML = '<div class="gs-empty">Search failed. Try again.</div>';
                });
        }, 250);
    });

    input.addEventListener('keydown', function (e) {
        if (box.hidden || !flatItems.length) {
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, flatItems.length - 1);
            highlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlight();
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && flatItems[activeIndex]) {
                e.preventDefault();
                window.location.href = flatItems[activeIndex].url;
            }
            // else: let the form submit normally to the full results page.
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#globalSearchResults') && e.target !== input) {
            closeDropdown();
        }
    });
})();
