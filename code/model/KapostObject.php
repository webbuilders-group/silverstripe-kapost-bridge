<?php
/**
 * Class KapostObject
 *
 * @property string $Title
 * @property string $Content
 * @property string $KapostChangeType
 * @property string $KapostRefID
 * @property string $KapostAuthor
 * @property string $KapostAuthorAvatar
 * @property string $KapostConversionNotes
 * @property boolean $ToPublish
 * @property boolean $IsKapostPreview
 * @method DataList|KapostRelationHint[] RelationHints()
 * @method DataList|KapostFieldHint[] FieldHints()
 * @mixin LenovoKapostObject
 */
class KapostObject extends DataObject {
    private static $db=array(
                            'Title'=>'Varchar(255)',
                            'Content'=>'HTMLText',
                            'KapostChangeType'=>"Enum(array('new', 'edit'), 'new')",
                            'KapostRefID'=>'Varchar(255)',
                            'KapostAuthor'=>'Varchar(255)',
                            'KapostAuthorAvatar'=>'Varchar(2083)',
                            'KapostConversionNotes'=>'Text',
                            'ToPublish'=>'Boolean',
                            'IsKapostPreview'=>'Boolean'
                         );
    
    private static $default_sort='Created';
    
    private static $summary_fields=array(
                                        'Title',
                                        'Created',
                                        'ClassNameNice',
                                        'KapostChangeType',
                                        'ToPublish'
                                    );
    
    
    /**
     * Prevent creation of the KapostObjects, they are delivered from Kapost
     * @param int|Member $member Member ID or member instance
     * @return bool Returns boolean false
     */
    final public function canCreate($member=null) {
        return false;
    }
    
    /**
     * Prevent editing of the KapostObjects, they are delivered from Kapost
     * @param int|Member $member Member ID or member instance
     * @return bool Returns boolean false
     */
    final public function canEdit($member=null) {
        return false;
    }
    
    /**
     * Gets fields used in the cms
     * @return FieldList Fields to be used
     */
    public function getCMSFields() {
        $fields=new FieldList(
                            new TabSet('Root',
                                    new Tab('Main', _t('KapostObject.MAIN', '_Main'),
                                        new ReadonlyField('Created', $this->fieldLabel('Created')),
                                        new ReadonlyField('KapostChangeTypeNice', $this->fieldLabel('KapostChangeType')),
                                        new ReadonlyField('KapostAuthor', $this->fieldLabel('KapostAuthor')),
                                        new ReadonlyField('ToPublishNice', $this->fieldLabel('ToPublish')),
                                        new ReadonlyField('ClassNameNice', _t('KapostObject.CONTENT_TYPE', '_Content Type')),
                                        new ReadonlyField('Title', $this->fieldLabel('Title')),
                                        HtmlEditorField_Readonly::create('ContentNice', $this->fieldLabel('Content'), $this->sanitizeHTML($this->Content))
                                    )
                                )
                        );
        
        
        //Allow extensions to adjust the fields
        $this->extend('updateCMSFields', $fields);
        
        return $fields;
    }
    
    /**
     * Gets the change type's friendly label
     * @return string Returns new or edit
     */
    public function getKapostChangeTypeNice() {
        switch($this->KapostChangeType) {
            case 'new': return _t('KapostFieldCaster.CHANGE_TYPE_NEW', '_New');
            case 'edit': return _t('KapostFieldCaster.CHANGE_TYPE_EDIT', '_Edit');
        }
    
        return $this->KapostChangeType;
    }
    
    /**
     * Gets the publish type's friendly label
     * @return string Returns live or draft
     */
    public function getToPublishNice() {
        if($this->ToPublish==true) {
            return _t('KapostFieldCaster.PUBLISH_TYPE_LIVE', '_Live');
        }
    
        return _t('KapostFieldCaster.PUBLISH_TYPE_DRAFT', '_Draft');
    }
    
    /**
     * Wrapper for the object's i18n_singular_name()
     * @return string Non-XML ready result of i18n_singular_name or the raw value
     */
    public function getClassNameNice() {
        return $this->i18n_singular_name();
    }
    
    /**
     * Gets the destination class when converting to the final object, by default this simply removes Kapost form the class name
     * @return string Class to convert to
     */
    public function getDestinationClass() {
        return preg_replace('/^Kapost/', '', $this->ClassName);
    }
    
    /**
     * Strips out not allowed tags, mainly this is to remove the kapost beacon script so it doesn't conflict with the cms
     * @param string $str String to be sanitized
     * @return string HTML to be used
     */
    final public function sanitizeHTML($str) {
        $htmlValue=Injector::inst()->create('HTMLValue', $str);
        $santiser=Injector::inst()->create('HtmlEditorSanitiser', HtmlEditorConfig::get_active());
        $santiser->sanitise($htmlValue);
        
        return $htmlValue->getContent();
    }
    
    /**
     * Ensures the content type appears in the searchable fields
     * @param array $_params
     * @return FieldList Form fields to use in searching
     */
    public function scaffoldSearchFields($_params=null) {
        $fields=parent::scaffoldSearchFields($_params);
        
        if(!$fields->dataFieldByName('ClassNameNice')) {
            $classMap=ClassInfo::subclassesFor('KapostObject');
            unset($classMap['KapostObject']);
            
            foreach($classMap as $key=>$value) {
                $classMap[$key]=_t($key.'.SINGULARNAME', $value);
            }
            
            $fields->push(DropdownField::create('ClassNameNice', _t('KapostObject.CONTENT_TYPE', '_Content Type'), $classMap)->setEmptyString('('._t('Enum.ANY', 'Any').')'));
        }
        
        return $fields;
    }
    
    /**
     * Gets the summary fields for this object
     * @return array Map of fields to labels
     */
    public function summaryFields() {
        $fields=parent::summaryFields();
        
        if(array_key_exists('ClassNameNice', $fields)) {
            $fields['ClassNameNice']=_t('KapostObject.CONTENT_TYPE', '_Content Type');
        }
        
        return $fields;
    }
    
    /**
     * Used for recording a conversion history record
     * @return KapostConversionHistory
     */
    public function createConversionHistory($destinationID) {
        user_error('You should implement the createConversionHistory() method on your decendent of KapostObject', E_USER_WARNING);
    }
    
    /**
     * Handles rendering of the preview for this object
     * @return string Preview to be rendered
     */
    public function renderPreview() {
        if(Director::isDev()) {
            user_error('You should implement the renderPreview() method on your decendent of KapostObject', E_USER_WARNING);
        }
        
        return $this->renderWith(array('KapostPreviewUnsupported', 'Page'));
    }
    
    /**
     * Gets the edit link for the Kapost Object
     * @return string Edit link for the Kapost Object
     */
    public function CMSEditLink() {
        return Controller::join_links(LeftAndMain::config()->url_base, KapostAdmin::config()->url_segment, 'KapostObject/EditForm/field/KapostObject/item', $this->ID, 'edit');
    }
    
    /**
     * Validation to be performed when object is being ingested from Kapost
     * @return ValidationResult
     * @see KapostObject::validate()
     */
    public function validate_incoming() {
        $validator=$this->validate();
        
        $this->extend('validate_incoming', $validator);
        
        return $validator;
    }
    
    /**
     * Validates the current object, invalid objects will not be written. By default all Kapost objects are valid if they have a value in the KapostRefID
     * @return ValidationResult
     * @see DataObject::validate()
     */
    protected function validate() {
        $validator=parent::validate();
        
        //Verify we have a KapostRefID
        $kapostID=$this->KapostRefID;
        if(empty($kapostID)) {
            $validator->error(_t('KapostObject.MISSING_KAPOST_ID', '_Kapost Reference ID is missing'), 'missing-kapost-id');
        }
        
        return $validator;
    }
    
    /**
     * Ensures a title is present for the Kapost Object before writing
     */
    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        
        //If the title is not present make one from the translated singular name and the Kapost Reference ID
        $title=$this->Title;
        if(empty($title)) {
            $this->Title=$this->i18n_singular_name().': '.$this->KapostRefID;
        }
    }
    
    /**
     * Calls the cleanup expired previews after writing
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        
        $this->cleanUpExpiredPreviews();
    }
    
    /**
     * Cleans up expired Kapost previews after twice the token expiry
     */
    protected function cleanUpExpiredPreviews() {
        $expiredPreviews=KapostObject::get()->filter('IsKapostPreview', true)->filter('LastEdited:LessThan', date('Y-m-d H:i:s', strtotime('-'.(KapostService::config()->preview_data_expiry).' minutes')));
        if($expiredPreviews->count()>0) {
            foreach($expiredPreviews as $kapostObj) {
                $kapostObj->delete();
            }
        }
    }
}
?>