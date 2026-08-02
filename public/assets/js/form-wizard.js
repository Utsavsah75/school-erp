/**
 * Lightweight multi-step "wizard" controller for long Add/Edit forms
 * (Teacher, Student registration). Steps are shown/hidden with a CSS
 * class instead of being separate pages/tabs, so every field's value
 * survives moving back and forth — nothing is ever removed from the DOM
 * or reset, and the browser still submits the whole form in one POST.
 *
 * Markup contract, inside a <form data-wizard id="...">:
 *   - Optional progress indicators: elements with [data-wizard-indicator]
 *     (rendered in the same order as the steps below)
 *   - One wrapper element per step, in order: class="wizard-step"
 *   - Navigation buttons anywhere in the form:
 *       [data-wizard-prev]   - goes back one step, no validation
 *       [data-wizard-next]   - validates the current step, then advances
 *       [data-wizard-submit] - the real type="submit" button; only ever
 *                               shown on the last ("Review") step
 *
 * A step can request extra validation beyond native HTML5 constraints
 * (e.g. "at least one email of two optional fields") by setting
 * data-wizard-validate="myGlobalFunctionName" on the .wizard-step — the
 * named function receives the step element and must return true/false,
 * and is responsible for showing its own error message.
 */
window.FormWizard = (function () {
    'use strict';

    function init(form) {
        if (!form || form.__wizardInitialized) {
            return null;
        }
        form.__wizardInitialized = true;

        var steps = Array.prototype.slice.call(form.querySelectorAll('.wizard-step'));
        if (!steps.length) {
            return null;
        }

        var indicators = Array.prototype.slice.call(form.querySelectorAll('[data-wizard-indicator]'));
        var prevBtns = Array.prototype.slice.call(form.querySelectorAll('[data-wizard-prev]'));
        var nextBtns = Array.prototype.slice.call(form.querySelectorAll('[data-wizard-next]'));
        var submitBtns = Array.prototype.slice.call(form.querySelectorAll('[data-wizard-submit]'));
        var current = 0;

        function isVisible(el) {
            return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
        }

        function validateStep(idx) {
            var step = steps[idx];
            var fields = step.querySelectorAll('input, select, textarea');
            for (var i = 0; i < fields.length; i++) {
                var f = fields[i];
                if (f.disabled || f.type === 'hidden' || !isVisible(f)) {
                    continue;
                }
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return false;
                }
            }
            var customFn = step.getAttribute('data-wizard-validate');
            if (customFn && typeof window[customFn] === 'function') {
                return !!window[customFn](step);
            }
            return true;
        }

        function show(idx) {
            steps.forEach(function (s, i) { s.classList.toggle('d-none', i !== idx); });
            indicators.forEach(function (ind, i) {
                ind.classList.toggle('wizard-indicator-active', i === idx);
                ind.classList.toggle('wizard-indicator-done', i < idx);
            });
            var isLast = idx === steps.length - 1;
            prevBtns.forEach(function (b) { b.classList.toggle('d-none', idx === 0); });
            nextBtns.forEach(function (b) { b.classList.toggle('d-none', isLast); });
            submitBtns.forEach(function (b) { b.classList.toggle('d-none', !isLast); });
            current = idx;

            var scrollTarget = form.closest('.card-body') || form;
            if (scrollTarget.scrollIntoView) {
                scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            form.dispatchEvent(new CustomEvent('wizard:step', {
                detail: { index: idx, step: steps[idx], total: steps.length }
            }));
        }

        nextBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!validateStep(current)) {
                    return;
                }
                if (current < steps.length - 1) {
                    show(current + 1);
                }
            });
        });

        prevBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (current > 0) {
                    show(current - 1);
                }
            });
        });

        // Pressing Enter inside an early step should advance to the next
        // step (validated) instead of submitting the whole form early.
        form.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            var isLast = current === steps.length - 1;
            if (!isLast) {
                e.preventDefault();
                if (validateStep(current)) {
                    show(current + 1);
                }
            }
        });

        // Belt-and-braces: a stray submit button, or a programmatic
        // form.submit(), can never skip past validation/Review.
        form.addEventListener('submit', function (e) {
            if (current !== steps.length - 1) {
                e.preventDefault();
                show(current);
                return;
            }
            if (!validateStep(current)) {
                e.preventDefault();
            }
        });

        show(0);

        return { show: show, goTo: show, current: function () { return current; } };
    }

    return { init: init };
})();
