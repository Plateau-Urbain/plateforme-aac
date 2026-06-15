$(document).ready(function () {
    $('#addAttribute').on('click', function (e) {
        // empêche le lien de créer un « # » dans l'URL
        e.preventDefault();

        // ajoute un nouveau formulaire tag (voir le prochain bloc de code)
        addForm($('table.tags'));
    });

    function successAjax(data, saving) {
        $("#js-form-space").replaceWith($(data).find('#js-form-space'));
        initFormListener();
        initLinkListener();
        initFileValidation();
        //$("input[data-provide='datepicker']").datepicker({'format' : 'dd/mm/yyyy', 'language': 'fr'});
        $("select").attr("data-placeholder", "Sélectionnez une option");
        $("select").chosen();

        // Réinitialiser l'éditeur Trumbowyg après mise à jour AJAX
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

        if ($(data).find('.alert-danger').length > 0) {
            $('html, body').animate({ scrollTop: $('.alert-danger').first().offset().top - 80 }, 300);
        }
    }

    function initLinkListener() {
        $('.js-btn-space').click(function () {

            var href = $(this).attr('href');
            var method = $(this).data('link-method');

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
        var name = document.getElementById('appbundle_space_name');

        $('button[type="submit"]:not(.no-ajax), input[type="submit"]:not(.no-ajax)').click(function (e) {
            if (name.checkValidity() == false) {
                window.scroll(name.scrollTop, name.scrollLeft)
                name.focus()
                name.parentElement.classList.add('has-error')
                e.preventDefault()
                return false
            }

            // Validation de la taille des fichiers (photos uniquement - 600 Ko)
            var maxSize = 600 * 1024; // 600 Ko en bytes
            // On exclut les inputs documents (.js-doc-upload) qui ont leur propre validation
            var fileInputs = $('input[type="file"]').not(function () {
                return $(this).closest('.js-doc-upload').length > 0;
            });
            var hasError = false;
            var errorMessage = '';
            var errorDiv = $('#file-validation-errors');
            var errorSpan = $('#file-error-message');

            // Masquer les erreurs précédentes
            errorDiv.hide();

            fileInputs.each(function () {
                var files = this.files;
                for (var i = 0; i < files.length; i++) {
                    if (files[i].size > maxSize) {
                        errorMessage += 'Le fichier "' + files[i].name + '" est trop volumineux (' + Math.round(files[i].size / 1024) + ' Ko). Taille maximale autorisée : 600 Ko.';
                        hasError = true;
                    }
                }
            });

            if (hasError) {
                errorSpan.text(errorMessage);
                errorDiv.show();
                e.preventDefault();
                return false;
            }

            var saving = false;

            if ($(this).hasClass('js-publish')) {
                return true;
            }

            if ($(this).hasClass('save')) {
                saving = $(this).data('save') ? $(this).data('save') : true;
            }

            var form = $(this).closest('form');
            var action = form.attr('action');

            form.find('textarea').each(function () {
                var $textarea = $(this);
                if ($textarea.data('trumbowyg')) {
                    $textarea.val($textarea.trumbowyg('html'));
                }
            });

            var formData = new FormData(form[0]);
            var submitButton = $(this);
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

    // Validation en temps réel lors de la sélection des fichiers (photos uniquement - 600 Ko)
    function initFileValidation() {
        // On exclut les inputs dans .js-doc-upload qui ont leur propre validation
        $('input[type="file"]').not(function () {
            return $(this).closest('.js-doc-upload').length > 0;
        }).on('change', function () {
            var maxSize = 600 * 1024; // 600 Ko en bytes
            var files = this.files;
            var errorMessage = '';
            var errorDiv = $('#file-validation-errors');
            var errorSpan = $('#file-error-message');

            // Masquer les erreurs précédentes
            errorDiv.hide();

            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxSize) {
                    errorMessage += 'Le fichier "' + files[i].name + '" est trop volumineux (' + Math.round(files[i].size / 1024) + ' Ko). Taille maximale autorisée : 600 Ko.';
                }
            }

            if (errorMessage) {
                errorSpan.text(errorMessage);
                errorDiv.show();
                // Vider le champ de fichier
                $(this).val('');
            }
        });
    }

    initFormListener();
    initLinkListener();
    initFileValidation();

    // Initialiser l'éditeur Trumbowyg au chargement de la page
    $.trumbowyg.svgPath = "/images/icons-trumbowyg.svg";
    $('textarea').trumbowyg({
        lang: 'fr',
        resetCss: true,
        removeformatPasted: true,
        autogrow: true
    });
});
