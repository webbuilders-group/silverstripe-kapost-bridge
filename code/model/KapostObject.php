<?php
class KapostObject extends DataObject {
    private static $db=array(
                            'Title'=>'Varchar(255)',
                            'Content'=>'HTMLText',
                            'KapostChangeType'=>"Enum(array('new', 'edit'), 'new')",
                            'KapostRefID'=>'Varchar(255)',
                            'KapostAuthor'=>'Varchar(255)',
                            'ToPublish'=>'Boolean'
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
     * @param {int|Member} $member Member ID or member instance
     * @return {bool} Returns boolean false
     */
    final public function canCreate($member=null) {
        return false;
    }
    
    /**
     * Prevent editing of the KapostObjects, they are delivered from Kapost
     * @param {int|Member} $member Member ID or member instance
     * @return {bool} Returns boolean false
     */
    final public function canEdit($member=null) {
        return false;
    }
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
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
     * @return {string} Returns new or edit
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
     * @return {string} Returns live or draft
     */
    public function getToPublishNice() {
        if($this->ToPublish==true) {
            return _t('KapostFieldCaster.PUBLISH_TYPE_LIVE', '_Live');
        }
    
        return _t('KapostFieldCaster.PUBLISH_TYPE_DRAFT', '_Draft');
    }
    
    /**
     * Wrapper for the object's i18n_singular_name()
     * @return {string} Non-XML ready result of i18n_singular_name or the raw value
     */
    public function getClassNameNice() {
        return $this->i18n_singular_name();
    }
    
    /**
     * Gets the destination class when converting to the final object, by default this simply removes Kapost form the class name
     * @return {string} Class to convert to
     */
    public function getDestinationClass() {
        return preg_replace('/^Kapost/', '', $this->ClassName);
    }
    
    /**
     * Strips out not allowed tags, mainly this is to remove the kapost beacon script so it doesn't conflict with the cms
     * @param {string} $str String to be sanitized
     * @return {string} HTML to be used
     */
    final public function sanitizeHTML($str) {
        $htmlValue=Injector::inst()->create('HTMLValue', $str);
        $santiser=Injector::inst()->create('HtmlEditorSanitiser', HtmlEditorConfig::get_active());
        $santiser->sanitise($htmlValue);
        
        return $htmlValue->getContent();
    }
    
    /**
     * Ensures the content type appears in the searchable fields
     * @param {array} $_params
     * @return {FieldList} Form fields to use in searching
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
     * @return {array} Map of fields to labels
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
     * @return {KapostConversionHistory}
     */
    public function createConversionHistory($destinationID) {
        user_error('You should implement the createConversionHistory() method on your decendent of KapostObject', E_USER_WARNING);
    }
}
?>