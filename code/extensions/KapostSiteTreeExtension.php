<?php
class KapostSiteTreeExtension extends DataExtension {
    private static $db=array(
                            'KapostRefID'=>'Varchar(255)'
                         );
    
    
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param {FieldList} $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        $kapostRefID=$this->owner->KapostRefID;
        if(!empty($kapostRefID)) {
            if(CMSPageEditController::has_extension('KapostPageEditControllerExtension')) {
                $fields->insertBefore(new LiteralField('KapostContentWarning', '<div class="message warning">'._t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING_RO', '_This Page\'s content is being populated by Kapost, some fields are not editable').'</div>'), 'Title');
            }else {
                $fields->insertBefore(new LiteralField('KapostContentWarning', '<div class="message warning">'._t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING', '_This Page\'s content is being populated by Kapost').'</div>'), 'Title');
            }
        }
        
        $fields->removeByName('KapostRefID');
    }
}
?>