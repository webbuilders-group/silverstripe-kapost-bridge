<?php
/**
 * Class KapostSiteTreeExtension
 *
 * @property SiteTree|KapostSiteTreeExtension $owner
 * @property string $KapostRefID
 */
class KapostSiteTreeExtension extends DataExtension {
    private static $db=array(
                            'KapostRefID'=>'Varchar(255)'
                         );
    
    
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param FieldList $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        $kapostRefID=$this->owner->KapostRefID;
        if(!empty($kapostRefID)) {
            if(CMSPageEditController::has_extension('KapostPageEditControllerExtension')) {
                $messageContent=_t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING_RO', '_This Page\'s content is being populated by Kapost, some fields are not editable.');
            }else {
                $messageContent=_t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING', '_This Page\'s content is being populated by Kapost.');
            }
            
            
            //Edit in kapost link
            $kapostBase=KapostAdmin::config()->kapost_base_url;
            if(!empty($kapostBase)) {
                $messageContent.=' <a href="'.Controller::join_links($kapostBase, 'posts', $kapostRefID).'" target="_blank">'._t('KapostSiteTreeExtension.KAPOST_CONTENT_EDIT_LABEL', '_Click here to edit in Kapost').'</a>';
            }
            
            
            $fields->insertBefore(new LiteralField('KapostContentWarning', '<div class="message warning">'.$messageContent.'</div>'), 'Title');
            
            
            //Detect Incoming Changes
            if(Permission::check('CMS_ACCESS_KapostAdmin')) {
                $incoming=KapostObject::get()->filter('IsKapostPreview', 0)->filter('KapostRefID', Convert::raw2sql($kapostRefID));
                if($incoming->count()>=1) {
                    $link=Controller::join_links(AdminRootController::config()->url_base, KapostAdmin::config()->url_segment, 'KapostObject/EditForm/field/KapostObject/item', $incoming->first()->ID, 'edit');
                    
                    $messageContent=_t('KapostSiteTreeExtension.KAPOST_INCOMING', '_There are incoming changes from Kapost waiting for this page.').' '.
                                    '<a href="'.$link.'" class="cms-panel-link">'._t('KapostSiteTreeExtension.KAPOST_INCOMING_VIEW', '_Click here to view the changes').'</a>';
                    
                    $fields->insertBefore(new LiteralField('KapostIncomingWarning', '<div class="message warning">'.$messageContent.'</div>'), 'Title');
                }
            }
        }
        
        $fields->removeByName('KapostRefID');
    }
    
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param FieldList $fields Field List that new fields will be added to
     */
    public function updateSettingsFields(FieldList $fields) {
    $kapostRefID=$this->owner->KapostRefID;
        if(!empty($kapostRefID)) {
            if(CMSPageSettingsController::has_extension('KapostPageSettingsControllerExtension')) {
                $messageContent=_t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING_RO', '_This Page\'s content is being populated by Kapost, some fields are not editable.');
            }else {
                $messageContent=_t('KapostSiteTreeExtension.KAPOST_CONTENT_WARNING', '_This Page\'s content is being populated by Kapost.');
            }
            
            
            //Edit in kapost link
            $kapostBase=KapostAdmin::config()->kapost_base_url;
            if(!empty($kapostBase)) {
                $messageContent.=' <a href="'.Controller::join_links($kapostBase, 'posts', $kapostRefID).'" target="_blank">'._t('KapostSiteTreeExtension.KAPOST_CONTENT_EDIT_LABEL', '_Click here to edit in Kapost').'</a>';
            }
            
            
            $fields->insertBefore(new LiteralField('KapostContentWarning', '<div class="message warning">'.$messageContent.'</div>'), 'ClassName');
            
            
            //Detect Incoming Changes
            if(Permission::check('CMS_ACCESS_KapostAdmin')) {
                $incoming=KapostObject::get()->filter('KapostRefID', Convert::raw2sql($kapostRefID));
                if($incoming->count()>=1) {
                    $link=Controller::join_links(AdminRootController::config()->url_base, KapostAdmin::config()->url_segment, 'KapostObject/EditForm/field/KapostObject/item', $incoming->first()->ID, 'edit');
                    
                    $messageContent=_t('KapostSiteTreeExtension.KAPOST_INCOMING', '_There are incoming changes from Kapost waiting for this page.').' '.
                                    '<a href="'.$link.'" class="cms-panel-link">'._t('KapostSiteTreeExtension.KAPOST_INCOMING_VIEW', '_Click here to view the changes').'</a>';
                    
                    $fields->insertBefore(new LiteralField('KapostIncomingWarning', '<div class="message warning">'.$messageContent.'</div>'), 'ClassName');
                }
            }
        }
    }
}
?>