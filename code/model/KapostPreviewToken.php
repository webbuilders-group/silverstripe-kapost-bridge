<?php
/**
 * Class KapostPreviewToken
 *
 * @property string $Code
 * @property string $KapostRefID
 */
class KapostPreviewToken extends DataObject {
    private static $db=array(
                            'Code'=>'Varchar(40)',
                            'KapostRefID'=>'Varchar(255)'
                         );
    
    
    /**
     * Cleans up the expired tokens after writing
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        
        //Clean up the expired tokens
        $expiredTokens=KapostPreviewToken::get()->filter('Created:LessThan', date('Y-m-d H:i:s', strtotime('-'.KapostService::config()->preview_token_expiry.' minutes')));
        if($expiredTokens->count()>0) {
            foreach($expiredTokens as $token) {
                $token->delete();
            }
        }
    }
}
?>