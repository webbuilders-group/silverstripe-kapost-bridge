<?php
class KapostFieldCaster extends Enum {
    private static $casting=array(
                                'NiceChangeType'=>'HTMLVarchar',
                                'NiceToPublish'=>'Varchar',
                                'NiceClassName'=>'Varchar'
                            );
    
    /**
     * Gets the change type's friendly label
     * @return {string} Returns new or edit
     */
    public function NiceChangeType() {
        switch($this->value) {
            case 'new': return _t('KapostFieldCaster.CHANGE_TYPE_NEW', '_New');
            case 'edit': return _t('KapostFieldCaster.CHANGE_TYPE_EDIT', '_Edit');
        }
        
        return $this->XML();
    }
    
    /**
     * Gets the publish type's friendly label
     * @return {string} Returns live or draft
     */
    public function NiceToPublish() {
        if($this->value==true) {
            return _t('KapostFieldCaster.PUBLISH_TYPE_LIVE', '_Live');
        }
        
        return _t('KapostFieldCaster.PUBLISH_TYPE_DRAFT', '_Draft');
    }
    
    /**
     * Wrapper for the object's i18n_singular_name()
     * @return {string} Non-XML ready result of i18n_singular_name or the raw value
     */
    public function NiceClassName() {
        if(class_exists($this->value) && $this->value instanceof DataObject) {
            return singleton($this->value)->i18n_singular_name();
        }
        
        return $this->value;
    }
}
?>