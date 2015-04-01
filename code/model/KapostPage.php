<?php
class KapostPage extends KapostObject {
    private static $db=array(
                            'MenuTitle'=>'Varchar(100)',
                            'MetaDescription'=>'Text'
                        );
    
    private static $has_one=array(
                                'LinkedPage'=>'SiteTree'
                             );
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $fields=parent::getCMSFields();
        
        
        $fields->addFieldToTab('Root.Main', new ReadonlyField('MenuTitle', $this->fieldLabel('MenuTitle')), 'Content');
        $fields->addFieldToTab('Root.Main', new ReadonlyField('MetaDescription', $this->fieldLabel('MetaDescription')));
        
        
        return $fields;
    }
}
?>