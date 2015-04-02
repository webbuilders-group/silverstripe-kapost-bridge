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
                                    'Content'=>null,
                                    'Form'=>$this->ConvertObjectForm()
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * 
     */
    public function ConvertObjectForm() {
        $fields=new FieldList(
                            CompositeField::create(
                                    new OptionsetField('ConvertMode', '', array(
                                                                                'ReplacePage'=>_t('KapostAdmin.REPLACES_AN_EXISTING_PAGE', '_This replaces an existing page'),
                                                                                'NewPage'=>_t('KapostAdmin.IS_NEW_PAGE', '_This is a new page')
                                                                            ), 'NewPage')
                                )->addExtraClass('kapostConvertLeftSide'),
                            CompositeField::create(
                                    new TreeDropdownField('TargetPageID', ' ', 'SiteTree')
                                )->addExtraClass('kapostConvertRightSide')
                        );
        
        
        $actions=new FieldList(
                                FormAction::create('doConvertObject', _t('KapostAdmin.CONTINUE_CONVERT', '_Continue'))
                                    ->setUseButtonTag(true)
                                    ->addExtraClass('ss-ui-action-constructive')
                                    ->setAttribute('data-icon', 'kapost-convert')
                            );
        
        
        $validator=new RequiredFields(
                                        'ConvertMode'
                                    );
        
        
        $form=new Form($this, 'ConvertObjectForm', $fields, $actions, $validator);
        $form
            ->addExtraClass('KapostAdmin center')
            ->setAttribute('data-layout-type', 'border')
            ->setTemplate('KapostAdmin_ConvertForm');
        
        
        //Allow extensions to adjust the form
        $this->extend('updateConvertObjectForm', $form);
        
        
        Requirements::css(KAPOST_DIR.'/css/KapostAdmin.css');
        
        Requirements::add_i18n_javascript(KAPOST_DIR.'/javascript/lang/');
        Requirements::javascript(KAPOST_DIR.'/javascript/KapostAdmin_convertPopup.js');
        
        return $form;
    }
    
    /**
     * Handles conversion of the current record
     * @param {array} $data Submitted Data
     * @param {Form} $form Submitting Form
     * @return {mixed} Returns an SS_HTTPResponse or an HTML string
     */
    public function doConvertObject($data, Form $form) {
        if($data['ConvertMode']=='ReplacePage') {
            if(empty($data['TargetPageID']) || $data['TargetPageID']==0) {
                $form->sessionMessage(_t('KapostAdmin.NO_REPLACE_PAGE_TARGET', '_You must select a page to replace'), 'error');
                return $this->popupController->redirectBack();
            }
            
            if(($redirectURL=$this->replacePage($data, $form))===false) {
                $form->sessionMessage(_t('KapostAdmin.ERROR_COULD_NOT_REPLACE', '_Sorry an error occured and the target page could not be replaced.'), 'error');
                return $this->popupController->redirectBack();
            }else {
                Requirements::clear();
                Requirements::customScript('window.parent.jQuery(\'.cms-edit-form.KapostAdmin\').entwine(\'ss\').panelRedirect('.json_encode($redirectURL).')');
                
                return $this->customise(array(
                                            'Title'=>null,
                                            'Content'=>null,
                                            'Form'=>null
                                        ))->renderWith('CMSDialog');
            }
        }else if($data['ConvertMode']=='NewPage') {
            if(($redirectURL=$this->newPage($data, $form))===false) {
                $form->sessionMessage(_t('KapostAdmin.ERROR_COULD_NOT_CREATE', '_Sorry an error occured and the page could not be created.'), 'error');
                return $this->popupController->redirectBack();
            }else {
                Requirements::clear();
                Requirements::customScript('window.parent.jQuery(\'.cms-edit-form.KapostAdmin\').entwine(\'ss\').panelRedirect('.json_encode($redirectURL).')');
                
                return $this->customise(array(
                                            'Title'=>null,
                                            'Content'=>null,
                                            'Form'=>null
                                        ))->renderWith('CMSDialog');
            }
        }
        
        
        //Allow extensions to convert the object
        if(in_array($data['ConvertMode'], KapostAdmin::config()->extra_conversion_modes)) {
            $results=$this->extend('doConvert'.$data['ConvertMode'], $data, $form, $this);
            if(count($results)>0) {
                foreach($results as $result) {
                    if($result!==false) {
                        Requirements::clear();
                        Requirements::customScript('window.parent.jQuery(\'.cms-edit-form.KapostAdmin\').entwine(\'ss\').panelRedirect('.json_encode($result).')');
                        
                        return $this->customise(array(
                                                    'Title'=>null,
                                                    'Content'=>null,
                                                    'Form'=>null
                                                ))->renderWith('CMSDialog');
                    }
                }
                
                
                $message=$form->Message();
                if(empty($message)) {
                    $form->sessionMessage(_t('KapostAdmin.GENERIC_CONVERSION_ERROR', '_Conversion method returns an error and no specific message'), 'error');
                }
                
                //All failed redirect back
                return $this->popupController->redirectBack();
            }
        }
        
        
        $form->sessionMessage(_t('KapostAdmin.UNKNOWN_CONVERSION_MODE', '_Unknown conversion mode: {mode}', array('mode'=>$data['ConvertMode'])), 'error');
        return $this->popupController->redirectBack();
    }
    
    /**
     * Handles creation of a new page from the current record
     * @param {array} $data Submitted Data
     * @param {Form} $form Submitting Form
     * @return {bool} Returns boolean true on success, false otherwise
     */
    public function newPage($data, Form $form) {
        if(class_exists($this->record->DestinationClass) && is_subclass_of($this->record->DestinationClass, 'SiteTree')) {
            $convertToClass=$this->record->DestinationClass;
        }else {
            $parentClasses=array_reverse(ClassInfo::dataClassesFor($this->record->ClassName));
            unset($parentClasses[$this->record->ClassName]);
            unset($parentClasses['KapostObject']);
            
            if(count($parentClasses)>0) {
                foreach($parentClasses as $class) {
                    $sng=singleton($class);
                    if(class_exists($sng->DestinationClass) && is_subclass_of($sng->DestinationClass, 'SiteTree')) {
                        $convertToClass=$sng->DestinationClass;
                        break;
                    }
                }
                
                if(isset($sng)) {
                    unset($sng);
                }
            }
        }
        
        
        //Verify we have a destination class
        if($convertToClass!==false) {
            $source=clone $this->record; //Create a clone of the record to be safe
            
            
            //Create the new page
            $destination=new $convertToClass();
            $destination->write();
            
            //Merge the kapost object into the new page
            $this->merge($destination, $source, 'right', true, true);
            
            
            //If a target was set and the parent has been found set the parent of the page
            if(intval($data['TargetPageID']) && SiteTree::get()->filter('ID', intval($data['TargetPageID']))->count()>0) {
                $destination->ParentID=intval($data['TargetPageID']);
            }
            
            //Write the destination one more time to be sure
            $destination->write();
            
            
            //Allow extensions to update the object
            $this->extend('updateNewPageConversion', $destination, $data, $form);
            
            
            //If the kapost object is to be published publish it
            if($this->record->ToPublish==true) {
                $destination->writeToStage('Stage', 'Live');
            }
            
            
            //Delete the current record
            $this->record->delete();
            
            
            return 'admin/pages/edit/show/'.$destination->ID;
        }
        
        return false;
    }
    
    /**
     * Handles creation of a new page from the current record
     * @param {array} $data Submitted Data
     * @param {Form} $form Submitting Form
     * @return {bool} Returns boolean true on success, false otherwise
     */
    public function replacePage($data, Form $form) {
        $destinationClass=$this->record->DestinationClass;
        $destination=SiteTree::get()->byID(intval($data['TargetPageID']));
        
        //Verify we have a destination class
        if(!empty($destination) && $destination!==false && $destination->exists() && ($destination->ClassName==$destinationClass || is_subclass_of($destination, $destinationClass))) {
            $source=clone $this->record; //Create a clone of the record to be safe
            
            
            //Merge the kapost object into the target page
            $this->merge($destination, $source, 'right', true, true);
            
            
            //Write the destination one more time to be sure
            $destination->write();
            
            
            //Allow extensions to update the object
            $this->extend('updateReplacePageConversion', $destination, $data, $form);
            
            
            //If the kapost object is to be published publish it
            if($this->record->ToPublish==true) {
                $destination->publish('Stage', 'Live');
            }
            
            
            //Delete the current record
            $this->record->delete();
            
            
            return 'admin/pages/edit/show/'.$destination->ID;
        }
        
        return false;
    }
    
    /**
     * Merges data and relations from another object of same class, without conflict resolution. Allows to specify which dataset takes priority in case its not empty. has_one-relations are just transferred with priority 'right'. has_many and many_many-relations are added regardless of priority.
     * Caution: has_many/many_many relations are moved rather than duplicated,
     * meaning they are not connected to the merged object any longer.
     * Caution: Just saves updated has_many/many_many relations to the database,
     * doesn't write the updated object itself (just writes the object-properties).
     * Caution: Does not delete the merged object.
     * Caution: Does now overwrite Created date on the original object.
     * @param $leftObj DataObject
     * @param $rightObj DataObject
     * @param $priority String left|right Determines who wins in case of a conflict (optional)
     * @param $includeRelations Boolean Merge any existing relations (optional)
     * @param $overwriteWithEmpty Boolean Overwrite existing left values with empty right values. Only applicable with $priority='right'. (optional)
     * @return Boolean
     */
    private function merge($leftObj, $rightObj, $priority = 'right', $includeRelations = true, $overwriteWithEmpty = false) {
        if(!$rightObj->ID) {
            user_error("DataObject->merge(): Please write your merged-in object to the database before merging, to make sure all relations are transferred properly.').", E_USER_WARNING);
            return false;
        }
        
        // makes sure we don't merge data like ID or ClassName
        $leftData=$leftObj->inheritedDatabaseFields();
        $rightData=$rightObj->inheritedDatabaseFields();
        
        foreach($rightData as $key=>$rightVal) {
            // don't merge conflicting values if priority is 'left'
            if($priority=='left' && $leftObj->{$key}!==$rightObj->{$key}) {
                continue;
            }
            
            // don't overwrite existing left values with empty right values (if $overwriteWithEmpty is set)
            if($priority=='right' && !$overwriteWithEmpty && empty($rightObj->{$key})) {
                continue;
            }
            
            $leftObj->{$key}=$rightObj->{$key};
        }
        
        // merge relations
        if($includeRelations) {
            if($manyMany=$leftObj->many_many()) {
                foreach($manyMany as $relationship=>$class) {
                    $leftComponents=$leftObj->getManyManyComponents($relationship);
                    
                    if(!$rightObj->many_many($relationship)) {
                        continue;
                    }
                    
                    $rightComponents=$rightObj->getManyManyComponents($relationship);
                    if($rightComponents && $rightComponents->exists()) {
                        $leftComponents->addMany($rightComponents->column('ID'));
                    }
                    
                    $leftComponents->write();
                }
            }
            
            if($hasMany=$leftObj->has_many()) {
                foreach($hasMany as $relationship=>$class) {
                    $leftComponents=$leftObj->getComponents($relationship);
                    
                    if(!$rightObj->has_many($relationship)) {
                        continue;
                    }
                    
                    $rightComponents=$rightObj->getComponents($relationship);
                    if($rightComponents && $rightComponents->exists()) {
                        $leftComponents->addMany($rightComponents->column('ID'));
                    }
                    $leftComponents->write();
                }

            }
            
            if($hasOne=$leftObj->has_one()) {
                foreach($hasOne as $relationship=>$class) {
                    $leftComponent=$leftObj->getComponent($relationship);
                    
                    if(!$rightObj->has_one($relationship)) {
                        continue;
                    }
                    
                    $rightComponent=$rightObj->getComponent($relationship);
                    if($leftComponent->exists() && $rightComponent->exists() && $priority=='right') {
                        $leftObj->{$relationship.'ID'}=$rightObj->{$relationship.'ID'};
                    }
                }
            }
        }
        
        return true;
    }
}
?>