<?php
class KapostAdmin extends ModelAdmin {
    public $showImportForm=false;
    
    private static $url_segment='kapost';
    private static $managed_models=array(
                                        'KapostObject',
                                        'KapostConversionHistory'
                                    );
    
    /**
     * Array of name's for extra conversion modes (see documentation for information on how to define these)
     * @config KapostAdmin.extra_conversion_modes
     */
    private static $extra_conversion_modes=array();
    
    /**
     * Set this to a string of the base url for your Kapost account for example https://example.kapost.com/
     * @config KapostAdmin.kapost_base_url
     */
    private static $kapost_base_url=null;
    
    
    /**
     * Form used for displaying the gridfield in the model admin
     * @param {string} $id ID of the form
     * @param {FieldList} $fields Fields to use in the form
     * @return {Form} Form to be used in the model admin interface
     */
    public function getEditForm($id=null, $fields=null) {
        $form=parent::getEditForm($id, $fields);
        
        Requirements::css(KAPOST_DIR.'/css/KapostAdmin.css');
        Requirements::javascript(KAPOST_DIR.'/javascript/KapostAdmin.js');
        
        
        if($this->modelClass=='KapostObject' && $gridField=$form->Fields()->dataFieldByName('KapostObject')) {
            $gridField->setList($gridField->getList()->filter('IsKapostPreview', 0));
            $gridField->getConfig()
                                ->addComponent(new KapostGridFieldRefreshButton('before'))
                                ->removeComponentsByType('GridFieldAddNewButton')
                                ->getComponentByType('GridFieldDataColumns')
                                    ->setFieldCasting(array(
                                                            'Created'=>'SS_Datetime->FormatFromSettings',
                                                            'ClassName'=>'KapostFieldCaster->NiceClassName',
                                                            'KapostChangeType'=>'KapostFieldCaster->NiceChangeType',
                                                            'ToPublish'=>'KapostFieldCaster->NiceToPublish'
                                                        ));
            
            $gridField->getConfig()
                                ->getComponentByType('GridFieldDetailForm')
                                    ->setItemRequestClass('KapostGridFieldDetailForm_ItemRequest');
        }else if($this->modelClass=='KapostConversionHistory' && $gridField=$form->Fields()->dataFieldByName('KapostConversionHistory')) {
            $gridField->getConfig()
                                ->removeComponentsByType('GridFieldAddNewButton')
                                ->addComponent(new KapostDestinationAction(), 'GridFieldEditButton')
                                ->getComponentByType('GridFieldDataColumns')
                                        ->setFieldCasting(array(
                                                                'Created'=>'SS_Datetime->FormatFromSettings',
                                                                'KapostChangeType'=>'KapostFieldCaster->NiceChangeType',
                                                            ));
            
            
            $gridField->getConfig()->getComponentByType('GridFieldDetailForm')->setItemEditFormCallback(function(Form $form) {
                                                                                                            $form->addExtraClass('KapostAdmin');
                                                                                                        });
        }
        
        
        $form->addExtraClass('KapostAdmin');
        
        return $form;
    }
}
?>