<?php
class KapostSiteTreeExtension extends DataExtension {
    private static $db=array(
                            'KapostRefID'=>'Varchar(255)'
                         );
    
    
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param {FieldList} $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->removeByName('KapostRefID');
    }
}
?>