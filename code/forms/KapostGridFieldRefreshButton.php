<?php
class KapostGridFieldRefreshButton implements GridField_HTMLProvider, GridField_ActionProvider
{
    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment='after')
    {
        $this->targetFragment=$targetFragment;
    }
    
    /**
     * Handle an action on the given {@link GridField}.
     * @param {GridField} $gridField GridField bound to
     * @param {string} $actionName Action identifier, see {@link getActions()}.
     * @param {array} $arguments Arguments relevant for the action
     * @param {array} $data All form data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        //Do nothing, we want to just reload the list
    }
    
    /**
     * Return a list of the actions handled by this action provider.
     * @param GridField GridField bound to
     * @return {array} Array with action identifier strings.
     */
    public function getActions($gridField)
    {
        return array('refresh-grid');
    }
    
    /**
     * Returns a map where the keys are fragment names and the values are pieces of HTML to add to these fragments.
     * @param {GridField} $gridField GridField bound to
     * @return {array} Array of HTML fragments to add
     */
    public function getHTMLFragments($gridField)
    {
        $button=GridField_FormAction::create($gridField, 'refresh-grid', _t('KapostGridFieldRefreshButton.REFRESH_LIST', '_Refresh List'), 'refresh-grid', null)
                                            ->setAttribute('data-icon', 'arrow-circle-double');
        
        return array(
                    $this->targetFragment=>$button->Field()
                );
    }
}
