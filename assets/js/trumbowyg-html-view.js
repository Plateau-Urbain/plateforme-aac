/**
 * Trumbowyg : en mode « Voir le HTML », passe à la ligne après chaque bloc (paragraphe, titre, liste…).
 */
(function () {
    'use strict';

    var blockBoundary = /\s*(<\/(?:p|div|h[1-6]|ul|ol|li|blockquote|table|tr)>|<br\s*\/?>|<hr\s*\/?>|<(?:ul|ol)\b[^>]*>)\s*/gi;

    function formatHtml(html) {
        return html.replace(blockBoundary, '$1\n').trim();
    }

    // Trumbowyg stoppe la propagation du mousedown et bascule l'affichage dans un setTimeout :
    // on écoute en phase de capture et on attend la fin de la bascule.
    document.addEventListener('mousedown', function (e) {
        var button = e.target.closest ? e.target.closest('.trumbowyg-viewHTML-button') : null;
        if (!button) {
            return;
        }

        var box = button.closest('.trumbowyg-box');
        setTimeout(function () {
            setTimeout(function () {
                if (!box || !box.classList.contains('trumbowyg-editor-hidden')) {
                    return;
                }
                var textarea = box.querySelector('textarea.trumbowyg-textarea');
                if (textarea) {
                    textarea.value = formatHtml(textarea.value);
                    var minHeight = textarea.clientHeight;
                    textarea.style.flex = '0 0 auto';
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.max(textarea.scrollHeight, minHeight) + 'px';
                }
            }, 0);
        }, 0);
    }, true);
})();
