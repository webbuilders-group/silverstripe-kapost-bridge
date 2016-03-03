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
                    $('#Form_ConvertObjectForm .kapostConvertRightSide > div.field.parent-page-id').hide();
                    $('#Form_ConvertObjectForm .kapostConvertRightSide > div.field.replace-page-id, #Form_ConvertObjectForm #UpdateURLSegment:not(.keep-hidden)').show();
                    
                    var segmentUpdateField=$('#Form_ConvertObjectForm_UpdateURLSegment');
                    if(segmentUpdateField.length>0 && segmentUpdateField.hasClass('.keep-hidden')==false) {
                        segmentUpdateField.attr('disabled', false);
                    }
                }else if(selectedVal=='NewPage') {
                    $('#Form_ConvertObjectForm .kapostConvertRightSide > div.field.parent-page-id').show();
                    $('#Form_ConvertObjectForm .kapostConvertRightSide > div.field.replace-page-id, #Form_ConvertObjectForm #UpdateURLSegment').hide();
                    
                    var segmentUpdateField=$('#Form_ConvertObjectForm div.field.urlsegmentcheck');
                    if(segmentUpdateField.length>0) {
                        segmentUpdateField.attr('disabled', true);
                    }
                }else {
                    $('#Form_ConvertObjectForm .kapostConvertRightSide > div.field.parent-page-id, #Form_ConvertObjectForm .kapostConvertRightSide > div.field.replace-page-id').hide();
                }
            }
        });
        
        $('#Form_ConvertObjectForm_ReplacePageID').entwine({
            onchange: function() {
                var segmentUpdateField=$('#Form_ConvertObjectForm_UpdateURLSegment');
                if(segmentUpdateField.length>0 && segmentUpdateField.attr('data-replace-id')!=$(this).val()) {
                    segmentUpdateField.attr('disabled', true).closest('.field').addClass('keep-hidden').hide();
                }else {
                    var segmentFieldWrapper=segmentUpdateField.closest('.field');
                    segmentFieldWrapper.removeClass('keep-hidden');
                    
                    if($('#Form_ConvertObjectForm_ConvertMode input.radio:checked').val()=='ReplacePage') {
                        segmentUpdateField.attr('disabled', false);
                        segmentFieldWrapper.show();
                    }
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
        
        $('#kapost-convert-notes-trigger').entwine({
            onadd: function() {
                $(this).addClass('animate').bindDelayListen();
            },
            
            /**
             * 
             */
            onclick: function(e) {
                var popup=$('#kapost-convert-notes-popup');
                var self=$(this);
                
                popup.toggleClass('visible');
                self.toggleClass('animate');
                
                if(popup.hasClass('visible')) {
                    $(document.body).on('click', hideConvertNotes);
                    popup.on('click', blockPropagate);
                    self.bindDelayListen();
                }else {
                    $(document.body).off('click', hideConvertNotes);
                    popup.off('click', blockPropagate);
                }
                
                e.stopPropagation();
                return false;
            },
            
            /**
             * 
             */
            bindDelayListen: function() {
                var self=$(this);
                self.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', self._delayInteration);
            },

            /**
             * 
             */
            restartAnimation: function() {
                var self=$(this);
                
                var newButton=self.clone().removeClass('animate');
                self.replaceWith(newButton);
            },
            
            /**
             * Delays the next loop iteration by 20s 
             */
            _delayInteration: function() {
                $(this).restartAnimation();
            }
        });
    });
    
    
    /**
     * Blocks propagation of an event
     * @param e Event Data
     */
    function blockPropagate(e) {
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the convert notes when the body is clicked
     * @param e Event Data
     */
    function hideConvertNotes(e) {
        $('#kapost-convert-notes-popup').removeClass('visible').off('click', blockPropagate);
        $('#kapost-convert-notes-trigger').entwine('kapost').restartAnimation();
        
        $(document.body).off('click', hideConvertNotes);
        
        e.stopPropagation();
        return false;
    }
})(jQuery);