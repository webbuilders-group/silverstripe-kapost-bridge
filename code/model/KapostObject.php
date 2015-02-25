<?php
class KapostObject extends DataObject {
    private static $db=array(
                            'Title'=>'Varchar(255)',
                            'Content'=>'HTMLText',
                            'MetaDescription'=>'Text',
                            'KapostChangeType'=>"Enum(array('new', 'edit'), 'new')",
                            'ToPublish'=>'Boolean'
                         );
    
    private static $default_sort='Created';
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $fields=parent::getCMSFields();
        
        
        $fields->removeByName('KapostChangeType');
        
        
        return $fields;
    }
}
?>