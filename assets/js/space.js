$(document).ready(function () {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    var partialAddScrollTop = null;
    var partialAddSectionId = null;
    var partialAddActions = ['add_photo', 'add_visit', 'add_document'];

    function scrollToSection(sectionId, fallbackScrollTop) {
        if (sectionId) {
            var $section = $('#' + sectionId);
            if ($section.length) {
                $('html, body').scrollTop($section.offset().top - 80);
                return;
            }
        }

        if (fallbackScrollTop !== null) {
            $('html, body').scrollTop(fallbackScrollTop);
        }
    }

    function syncFormActionUrl() {
        var $form = $('#js-form-space form').first();
        if (!$form.length) {
            return;
        }

        var action = $form.attr('action') || '';
        if (!action) {
            return;
        }

        var nextPath = $('<a>').attr('href', action)[0].pathname;
        if (nextPath && window.location.pathname !== nextPath) {
            history.replaceState(null, '', action);
        }
    }
    function scrollToFirstError() {
        var $first = $('#js-form-space .has-error:visible').first();
        if ($first.length) {
            $('html, body').animate({ scrollTop: $first.offset().top - 100 }, 300);
            var $input = $first.find('input, select, textarea').filter(':visible').first();
            if ($input.length) {
                $input.one('focus focusin', function (e) {
                    e.stopPropagation();
                });
                $input.focus();
            }
            return true;
        }

        var $alert = $('#js-form-space .alert-danger:visible').first();
        if ($alert.length) {
            $('html, body').animate({ scrollTop: $alert.offset().top - 80 }, 300);
            return true;
        }

        return false;
    }

    function syncTrumbowygFields() {
        $('#js-form-space form textarea').each(function () {
            var $textarea = $(this);
            if ($textarea.data('trumbowyg')) {
                $textarea.val($textarea.trumbowyg('html'));
            }
        });
    }

    function submitPublishForm($btn) {
        var $form = $btn.closest('form');
        var buttonName = $btn.attr('name');

        $form.find('input.js-publish-flag').remove();

        if (buttonName) {
            $('<input>', {
                type: 'hidden',
                'class': 'js-publish-flag',
                name: buttonName,
                value: $btn.val() || ''
            }).appendTo($form);
        }

        $form.get(0).submit();
    }

    function initPublishValidation() {
        $(document)
            .off('click.publishvalidation', '#js-form-space .js-publish')
            .on('click.publishvalidation', '#js-form-space .js-publish', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var $btn = $(this);
                var message = $btn.attr('data-confirm');

                if (message && !window.confirm(message)) {
                    return false;
                }

                syncTrumbowygFields();
                submitPublishForm($btn);
                return false;
            });
    }

    function successAjax(data, saving) {
        $("#js-form-space").replaceWith($(data).find('#js-form-space'));
        initFormListener();
        initPublishValidation();
        initLinkListener();
        initFileValidation();
        $("select").attr("data-placeholder", "Sélectionnez une option");
        $("select").chosen();

        $.trumbowyg.svgPath = "/images/icons-trumbowyg.svg";
        $('textarea').trumbowyg({
            lang: 'fr',
            resetCss: true,
            removeformatPasted: true,
            autogrow: true
        });

        if (saving && $(data).find('.alert-success').length > 0 && $("#js-form-space .has-error").length < 1) {
            $.colorbox({ html: $('#saveBox').html().replace('%%savemsg%%', saving) });
        }

        var scrolledToError = scrollToFirstError();
        if (!scrolledToError) {
            scrollToSection(partialAddSectionId, partialAddScrollTop);
        }

        partialAddScrollTop = null;
        partialAddSectionId = null;
        syncFormActionUrl();
    }

    function getPhotoFileInputs() {
        return $('#js-form-space .space-form-add-row input[type="file"]');
    }

    function getPhotoFileValidationError(file) {
        var maxSize = 600 * 1024;

        if (/[\/\\]/.test(file.name)) {
            return 'Le fichier "' + file.name + '" a un nom non valide.';
        }

        if (file.size > maxSize) {
            return 'Le fichier "' + file.name + '" est trop volumineux (' + Math.round(file.size / 1024) + ' Ko). Taille maximale autorisée : 600 Ko.';
        }

        return '';
    }

    function showPhotoFileValidationError(message) {
        var errorDiv = $('#file-validation-errors');
        var errorSpan = $('#file-error-message');

        if (!errorDiv.length) {
            return;
        }

        if (!message) {
            errorSpan.text('');
            errorDiv.hide();
            return;
        }

        errorSpan.text(message);
        errorDiv.show();
    }

    function initLinkListener() {
        $('.js-btn-space').off('click.spacelink').on('click.spacelink', function () {
            var href = $(this).attr('href');
            var method = $(this).data('link-method') || 'post';

            $.ajax({
                url: href,
                type: method,
                async: false,
                success: function (data) {
                    successAjax(data);
                },
                cache: false,
                contentType: false,
                processData: false
            });

            return false;
        });
    }

    function initFormListener() {
        $('button[type="submit"]:not(.no-ajax), input[type="submit"]:not(.no-ajax)')
            .off('click.formlistener')
            .on('click.formlistener', function (e) {
            var hasError = false;
            var errorMessage = '';

            getPhotoFileInputs().each(function () {
                var files = this.files;
                for (var i = 0; i < files.length; i++) {
                    var fileError = getPhotoFileValidationError(files[i]);
                    if (fileError) {
                        errorMessage = fileError;
                        hasError = true;
                        break;
                    }
                }
            });

            if (hasError) {
                showPhotoFileValidationError(errorMessage);
                e.preventDefault();
                return false;
            }

            showPhotoFileValidationError('');

            var saving = false;

            if ($(this).hasClass('save')) {
                saving = $(this).data('save') ? $(this).data('save') : true;
            }

            var form = $(this).closest('form');
            var action = form.attr('action');

            syncTrumbowygFields();

            var formData = new FormData(form[0]);
            var submitButton = $(this);
            var submitName = submitButton.attr('name');
            var submitValue = submitButton.val();

            if (submitValue === undefined || submitValue === null || submitValue === '') {
                submitValue = submitButton.text().trim() || '1';
            }

            // FormData(form) does not include the clicked submit control when we intercept click.
            if (submitName) {
                formData.set(submitName, submitValue);
            }

            if (partialAddActions.indexOf(submitName) !== -1) {
                partialAddScrollTop = $(window).scrollTop();
                partialAddSectionId = submitButton.closest('.section').attr('id') || null;
            }

            var previewing = submitName === 'appbundle_space[preview]';

            $.ajax({
                url: action,
                type: 'POST',
                data: formData,
                async: false,
                success: function (data, textStatus, jqXHR) {
                    if (previewing) {
                        var url = (typeof data === 'string' ? data : '').trim();
                        var contentType = jqXHR.getResponseHeader('Content-Type') || '';

                        if (contentType.indexOf('text/plain') !== -1 && url.charAt(0) === '/') {
                            window.open(url);
                            return;
                        }

                        var editMatch = action.match(/\/editer\/(\d+)/);
                        if (editMatch) {
                            window.open('/espace-manager/previsualiser/' + editMatch[1]);
                            return;
                        }

                        if (typeof data === 'string' && data.indexOf('<') === -1 && data.length > 0) {
                            alert(data);
                        } else {
                            successAjax(data, false);
                            alert('Impossible d\'ouvrir la prévisualisation. Vérifiez les champs du formulaire.');
                        }
                        return;
                    }

                    successAjax(data, saving);
                },
                cache: false,
                contentType: false,
                processData: false
            });

            return false;
        });
    }

    function initFileValidation() {
        $(document)
            .off('change.photofilevalidation', '#js-form-space .space-form-add-row input[type="file"]')
            .on('change.photofilevalidation', '#js-form-space .space-form-add-row input[type="file"]', function () {
            var files = this.files;
            var errorMessage = '';

            for (var i = 0; i < files.length; i++) {
                errorMessage = getPhotoFileValidationError(files[i]);
                if (errorMessage) {
                    break;
                }
            }

            if (errorMessage) {
                showPhotoFileValidationError(errorMessage);
                $(this).val('');
                return;
            }

            showPhotoFileValidationError('');
        });

        // Efface l'erreur serveur de document dès qu'un fichier est sélectionné dans .js-doc-upload
        $(document)
            .off('change.docfilevalidation', '#js-form-space .js-doc-upload input[type="file"]')
            .on('change.docfilevalidation', '#js-form-space .js-doc-upload input[type="file"]', function () {
                if (this.files.length > 0) {
                    $('#js-form-space .space-doc-error').remove();
                    dismissStalePublishFlash();
                }
            });
    }

    $('#addAttribute').on('click', function (e) {
        e.preventDefault();
        addForm($('table.tags'));
    });

    function dismissStalePublishFlash() {
        $('.alert-danger').not('#file-validation-errors').each(function () {
            var text = $(this).text();
            if (text.indexOf('Publication impossible') !== -1 || text.indexOf('n\'a pas pu être publié') !== -1) {
                $(this).remove();
            }
        });
    }

    function clearPriceRowError($priceRow) {
        $priceRow.removeClass('has-error');
        $priceRow.find('.form-group').removeClass('has-error');
        $priceRow.find('.space-price-row__errors').remove();
        $priceRow.find('span.help-block').remove();
        dismissStalePublishFlash();
    }

    function isPriceRowSatisfied($priceRow) {
        var filled = false;
        $priceRow.find('input, textarea').each(function () {
            if (this.type === 'file') {
                return;
            }
            if (($(this).val() || '').trim() !== '') {
                filled = true;
            }
        });
        return filled;
    }

    // Efface l'état d'erreur dès que l'utilisateur corrige un champ
    $(document).on('input change', '#js-form-space .has-error input, #js-form-space .has-error select, #js-form-space .has-error textarea', function () {
        if (this.type === 'file') {
            return;
        }

        var $priceRow = $(this).closest('.space-price-row');
        if ($priceRow.length) {
            if (isPriceRowSatisfied($priceRow)) {
                clearPriceRowError($priceRow);
            }
            return;
        }

        var $group = $(this).closest('.has-error');
        if (!$group.length) {
            return;
        }

        if (this.value.trim() !== '') {
            $group.removeClass('has-error').find('span.help-block').remove();
            dismissStalePublishFlash();
        }
    });

    // Efface le has-error-location du panneau de site dès que tous ses champs en erreur sont corrigés
    $(document).on('input change', '#js-form-space .location-item input, #js-form-space .location-item select, #js-form-space .location-item textarea', function () {
        if (this.type === 'file') {
            return;
        }
        var $panel = $(this).closest('.location-item.has-error-location');
        if (!$panel.length) {
            return;
        }
        setTimeout(function () {
            if (!$panel.find('.has-error').length) {
                $panel.removeClass('has-error-location');
                if (!$('#js-form-space .has-error-location').length) {
                    $('#js-form-space .space-locations-error').remove();
                    dismissStalePublishFlash();
                }
            }
        }, 0);
    });

    $(document).on('tbwchange tbwpaste', 'textarea', function () {
        var $group = $(this).closest('.has-error');
        if ($group.length && $(this).trumbowyg('html').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() !== '') {
            $group.removeClass('has-error').find('span.help-block').remove();
            dismissStalePublishFlash();
        }
    });

    initFormListener();
    initPublishValidation();
    initLinkListener();
    initFileValidation();

    $.trumbowyg.svgPath = "/images/icons-trumbowyg.svg";
    $('textarea').trumbowyg({
        lang: 'fr',
        resetCss: true,
        removeformatPasted: true,
        autogrow: true
    });

    scrollToFirstError();
    $(window).on('load', function () {
        if (window.location.hash) {
            scrollToSection(window.location.hash.replace('#', ''), null);
            return;
        }

        scrollToFirstError();
    });
});
