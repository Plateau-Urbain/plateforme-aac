/**
 * Classement des lieux sur le formulaire de candidature AAC multi-lieux.
 */
(function ($) {
    'use strict';

    function isExcludedItem($item) {
        return $item.hasClass('is-excluded') || $item.find('.js-location-preference-excluded').val() === '1';
    }

    function setExcludedState($item, excluded) {
        var $excludeBtn = $item.find('.js-exclude-location');
        var $restoreBtn = $item.find('.js-restore-location');
        var $badge = $item.find('.js-excluded-badge');
        var $rankLabel = $item.find('.js-preference-rank-label');

        $item.toggleClass('is-excluded', excluded);
        $item.find('.js-location-preference-excluded').val(excluded ? '1' : '0');

        if (excluded) {
            $item.find('.js-location-preference-rank').val('');
            $rankLabel.text('—').css({ background: '#94a3b8' });
            $excludeBtn.hide();
            $restoreBtn.show();
            if (!$badge.length) {
                $item.find('.location-preference-info > div').first().append(
                    '<span class="label label-default js-excluded-badge" style="font-size: 11px;">Ne m\'intéresse pas</span>'
                );
            }
        } else {
            $excludeBtn.show();
            $restoreBtn.hide();
            $badge.remove();
            $rankLabel.css({ background: '#0f172a' });
        }
    }

    function renumberLocationPreferences() {
        var rank = 0;
        $('#location-preferences-list .location-preference-item:visible').each(function () {
            var $item = $(this);
            if (isExcludedItem($item)) {
                $item.find('.js-location-preference-rank').val('');
                $item.find('.js-preference-rank-label').text('—');
                return;
            }

            rank += 1;
            $item.find('.js-location-preference-rank').val(rank);
            $item.find('.js-preference-rank-label').text(rank).css({ background: '#0f172a' });
        });
    }

    function updateLocationPreferenceControls() {
        var $items = $('#location-preferences-list .location-preference-item:visible:not(.is-excluded)');
        var $allVisible = $('#location-preferences-list .location-preference-item:visible');

        $allVisible.each(function () {
            var $item = $(this);
            if (isExcludedItem($item)) {
                $item.find('.js-move-preference-up, .js-move-preference-down')
                    .prop('disabled', true)
                    .toggleClass('is-disabled', true)
                    .attr('aria-disabled', 'true');
                return;
            }

            var index = $items.index($item);
            var isFirst = index === 0;
            var isLast = index === $items.length - 1;

            $item.find('.js-move-preference-up')
                .prop('disabled', isFirst)
                .toggleClass('is-disabled', isFirst)
                .attr('aria-disabled', isFirst ? 'true' : 'false');
            $item.find('.js-move-preference-down')
                .prop('disabled', isLast)
                .toggleClass('is-disabled', isLast)
                .attr('aria-disabled', isLast ? 'true' : 'false');
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

    function moveExcludedItemsToEnd() {
        var $list = $('#location-preferences-list');
        $list.find('.location-preference-item.is-excluded:visible').each(function () {
            $list.append($(this));
        });
    }

    function bindLocationPreferenceItem($item) {
        $item.find('.js-move-preference-up').off('click').on('click', function () {
            if ($(this).prop('disabled') || isExcludedItem($item)) {
                return;
            }

            var $prev = $item.prevAll('.location-preference-item:visible:not(.is-excluded)').first();
            if ($prev.length) {
                $item.insertBefore($prev);
                renumberLocationPreferences();
                updateLocationPreferenceControls();
                flashReorderedItem($item);
            }
        });

        $item.find('.js-move-preference-down').off('click').on('click', function () {
            if ($(this).prop('disabled') || isExcludedItem($item)) {
                return;
            }

            var $next = $item.nextAll('.location-preference-item:visible:not(.is-excluded)').first();
            if ($next.length) {
                $item.insertAfter($next);
                renumberLocationPreferences();
                updateLocationPreferenceControls();
                flashReorderedItem($item);
            }
        });

        $item.find('.js-exclude-location').off('click').on('click', function () {
            setExcludedState($item, true);
            $('#location-preferences-list').append($item);
            renumberLocationPreferences();
            updateLocationPreferenceControls();
        });

        $item.find('.js-restore-location').off('click').on('click', function () {
            setExcludedState($item, false);
            var $list = $('#location-preferences-list');
            var $firstExcluded = $list.find('.location-preference-item.is-excluded:visible').first();
            if ($firstExcluded.length && !$firstExcluded.is($item)) {
                $item.insertBefore($firstExcluded);
            } else {
                $list.append($item);
            }
            renumberLocationPreferences();
            updateLocationPreferenceControls();
            flashReorderedItem($item);
        });
    }

    function initApplicationLocationPreferences() {
        var $list = $('#location-preferences-list');
        if (!$list.length) {
            return;
        }

        $list.find('.location-preference-item').each(function () {
            var $item = $(this);
            if (isExcludedItem($item)) {
                setExcludedState($item, true);
            }
            bindLocationPreferenceItem($item);
        });

        moveExcludedItemsToEnd();
        renumberLocationPreferences();
        updateLocationPreferenceControls();
    }

    $(document).ready(function () {
        initApplicationLocationPreferences();
    });

    window.initApplicationLocationPreferences = initApplicationLocationPreferences;
    window.renumberLocationPreferences = renumberLocationPreferences;
})(jQuery);
