(function($) {
    $.entwine('kapost', function($) {
        $(window).resize(function() {
            $('#Form_ConvertObjectForm').layout();
        });
        
        $('#Form_ConvertObjectForm').entwine({
            onadd: function(e) {
                $(this).layout();
            }
        });
        
        $('#Form_ConvertObjectForm_ConvertMode').entwine({
            onadd: function(e) {
                this.updateTreeLabel();
            },
            
            updateTreeLabel: function() {
                var selectedVal=$('#Form_ConvertObjectForm_ConvertMode input.radio:checked').val();
                
                if(selectedVal=='ReplacePage') {
                    $('#Form_ConvertObjectForm #TargetPageID label').text(ss.i18n._t('KapostAdmin.REPLACE_PAGE', '_Replace this page'));
                }else if(selectedVal=='NewPage') {
                    $('#Form_ConvertObjectForm #TargetPageID label').text(ss.i18n._t('KapostAdmin.USE_AS_PARENT', '_Use this page as the parent for the new page, leave empty for a top level page'));
                }
            }
        });
        
        $('#Form_ConvertObjectForm_ConvertMode input.radio').entwine({
            onchange: function(e) {
                $(this).closest('ul').updateTreeLabel();
            }
        });
    });
})(jQuery);