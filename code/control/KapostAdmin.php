<?php
class KapostAdmin extends ModelAdmin {
    public $showImportForm=false;
    
    private static $url_segment='kapost';
    private static $managed_models=array(
                                        'KapostObject'
                                    );
}
?>