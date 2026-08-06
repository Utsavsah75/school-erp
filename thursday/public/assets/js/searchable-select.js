/**
 * SearchableSelect — reusable, accessible, production-ready custom dropdown.
 *
 * Two ways to use it:
 *
 * 1) Progressive enhancement of an existing <select> (recommended in this
 *    codebase — keeps the original <select> in the DOM, hidden, so existing
 *    server-rendered <option data-*="..."> attributes, form submission, and
 *    any code that reads `select.value` all keep working unchanged):
 *
 *      <select id="bookSelect">
 *        <option value="">-- Select Book --</option>
 *        <option value="12" data-title="..." data-available="4">... </option>
 *      </select>
 *      <script>
 *        SearchableSelect.enhance('#bookSelect', {
 *          placeholder: 'Select',
 *          searchPlaceholder: 'Search',
 *          onChange: (value, option) => console.log(value, option?.dataset)
 *        });
 *      </script>
 *
 * 2) Fully programmatic, options-driven (matches a React-select-like API):
 *
 *      new SearchableSelect(document.getElementById('mount'), {
 *        options: [{ value: '1', label: 'HTML' }, { value: '2', label: 'CSS' }],
 *        value: null,
 *        placeholder: 'Select',
 *        disabled: false,
 *        onChange: (value, option) => {}
 *      });
 *
 * Accessibility: combobox/listbox ARIA pattern, full keyboard support
 * (↑/↓ navigate, Enter select, Esc close, typing filters), focus management,
 * click-outside-to-close. Rendering is virtualization-free but list nodes
 * are only (re)built on filter/open, and options beyond the viewport are
 * simply scrolled (native browser scrolling), so it stays smooth into the
 * thousands-of-options range.
 */
(function (global) {
    'use strict';

    let instanceCounter = 0;

    class SearchableSelect {
        /**
         * @param {HTMLElement} mount Element the widget renders into.
         * @param {Object} opts
         * @param {Array<{value:string,label:string,disabled?:boolean,data?:Object}>} opts.options
         * @param {string|null} [opts.value]
         * @param {string} [opts.placeholder]
         * @param {string} [opts.searchPlaceholder]
         * @param {boolean} [opts.disabled]
         * @param {(value:string|null, option:Object|null)=>void} [opts.onChange]
         * @param {HTMLSelectElement} [opts.sourceSelect] Internal: original <select> being enhanced.
         */
        constructor(mount, opts) {
            this.mount = mount;
            this.options = opts.options || [];
            this.value = opts.value ?? null;
            this.placeholder = opts.placeholder || 'Select';
            this.searchPlaceholder = opts.searchPlaceholder || 'Search';
            this.disabled = !!opts.disabled;
            this.onChange = typeof opts.onChange === 'function' ? opts.onChange : () => {};
            this.sourceSelect = opts.sourceSelect || null;
            this.noResultsText = opts.noResultsText || 'No results found';

            this.id = 'ss-' + (++instanceCounter);
            this.filtered = this.options;
            this.activeIndex = -1;
            this.isOpen = false;

            this._build();
            this._bind();
            this._renderValue();
        }

        // ---- public API ----------------------------------------------------

        setOptions(options) {
            this.options = options || [];
            this.filtered = this.options;
            this._renderList();
        }

        setValue(value, { silent = false } = {}) {
            this.value = value;
            this._renderValue();
            if (!silent) {
                const opt = this._findOption(value);
                this.onChange(this.value, opt);
            }
            if (this.sourceSelect) {
                this.sourceSelect.value = value ?? '';
                this.sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        setDisabled(disabled) {
            this.disabled = !!disabled;
            this.root.setAttribute('data-disabled', String(this.disabled));
            this.control.tabIndex = this.disabled ? -1 : 0;
            if (this.disabled) this.close();
        }

        setPlaceholder(text) {
            this.placeholder = text;
            this._renderValue();
        }

        open() {
            if (this.disabled || this.isOpen) return;
            this.isOpen = true;
            this.root.classList.add('is-open');
            this.searchInput.value = '';
            this.filtered = this.options;
            this._renderList();
            this.activeIndex = this.filtered.findIndex((o) => o.value === this.value);
            this._highlight(this.activeIndex);
            // Focus after paint so the open animation isn't janked by focus scroll.
            requestAnimationFrame(() => this.searchInput.focus());
            document.addEventListener('mousedown', this._onDocMouseDown, true);
        }

        close() {
            if (!this.isOpen) return;
            this.isOpen = false;
            this.root.classList.remove('is-open');
            document.removeEventListener('mousedown', this._onDocMouseDown, true);
        }

        // ---- internals -------------------------------------------------------

        _findOption(value) {
            return this.options.find((o) => String(o.value) === String(value)) || null;
        }

        _build() {
            const root = document.createElement('div');
            root.className = 'ss-select';
            root.setAttribute('data-disabled', String(this.disabled));

            root.innerHTML = `
                <div class="ss-control" role="combobox" aria-haspopup="listbox" aria-expanded="false"
                     aria-owns="${this.id}-list" tabindex="${this.disabled ? -1 : 0}">
                    <span class="ss-value is-placeholder"></span>
                    <button type="button" class="ss-clear d-none" aria-label="Clear selection">&times;</button>
                    <svg class="ss-caret" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.7"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="ss-panel">
                    <div class="ss-search-wrap">
                        <input type="text" class="ss-search" placeholder="${this._esc(this.searchPlaceholder)}"
                               role="searchbox" aria-label="${this._esc(this.searchPlaceholder)}" autocomplete="off">
                    </div>
                    <ul class="ss-list" id="${this.id}-list" role="listbox"></ul>
                </div>
            `;

            this.mount.appendChild(root);
            this.root = root;
            this.control = root.querySelector('.ss-control');
            this.valueEl = root.querySelector('.ss-value');
            this.clearBtn = root.querySelector('.ss-clear');
            this.searchInput = root.querySelector('.ss-search');
            this.listEl = root.querySelector('.ss-list');
        }

        _bind() {
            this._onDocMouseDown = (e) => {
                if (!this.root.contains(e.target)) this.close();
            };

            this.control.addEventListener('click', () => {
                if (this.disabled) return;
                this.isOpen ? this.close() : this.open();
            });

            this.control.addEventListener('keydown', (e) => {
                if (this.disabled) return;
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                    if (!this.isOpen) this.open();
                }
            });

            this.clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.setValue(null);
            });

            this.searchInput.addEventListener('input', () => {
                const term = this.searchInput.value.trim().toLowerCase();
                this.filtered = !term
                    ? this.options
                    : this.options.filter((o) => o.label.toLowerCase().includes(term));
                this.activeIndex = this.filtered.length ? 0 : -1;
                this._renderList();
            });

            this.searchInput.addEventListener('keydown', (e) => {
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        this._moveActive(1);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this._moveActive(-1);
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (this.activeIndex >= 0 && this.filtered[this.activeIndex]) {
                            this._select(this.filtered[this.activeIndex]);
                        }
                        break;
                    case 'Escape':
                        e.preventDefault();
                        this.close();
                        this.control.focus();
                        break;
                    case 'Tab':
                        this.close();
                        break;
                }
            });
        }

        _moveActive(delta) {
            if (!this.filtered.length) return;
            const enabled = this.filtered.map((o, i) => (o.disabled ? -1 : i)).filter((i) => i !== -1);
            if (!enabled.length) return;
            let pos = enabled.indexOf(this.activeIndex);
            pos = pos === -1 ? 0 : (pos + delta + enabled.length) % enabled.length;
            this.activeIndex = enabled[pos];
            this._highlight(this.activeIndex);
        }

        _highlight(index) {
            this.listEl.querySelectorAll('.ss-option').forEach((el, i) => {
                el.classList.toggle('is-active', i === index);
            });
            const activeEl = this.listEl.children[index];
            if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
        }

        _select(opt) {
            if (opt.disabled) return;
            this.setValue(opt.value);
            this.close();
            this.control.focus();
        }

        _renderList() {
            this.listEl.innerHTML = '';
            if (!this.filtered.length) {
                const empty = document.createElement('li');
                empty.className = 'ss-empty';
                empty.textContent = this.noResultsText;
                this.listEl.appendChild(empty);
                return;
            }
            this.filtered.forEach((opt, i) => {
                const li = document.createElement('li');
                li.className = 'ss-option' + (String(opt.value) === String(this.value) ? ' is-selected' : '');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', String(opt.value) === String(this.value));
                li.textContent = opt.label;
                li.addEventListener('mouseenter', () => {
                    this.activeIndex = i;
                    this._highlight(i);
                });
                li.addEventListener('click', () => this._select(opt));
                this.listEl.appendChild(li);
            });
        }

        _renderValue() {
            const opt = this._findOption(this.value);
            this.valueEl.textContent = opt ? opt.label : this.placeholder;
            this.valueEl.classList.toggle('is-placeholder', !opt);
            this.clearBtn.classList.toggle('d-none', !opt);
            this.control.setAttribute('aria-expanded', String(this.isOpen));
        }

        _esc(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }
    }

    /**
     * Progressively enhance an existing <select>. The original element is
     * kept in the DOM (visually hidden) so it stays the source of truth for
     * `.value` and any data-* attributes on its <option> elements — existing
     * code that reads those keeps working untouched.
     */
    SearchableSelect.enhance = function (selectOrSelector, opts = {}) {
        const select = typeof selectOrSelector === 'string'
            ? document.querySelector(selectOrSelector)
            : selectOrSelector;
        if (!select) return null;

        const options = Array.from(select.options)
            .filter((o) => o.value !== '')
            .map((o) => ({ value: o.value, label: o.textContent.trim(), dataset: o.dataset, el: o }));

        const mount = document.createElement('div');
        select.insertAdjacentElement('afterend', mount);
        select.classList.add('d-none');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;

        const instance = new SearchableSelect(mount, {
            options,
            value: select.value || null,
            placeholder: opts.placeholder || 'Select',
            searchPlaceholder: opts.searchPlaceholder || 'Search',
            disabled: select.disabled,
            noResultsText: opts.noResultsText,
            sourceSelect: select,
            onChange: (value, opt) => {
                if (typeof opts.onChange === 'function') opts.onChange(value, opt ? opt.el : null);
            },
        });

        // Keep the widget in sync if something else programmatically changes
        // the underlying <select> (e.g. a "reset form" call elsewhere).
        select.addEventListener('ss:sync', () => {
            instance.setValue(select.value || null, { silent: true });
        });

        return instance;
    };

    global.SearchableSelect = SearchableSelect;
})(window);
