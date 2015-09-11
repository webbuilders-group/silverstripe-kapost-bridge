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
        
        
        $fields->insertAfter(new ReadonlyField('MenuTitle', $this->fieldLabel('MenuTitle')), 'Title');
        $fields->addFieldToTab('Root.Main', new ReadonlyField('MetaDescription', $this->fieldLabel('MetaDescription')));
        
        
        return $fields;
    }
    
    /**
     * Used for recording a conversion history record
     * @param {int} $destinationID ID of the destination object when converting
     * @return {KapostConversionHistory}
     */
    public function createConversionHistory($destinationID) {
        $obj=new KapostPageConversionHistory();
        $obj->Title=$this->Title;
        $obj->KapostChangeType=$this->KapostChangeType;
        $obj->KapostRefID=$this->KapostRefID;
        $obj->KapostAuthor=$this->KapostAuthor;
        $obj->DestinationType=$this->ClassNameNice;
        $obj->DestinationID=$destinationID;
        $obj->write();
        
        return $obj;
    }
    
    /**
     * Handles rendering of the preview for this object
     * @return {string} Preview to be rendered
     */
    public function renderPreview() {
        return Page_Controller::create($this)->customise(array(
                                                                'ClassName'=>$this->DestinationClass,
                                                                'IsKapostPreview'=>true,
                                                                'Children'=>false,
                                                                'Menu'=>false,
                                                                'MetaTags'=>false
                                                            ))->renderWith('Page');
    }
}
?>