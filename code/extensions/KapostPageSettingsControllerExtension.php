<?php
class KapostPageSettingsControllerExtension extends Extension {
    /**
     * Updates the form to make all of the fields read only with the exception of a few fields
     * @param {Form} $form Form to be adjusted
     */
    public function updateEditForm(Form $form) {
        $record=$form->getRecord();
        if($record) {
            $kapostRefID=$record->KapostRefID;
            if(empty($kapostRefID)) {
                return;
            }

            //Make the fields all read only
            $oldFields=$form->Fields();
            $form->setFields($oldFields->makeReadonly());
            
            //Make the fields that should be non-readonly editable again
            if(is_array($record->config()->non_readonly_settings_fields)) {
                foreach($record->config()->non_readonly_settings_fields as $fieldName) {
                    $oldField=$oldFields->dataFieldByName($fieldName);
                    if($oldField) {
                        $form->Fields()->replaceField($fieldName, $oldField);
                    }
                }
            }
        }
    }
}
?>