/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function() {
    $(".inline").colorbox({inline:true, width:"400px"});

    $(".inline_register").colorbox({inline:true, width:"400px"});
});

//$(function () {
//    if ($.fn.datepicker) {
//        $('[data-provide="datepicker"]').datepicker({
//            'language': 'fr'
//        });
//    }
//});

/**
 * Fake put/delete/post deletes on links
 */
$(function () {
    var createLinkMethodForm = function (action, data) {
        var $form = $('<form action="' + action + '" method="POST"></form>');
        for (var input in data) {
            if (data.hasOwnProperty(input)) {
                $form.append('<input name="' + input + '" value="' + data[input] + '">');
            }
        }

        return $form;
    };

    // Faking method
    $(document).on('click', '[data-link-method]', function (e) {
        e.preventDefault();
        var $element = $(this);

        var $form = createLinkMethodForm($element.attr('href'), {
            _method: $element.data('link-method')
        }).hide();

        $('body').append($form); // Firefox requires form to be on the page to allow submission
        $form.submit();
    });
});

$(function () {
    $('[data-checkbox-toggle]').each(function () {
        var $element = $(this);
        var selector = $element.data('checkbox-toggle');
        var $target = $(selector);

        if ($element.is(':checked')) {
            $target.show();
        } else {
            $target.hide();
        }

        $element.on('change', function () {
            if ($(this).is(':checked')) {
                $target.show();
            } else {
                $target.hide();
            }
        });
    });
});

$(function () {
    var recalculate_size_price = function ($container, basePrice) {
        var $input = $container.find('input');
        var $monthPrice = $container.find('[data-model="monthPrice"]');
        var $yearPrice = $container.find('[data-model="yearPrice"]');
        var value = parseInt($input.val());

        if (value > 0) {
            $monthPrice.text(value * basePrice);
            $yearPrice.text(value * basePrice * 12);
        } else {
            $monthPrice.text('-');
            $yearPrice.text('-');
        }
    };

    $('[data-size-calculator]').each(function () {
        var $element = $(this);
        var basePrice = $element.data('size-calculator');

        recalculate_size_price($element, basePrice);

        $element.on('change', 'input', function (e) {
           e.preventDefault();
           recalculate_size_price($element, basePrice);
        });
    });
});



$(function(){
  var sizePhotoListItem = function() {
    var $images = $('.photo-list .photo-item .image');
    $images.each(function(){
      $(this).css({height: $(this).width() * 0.75});
    });
  };
  $(window).on('resize', sizePhotoListItem);
  sizePhotoListItem();
});

// $(function(){
//   $('#appbundle_application_save, #appbundle_application_save_file').click(function(e){
//     e.preventDefault();
//     // $(this).parents('form').attr('no-validate', 'true').submit();
//   });
// });

$(function(){
  $(document).on('focus', '.form-group.has-error input', function(){
    $(this).parents('.form-group.has-error').removeClass('has-error').find('.help-block').detach();
  });
});

$(function() {
    function addPasswordToggle(input) {
        var $passwordInput = $(input);
        if ($passwordInput.data('password-toggle-added')) {
            return;
        }
        $passwordInput.data('password-toggle-added', true);
        
        var $parent = $passwordInput.parent();
        if ($parent.length) {
            if ($parent.css('position') === 'static') {
                $parent.css('position', 'relative');
            }
            
            // Adjust padding-right to prevent text overlap with the eye icon
            $passwordInput.css('padding-right', '35px');
            
            // Calculate right offset based on parent padding-right (e.g. if parent is a Bootstrap column)
            var paddingRight = parseFloat($parent.css('padding-right')) || 0;
            var rightOffset = 12 + paddingRight;
            
            var $toggleBtn = $('<span class="password-toggle" style="position: absolute; right: ' + rightOffset + 'px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10; color: #777;"><i class="fa-solid fa-eye"></i></span>');
            $parent.append($toggleBtn);
            
            $toggleBtn.on('click', function(e) {
                e.preventDefault();
                var $icon = $toggleBtn.find('i');
                if ($passwordInput.attr('type') === 'password') {
                    $passwordInput.attr('type', 'text');
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $passwordInput.attr('type', 'password');
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        }
    }

    // Initialize on existing password fields
    $('input[type="password"]').each(function() {
        addPasswordToggle(this);
    });

    // Observe body for dynamically added inputs (e.g. Colorbox modal/Ajax templates)
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if ($(node).is('input[type="password"]')) {
                            addPasswordToggle(node);
                        } else {
                            $(node).find('input[type="password"]').each(function() {
                                addPasswordToggle(this);
                            });
                        }
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
});

