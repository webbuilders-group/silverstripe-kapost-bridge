(function($) {
    $.entwine('ss', function($) {
        $('.cms-edit-form.KapostAdmin').entwine({
            panelRedirect: function (destination) {
                //Close the dialog
                $('#kapost-convert-object-dialog').ssdialog('close');
                
                //Load the destination panel
                $('.cms-container').loadPanel(destination);
                
                if(destination.match(/^admin\/pages\//)) {
                    var menu=$('.cms-menu-list');
                    var item=menu.find('li#Menu-CMSPagesController');
                    
                    if(!item.hasClass('current')) {
                        item.select();
                    }
                    
                    menu.updateItems();
                }
            }
        });
        
        $('.cms-edit-form .Actions input.action[type=submit].kapost-action-convert, .cms-edit-form .Actions button.action.kapost-action-convert').entwine({
            onclick: function(e) {
                //Get the post ID
                var postID=$(this).closest('form').attr('action');
                postID=parseInt(postID.replace(/^admin\/kapost\/KapostObject\/EditForm\/field\/KapostObject\/item\/(\d+)\/ItemEditForm/, '$1'));
                
                //If the post id is ok open the dialog
                if(postID>0) {
                    var dialog=$('#kapost-convert-object-dialog');
                    if(!dialog.length) {
                        dialog=$('<div class="ss-ui-dialog" id="kapost-convert-object-dialog"/>');
                        $('body').append(dialog);
                    }
                    
                    
                    dialog.ssdialog({'iframeUrl': 'admin/kapost/KapostObject/EditForm/field/KapostObject/item/'+postID+'/convert', 'autoOpen': true, maxHeight: 400});
                }
                
                
                $(this).blur(); //Blur so jQuery UI removes the down classes
                
                //Stop propagation to prevent the core cms js from firing on the button
                e.stopPropagation();
                return false;
            }
        });
    });
})(jQuery);