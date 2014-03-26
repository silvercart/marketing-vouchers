jQuery;
(function($) {
    $('#Form_EditForm_RestrictValueToProduct').live('click', function() {
        $('#Form_EditForm_RestrictValueToProduct_Copy').attr('checked', $(this).attr('checked'));
    });
    $('#Form_EditForm_RestrictValueToProduct_Copy').live('click', function() {
        $('#Form_EditForm_RestrictValueToProduct').attr('checked', $(this).attr('checked'));
    });
})(jQuery);