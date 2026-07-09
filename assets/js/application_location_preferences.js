/**
 * Classement des lieux sur le formulaire de candidature AAC multi-lieux.
 */
(function ($) {
    'use strict';

    function renumberLocationPreferences() {
        $('#location-preferences-list .location-preference-item:visible').each(function (idx) {
            var rank = idx + 1;
            $(this).find('.js-location-preference-rank').val(rank);
            $(this).find('.js-preference-rank-label').text(rank);
        });
    }

    function updateLocationPreferenceControls() {
        var $items = $('#location-preferences-list .location-preference-item:visible');

        $items.each(function (index) {
            var $item = $(this);
            var $up = $item.find('.js-move-preference-up');
            var $down = $item.find('.js-move-preference-down');
            var isFirst = index === 0;
            var isLast = index === $items.length - 1;

            $up.prop('disabled', isFirst).toggleClass('is-disabled', isFirst).attr('aria-disabled', isFirst ? 'true' : 'false');
            $down.prop('disabled', isLast).toggleClass('is-disabled', isLast).attr('aria-disabled', isLast ? 'true' : 'false');
        });
    }

    function flashReorderedItem($item) {
        $item.removeClass('is-reordered');
        if ($item.length && $item[0]) {
            void $item[0].offsetWidth;
        }
        $item.addClass('is-reordered');
        window.setTimeout(function () {
            $item.removeClass('is-reordered');
        }, 750);
    }

    function bindLocationPreferenceItem($item) {
        $item.find('.js-move-preference-up').off('click').on('click', function () {
            if ($(this).prop('disabled')) {
                return;
            }

            var $prev = $item.prev('.location-preference-item:visible');
            if ($prev.length) {
                $item.insertBefore($prev);
                renumberLocationPreferences();
                updateLocationPreferenceControls();
                flashReorderedItem($item);
            }
        });

        $item.find('.js-move-preference-down').off('click').on('click', function () {
            if ($(this).prop('disabled')) {
                return;
            }

            var $next = $item.next('.location-preference-item:visible');
            if ($next.length) {
                $item.insertAfter($next);
                renumberLocationPreferences();
                updateLocationPreferenceControls();
                flashReorderedItem($item);
            }
        });
    }

    function initApplicationLocationPreferences() {
        var $list = $('#location-preferences-list');
        if (!$list.length) {
            return;
        }

        $list.find('.location-preference-item').each(function () {
            bindLocationPreferenceItem($(this));
        });

        renumberLocationPreferences();
        updateLocationPreferenceControls();
    }

    $(document).ready(function () {
        initApplicationLocationPreferences();
    });

    window.initApplicationLocationPreferences = initApplicationLocationPreferences;
})(jQuery);
