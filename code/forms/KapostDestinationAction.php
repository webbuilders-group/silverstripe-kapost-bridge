<?php
class KapostDestinationAction extends GridFieldViewButton {
    /**
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 *
	 * @return string - the HTML for the column 
	 */
    public function getColumnContent($gridField, $record, $columnName) {
        return '<a href="'.$record->getDestinationLink().'" class="action action-detail kapost-panel-link kapost-destination-action"><img src="'.KAPOST_DIR.'/images/icons/arrow-switch.png" alt=""/></a>';
    }
}
?>