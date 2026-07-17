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

        if (/[^a-zA-Z0-9._-]/.test(file.name)) {
            return 'Le fichier "' + file.name + '" a un nom non valide. Utilisez uniquement des lettres sans accent, chiffres, points, tirets et underscores.';
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

            if (partialAddActions.indexOf(submitName) !== -1) {
                partialAddScrollTop = $(window).scrollTop();
                partialAddSectionId = submitButton.closest('.section').attr('id') || null;
            }

            var previewing = submitButton.attr('name') === 'appbundle_space[preview]';

            if (previewing) {
                var previewName = submitButton.attr('name');
                if (previewName) {
                    formData.append(previewName, submitButton.val() || '');
                }
            }

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
    }

    $('#addAttribute').on('click', function (e) {
        e.preventDefault();
        addForm($('table.tags'));
    });

    // Efface l'état d'erreur dès que l'utilisateur corrige un champ
    $(document).on('input change', '#js-form-space .has-error input, #js-form-space .has-error select, #js-form-space .has-error textarea', function () {
        var $group = $(this).closest('.has-error');
        if (!$group.length) {
            return;
        }

        if (this.type === 'file') {
            return;
        }

        if (this.value.trim() !== '') {
            $group.removeClass('has-error').find('.help-block').remove();
        }
    });

    $(document).on('tbwchange tbwpaste', 'textarea', function () {
        var $group = $(this).closest('.has-error');
        if ($group.length && $(this).trumbowyg('html').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() !== '') {
            $group.removeClass('has-error').find('.help-block').remove();
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
