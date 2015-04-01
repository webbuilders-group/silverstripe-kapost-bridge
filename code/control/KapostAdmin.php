<?php
class KapostAdmin extends ModelAdmin {
    public $showImportForm=false;
    
    private static $url_segment='kapost';
    private static $managed_models=array(
                                        'KapostObject'
                                    );
    
    /**
     * Form used for displaying the gridfield in the model admin
     * @param {string} $id ID of the form
     * @param {FieldList} $fields Fields to use in the form
     * @return {Form} Form to be used in the model admin interface
     */
    public function getEditForm($id=null, $fields=null) {
        $form=parent::getEditForm($id, $fields);
        
        if($gridField=$form->Fields()->dataFieldByName('KapostObject')) {
            $gridField->getConfig()
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
        }
        
        
        return $form;
    }
}
?>