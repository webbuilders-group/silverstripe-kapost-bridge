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
                this.updateVisibleFields();
            },
            
            updateVisibleFields: function() {
                var selectedVal=$('#Form_ConvertObjectForm_ConvertMode input.radio:checked').val();
                
                if(selectedVal=='ReplacePage') {
                    $('#Form_ConvertObjectForm #ParentPageID').hide();
                    $('#Form_ConvertObjectForm #ReplacePageID').show();
                }else if(selectedVal=='NewPage') {
                    $('#Form_ConvertObjectForm #ParentPageID').show();
                    $('#Form_ConvertObjectForm #ReplacePageID').hide();
                }else {
                    $('#Form_ConvertObjectForm #ParentPageID, #Form_ConvertObjectForm #ReplacePageID').hide();
                }
            }
        });
        
        $('#Form_ConvertObjectForm_ConvertMode input.radio').entwine({
            onchange: function(e) {
                $(this).closest('ul').updateVisibleFields();
            }
        });
        
        $('#Form_ConvertObjectForm_action_doConvertObject').entwine({
            onclick: function() {
                $(document.body).append(
                                        '<div class="cms-content-loading-overlay ui-widget-overlay-light"></div>'+
                                        '<div class="cms-content-loading-spinner"></div>'
                                    );
            }
        });
    });
})(jQuery);