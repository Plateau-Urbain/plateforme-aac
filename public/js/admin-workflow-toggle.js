/**
 * Sonata SpaceAdmin: toggle mono-site / multi-sites fields.
 * Must use jQuery events because Sonata Select2 does not reliably fire native "change".
 */
(function () {
    'use strict';

    var initialized = false;

    function findWorkflowSelect() {
        return document.querySelector('select[name$="[workflowType]"]');
    }

    function findLocationsBox() {
        // Sonata wraps each field in #sonata-ba-field-container-{id}
        var container = document.querySelector('[id^="sonata-ba-field-container-"][id$="_locations"]');
        if (container) {
            return container.closest('.box') || container;
        }

        // Fallback: box titled "Secteurs / Lieux…"
        var titles = document.querySelectorAll('.box .box-title');
        for (var i = 0; i < titles.length; i++) {
            if ((titles[i].textContent || '').indexOf('Lieux') !== -1) {
                return titles[i].closest('.box');
            }
        }

        return null;
    }

    function findGeneralBox(select) {
        var group = select ? select.closest('.form-group') : null;
        return group ? group.closest('.box') : null;
    }

    function toggleWorkflowFields(select) {
        if (!select) {
            return;
        }

        var isMulti = select.value === 'multi_location';

        // 1. Locations box (Secteurs / Lieux)
        var locationsBox = findLocationsBox();
        if (locationsBox) {
            locationsBox.style.display = isMulti ? '' : 'none';
        }

        // 2. Mono-site fields — scoped to the General box to avoid hiding location fields
        var generalBox = findGeneralBox(select);
        var singleFields = ['zipCode', 'limitAvailability', 'nbSpaces', 'minSpace', 'maxSpace'];
        var scope = generalBox || document;

        singleFields.forEach(function (fieldName) {
            var field = scope.querySelector(
                '[name$="[' + fieldName + ']"], [id$="_' + fieldName + '"]'
            );
            var group = field ? field.closest('.form-group') : null;
            if (group) {
                group.style.display = isMulti ? 'none' : '';
            }
        });

        // 3. City label: Ville vs Commune ou territoire
        var cityField = scope.querySelector('[name$="[city]"], [id$="_city"]');
        var cityGroup = cityField ? cityField.closest('.form-group') : null;
        if (cityGroup) {
            var label = cityGroup.querySelector('label');
            if (label) {
                label.textContent = isMulti ? 'Commune ou territoire' : 'Ville';
            }
        }
    }

    function initWorkflowToggle() {
        var select = findWorkflowSelect();
        if (!select || initialized) {
            // Still re-apply visibility if Select2 re-rendered after first init
            if (select) {
                toggleWorkflowFields(select);
            }
            return;
        }
        initialized = true;

        var run = function () {
            toggleWorkflowFields(select);
        };

        // Select2 (Sonata) triggers jQuery "change" / "select2:select", not always native change
        if (typeof jQuery !== 'undefined') {
            jQuery(select)
                .off('.workflowToggle')
                .on('change.workflowToggle select2:select.workflowToggle select2:clear.workflowToggle', run);
        } else {
            select.addEventListener('change', run);
        }

        run();

        // Select2 may initialize after DOM ready — re-apply once more shortly after
        setTimeout(run, 100);
        setTimeout(run, 400);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWorkflowToggle);
    } else {
        initWorkflowToggle();
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(initWorkflowToggle);
    }
})();
