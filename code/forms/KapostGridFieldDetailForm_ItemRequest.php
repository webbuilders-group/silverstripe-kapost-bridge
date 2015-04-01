<?php
class KapostGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {
    private static $allowed_actions=array(
                                        'ItemEditForm',
                                        'convert',
                                        'ConvertObjectForm'
                                    );
    
    public function ItemEditForm() {
        $form=parent::ItemEditForm();
        
        if($this->record && $this->record->exists()) {
            $form->Fields()->addFieldToTab('Root.Main', ReadonlyField::create('KapostRefID', $this->record->fieldLabel('KapostRefID'), $this->record->KapostRefID)->setForm($form));
            
            $form->Actions()->insertBefore(
                                            FormAction::create('doConvertPost', _t('KapostAdmin.CONVERT_POST', '_Convert Post'))
                            			        ->setUseButtonTag(true)
                                                ->addExtraClass('ss-ui-action-constructive kapost-action-convert')
				                                ->setAttribute('data-icon', 'kapost-convert')
                                                ->setForm($form)
                                        , 'action_doDelete');
        }
        
        
        
        Requirements::css(KAPOST_DIR.'/css/KapostAdmin.css');
        Requirements::javascript(KAPOST_DIR.'/javascript/KapostAdmin.js');
        
        
        $form->addExtraClass('KapostAdmin');
        return $form;
    }
    
    /**
     * 
     */
    public function convert() {
        return $this->customise(array(
                                    'Title'=>_t('KapostAdmin.CONVERT_POST', '_Convert Post'),
                                    'Content'=>'',
                                    'Form'=>$this->ConvertObjectForm()
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * 
     */
    public function ConvertObjectForm() {
        /* $fields=new FieldList(
                            CompositeField::create(
                                    new OptionsetField('ConvertMode', '', array(
                                                                            ))
                                )->addExtraClass('kapostConvertLeftSide'),
                            CompositeField::create(
                                    new TreeDropdownField('ExistingPageOverride', '', 'SiteTree')
                                )->addExtraClass('kapostConvertRightSide')
                        ); */
    }
}
?>