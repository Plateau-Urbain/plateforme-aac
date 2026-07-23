document.addEventListener('DOMContentLoaded', function() {
    function initWorkflowToggle() {
        var select = document.querySelector('select[name$="[workflowType]"]');
        if (!select) return;

        function toggleWorkflowFields() {
            var isMulti = select.value === 'multi_location';
            
            // 1. Locations Box (Secteurs / Lieux section)
            var locationsElem = document.querySelector('[id$="_locations"]');
            var locationsBox = locationsElem ? locationsElem.closest('.box') : null;
            if (!locationsBox && locationsElem) {
                locationsBox = locationsElem.closest('.form-group');
            }
            if (locationsBox) {
                locationsBox.style.display = isMulti ? '' : 'none';
            }

            // 2. Single-site specific fields (zipCode, limitAvailability, nbSpaces, minSpace, maxSpace)
            var singleFields = ['zipCode', 'limitAvailability', 'nbSpaces', 'minSpace', 'maxSpace'];
            singleFields.forEach(function(fieldName) {
                var field = document.querySelector('[name$="[' + fieldName + ']"], [id$="_' + fieldName + '"]');
                var group = field ? field.closest('.form-group') : null;
                if (group) {
                    group.style.display = isMulti ? 'none' : '';
                }
            });

            // 3. City label adjustment
            var cityField = document.querySelector('[name$="[city]"], [id$="_city"]');
            var cityGroup = cityField ? cityField.closest('.form-group') : null;
            if (cityGroup) {
                var label = cityGroup.querySelector('label');
                if (label) {
                    label.textContent = isMulti ? 'Commune ou territoire' : 'Ville';
                }
            }
        }

        select.addEventListener('change', toggleWorkflowFields);
        toggleWorkflowFields();
    }

    initWorkflowToggle();
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(initWorkflowToggle);
    }
});
