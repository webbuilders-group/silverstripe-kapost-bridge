<?php
/**
 * Class KapostPageEditControllerExtension
 *
 * @property CMSPageEditController $owner
 */
class KapostPageEditControllerExtension extends Extension {
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
            foreach($record->config()->non_readonly_fields as $fieldName) {
                $oldField=$oldFields->dataFieldByName($fieldName);
                if($oldField) {
                    $form->Fields()->replaceField($fieldName, $oldField);
                }
            }
            
            
            //Loop through the wysiwyg fields that need to be made safe and sanitize their html
            foreach($record->config()->make_safe_wysiwyg_fields as $fieldName) {
                $field=$form->Fields()->dataFieldByName($fieldName);
                if($field) {
                    $field->setName($field->getName().'_safe');
                    $field->setValue($this->sanitizeHTML($field->Value()));
                }
            }
        }
    }
    
    /**
     * Strips out not allowed tags, mainly this is to remove the kapost beacon script so it doesn't conflict with the cms
     * @param {string} $str String to be sanitized
     * @return {string} HTML to be used
     */
    private function sanitizeHTML($str) {
        $htmlValue=Injector::inst()->create('HTMLValue', $str);
        $santiser=Injector::inst()->create('HtmlEditorSanitiser', HtmlEditorConfig::get_active());
        $santiser->sanitise($htmlValue);
    
        return $htmlValue->getContent();
    }
}
?>