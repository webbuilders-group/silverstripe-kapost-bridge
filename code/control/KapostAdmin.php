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
    
    /**
	 * 
	 * @return SS_List of forms
	 * 
	 * @see ModelAdmin::getManagedModelTabs()
	 */
	protected function getManagedModelTabs() {
		$tabs=parent::getManagedModelTabs();
        
		
		//If the log viewer is installed add that to the managed model tabs
		if(class_exists('KapostBridgeLogViewer')) {
		    $sng=new KapostBridgeLogViewer();
		    $tabs->push(new ArrayData(array(
                            	            'Title'=>$sng->SectionTitle(),
                            	            'ClassName'=>'KapostBridgeLogViewer',
                            	            'Link'=>$sng->Link(),
                            	            'LinkOrCurrent'=>'link'
                            		    )));
		}
		
		
		return $tabs;
	}
    
    /**
     * Gets the list used in the ModelAdmin
     * @return {SS_List}
     */
    public function getList() {
        $context=$this->getSearchContext();
        $params=$this->getRequest()->requestVar('q');
        
        if(is_array($params)) {
            if(array_key_exists('Created', $params) && is_array($params['Created'])) {
                $params['Created']=implode(' ', $params['Created']);
            }
            
            $params=array_map('trim', $params);
        }
        
        $list=$context->getResults($params);
        
        $this->extend('updateList', $list);
        
        return $list;
    }
    
    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     */
    public function getManagedModels() {
        $models=parent::getManagedModels();
        
        if(array_key_exists('KapostObject', $models)) {
            $models['KapostObject']['title']=_t('KapostAdmin.INCOMING_CONTENT', '_Incoming Content');
        }
        
        return $models;
    }
}
?>