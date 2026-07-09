/**
 * Gestion des lieux pour les AAC multi-lieux : collection dynamique, géocodage, carte Leaflet.
 */
(function ($, window) {
    'use strict';

    var geocodeUrl = window.spaceLocationsConfig ? window.spaceLocationsConfig.geocodeUrl : '/api/geocode';
    var leafletLoaded = false;
    var leafletLoading = false;

    function loadLeaflet(callback) {
        if (typeof L !== 'undefined') {
            callback();
            return;
        }
        if (leafletLoading) {
            $(window).one('leaflet:ready', callback);
            return;
        }
        leafletLoading = true;

        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(css);

        var script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = function () {
            leafletLoading = false;
            leafletLoaded = true;
            $(window).trigger('leaflet:ready');
            callback();
        };
        document.head.appendChild(script);
    }

    function initLocationMap($item) {
        var $mapEl = $item.find('.js-location-map');
        if (!$mapEl.length || $mapEl.data('map-init')) {
            return;
        }

        loadLeaflet(function () {
            var lat = parseFloat($item.find('.js-location-lat').val()) || 48.8566;
            var lng = parseFloat($item.find('.js-location-lng').val()) || 2.3522;
            var zoom = $item.find('.js-location-lat').val() ? 15 : 6;

            var map = L.map($mapEl[0]).setView([lat, lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            marker.on('dragend', function () {
                var pos = marker.getLatLng();
                $item.find('.js-location-lat').val(pos.lat.toFixed(6));
                $item.find('.js-location-lng').val(pos.lng.toFixed(6));
            });

            $mapEl.data('map-init', true);
            $mapEl.data('leaflet-map', map);
            $mapEl.data('leaflet-marker', marker);

            setTimeout(function () { map.invalidateSize(); }, 200);
        });
    }

    function updateMapPosition($item, lat, lng) {
        var $mapEl = $item.find('.js-location-map');
        var map = $mapEl.data('leaflet-map');
        var marker = $mapEl.data('leaflet-marker');

        if (!map || !marker) {
            initLocationMap($item);
            setTimeout(function () { updateMapPosition($item, lat, lng); }, 500);
            return;
        }

        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], 15);
        $item.find('.js-location-lat').val(lat.toFixed(6));
        $item.find('.js-location-lng').val(lng.toFixed(6));
    }

    function geocodeLocation($item) {
        var address = $item.find('[id$="_address"]').val() || '';
        var zipCode = $item.find('.js-location-zip').val() || '';
        var city = $item.find('.js-location-city').val() || '';
        var $btn = $item.find('.js-geocode-location');

        $btn.prop('disabled', true).text('Recherche…');

        $.ajax({
            url: geocodeUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ address: address, zipCode: zipCode, city: city })
        }).done(function (data) {
            updateMapPosition($item, data.lat, data.lng);
        }).fail(function (xhr) {
            var msg = 'Adresse introuvable.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            window.alert(msg);
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-map-marker"></i> Géolocaliser');
        });
    }

    function renumberLocations() {
        $('#locations-collection .location-item:visible').each(function (idx) {
            $(this).find('.js-location-number').text(idx + 1);
            $(this).find('.js-location-order').val(idx);
        });
    }

    function toggleSuspensionMessage($item) {
        var suspended = $item.find('.js-location-suspended').is(':checked');
        $item.find('.js-suspension-message-row').toggle(suspended);
        $item.toggleClass('suspended-location', suspended);
    }

    function bindLocationItem($item) {
        initLocationMap($item);
        toggleSuspensionMessage($item);

        $item.find('.js-geocode-location').off('click').on('click', function () {
            geocodeLocation($item);
        });

        $item.find('.js-location-suspended').off('change').on('change', function () {
            toggleSuspensionMessage($item);
        });

        $item.find('.js-remove-location').off('click').on('click', function () {
            if (!window.confirm('Supprimer ce lieu ?')) {
                return;
            }
            var $checkbox = $item.find('input[type="checkbox"][name$="[_delete]"]');
            if ($checkbox.length) {
                $checkbox.prop('checked', true);
                $item.hide();
            } else {
                $item.remove();
            }
            renumberLocations();
            if ($('#locations-collection .location-item:visible').length === 0) {
                $('.js-no-locations-msg').show();
            }
        });
    }

    function addLocationItem() {
        var $collection = $('#locations-collection');
        if (!$collection.length) {
            return;
        }

        var prototype = $collection.data('prototype');
        var index = $collection.data('index');
        if (!prototype) {
            return;
        }

        var newForm = prototype.replace(/__name__/g, index).replace(/__number__/g, index + 1);
        $collection.data('index', index + 1);

        var $item = $('<div class="location-item panel panel-default"></div>').html(newForm);
        $collection.append($item);
        $('.js-no-locations-msg').hide();

        bindLocationItem($item);
        renumberLocations();
        $item.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initSpaceLocations() {
        if (!$('#locations-collection').length) {
            return;
        }

        $('#locations-collection .location-item').each(function () {
            bindLocationItem($(this));
        });

        renumberLocations();

        $('#js-add-location').off('click').on('click', function () {
            addLocationItem();
        });
    }

    $(document).ready(function () {
        initSpaceLocations();
    });

    window.initSpaceLocations = initSpaceLocations;
})(jQuery, window);
