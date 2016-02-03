<?php
class KapostConversionHistory extends DataObject {
    /**
     * Number of days that conversion history records are kept
     * @config KapostConversionHistory.expires_days
     * @default 30
     */
    private static $expires_days=30;
    
    
    private static $db=array(
                            'Title'=>'Varchar(255)',
                            'KapostChangeType'=>"Enum(array('new', 'edit'), 'new')",
                            'KapostRefID'=>'Varchar(255)',
                            'KapostAuthor'=>'Varchar(255)',
                            'DestinationType'=>'Varchar(255)',
                            'DestinationID'=>'Int',
                            'ConverterName'=>'Varchar(120)'
                         );
    
    private static $indexes=array(
                                'DestinationID'=>true
                            );
    
    private static $default_sort='Created DESC';
    
    private static $summary_fields=array(
                                        'Title',
                                        'Created',
                                        'ConverterName',
                                        'DestinationType',
                                        'KapostChangeType',
                                        'KapostAuthor'
                                    );
    
    
    /**
     * Prevent creation of the KapostConversionHistory
     * @param {int|Member} $member Member ID or member instance
     * @return {bool} Returns boolean false
     */
    final public function canCreate($member=null) {
        return false;
    }
    
    /**
     * Prevent editing of the KapostConversionHistory
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
        $kapostBase=KapostAdmin::config()->kapost_base_url;
        if($kapostBase[strlen($kapostBase)-1]!='/') {
            $kapostBase.='/';
        }
        
        $fields=new FieldList(
                            new ReadonlyField('Title', $this->fieldLabel('Title')),
                            new ReadonlyField('Created', _t('KapostConversionHistory.CONVERSION_DATE', '_Conversion Date')),
                            new ReadonlyField('ConverterName', $this->fieldLabel('ConverterName')),
                            new ReadonlyField('KapostChangeTypeNice', $this->fieldLabel('KapostChangeType')),
                            new ReadonlyField('KapostAuthor', $this->fieldLabel('KapostAuthor')),
                            new ReadonlyField('DestinationType', $this->fieldLabel('DestinationType')),
                            $kapostRef=ReadonlyField::create(
                                                    'KapostRefID_linked',
                                                    $this->fieldLabel('KapostRefID'),
                                                    (!empty($kapostBase) ? '<a href="'.htmlentities($kapostBase.'posts/'.$this->KapostRefID).'" target="_blank">'.htmlentities($this->KapostRefID).'</a>':$this->KapostRefID)
                                                ),

                            $destination=ReadonlyField::create(
                                                            'Destination',
                                                            $this->fieldLabel('DestinationID'),
                                                            '<a href="'.htmlentities($this->getDestinationLink()).'" class="kapost-panel-link">'._t('KapostConversionHistory.CONVERTED_TO_OBJECT', '_View Destination Object').'</a>'
                                                        )
                        );
        
        
        $kapostRef->dontEscape=true;
        $destination->dontEscape=true;
        
        
        //Allow extensions to add/remove fields
        $this->extend('updateCMSFields', $fields);
        
        
        return $fields;
    }
    
    /**
     * Gets the link to the desination in the cms
     * @return {string} Relative link to the destination page
     */
    public function getDestinationLink() {
        user_error('You must implement the getDestinationLink() method on your decendent of KapostConversionHistory: '.$this->class, E_USER_WARNING);
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
     * Gets the summary fields for this object
     * @return {array} Map of fields to labels
     */
    public function summaryFields() {
        $fields=parent::summaryFields();
        
        if(array_key_exists('Created', $fields)) {
            $fields['Created']=_t('KapostConversionHistory.CONVERSION_DATE', '_Conversion Date');
        }
        
        return $fields;
    }
    
    /**
     * Cleans up the conversion history records after X days, after writing to the database where X is defined in the config
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        $records=KapostConversionHistory::get()->filter('Created:LessThan', date('Y-m-d H:i:s', strtotime('-'.self::config()->expires_days.' days')));
        if($records->count()>0) {
            foreach($records as $record) {
                $record->delete();
            }
        }
    }
}

class KapostPageConversionHistory extends KapostConversionHistory {
    /**
     * Gets the link to the desination in the cms
     * @return {string} Relative link to the destination page
     */
    public function getDestinationLink() {
        return 'admin/pages/edit/show/'.$this->DestinationID;
    }
}
?>