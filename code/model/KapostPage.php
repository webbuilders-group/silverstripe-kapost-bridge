<?php
/**
 * Class KapostPage
 *
 * @property string $MenuTitle
 * @property string $MetaDescription
 * @property string $HTMLTitle
 * @property string $Abstract
 * @property string $Panel1Heading
 * @property string $Subheading
 * @property string $Segment
 * @property string $FundingLogos
 * @property string $ContentValue
 * @property string $Personas
 * @property string $BuyingStage
 * @property string $SocialTitle
 * @property string $SocialDescription
 * @property boolean $ShowInMenus
 * @property boolean $ShowInSearch
 * @property boolean $ShowInBreadcrumbs
 * @property string $Priority
 * @property boolean $HideRepContact
 * @property boolean $HideSearchForm
 * @property boolean $HideResourceCenter
 * @property boolean $MetaNoIndex
 * @property string $ProductServiceTags
 * @property int $LinkedPageID
 * @property int $Panel1BackgroundID
 * @property int $SocialImageID
 * @method SiteTree LinkedPage()
 * @method Image Panel1Background()
 * @method Image SocialImage()
 * @mixin LenovoKapostPage
 */
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
     * @return FieldList Fields to be used
     */
    public function getCMSFields() {
        $self=$this;
        $this->beforeUpdateCMSFields(function($fields) use($self) {
            $fields->insertAfter(new ReadonlyField('MenuTitle', $self->fieldLabel('MenuTitle')), 'Title');
            $fields->addFieldToTab('Root.Main', new ReadonlyField('MetaDescription', $self->fieldLabel('MetaDescription')));
        });
        
        
        return parent::getCMSFields();
    }
    
    /**
     * Used for recording a conversion history record
     * @param int $destinationID ID of the destination object when converting
     * @return KapostConversionHistory
     */
    public function createConversionHistory($destinationID) {
        $obj=new KapostConversionHistory();
        $obj->Title=$this->Title;
        $obj->KapostChangeType=$this->KapostChangeType;
        $obj->KapostRefID=$this->KapostRefID;
        $obj->KapostAuthor=$this->KapostAuthor;
        $obj->DestinationType=$this->DestinationClass;
        $obj->DestinationID=$destinationID;
        $obj->ConverterName=Member::currentUser()->Name;
        $obj->write();
        
        return $obj;
    }
    
    /**
     * Handles rendering of the preview for this object
     * @return string Preview to be rendered
     */
    public function renderPreview() {
        $previewFieldMap=array(
                                'ClassName'=>$this->DestinationClass,
                                'IsKapostPreview'=>true,
                                'Children'=>false,
                                'Menu'=>false,
                                'MetaTags'=>false,
                                'Breadcrumbs'=>false,
                                'current_stage'=>Versioned::current_stage(),
                                'SilverStripeNavigator'=>false
                            );
        
        //Allow extensions to add onto the array
        $extensions=$this->extend('updatePreviewFieldMap');
        $extensions=array_filter($extensions, function($v) {return !is_null($v) && is_array($v);});
        if(count($extensions)>0) {
            foreach($extensions as $ext) {
                $previewFieldMap=array_merge($previewFieldMap, $ext);
            }
        }
        
        
        //Find the controller class
        $ancestry=ClassInfo::ancestry($this->DestinationClass);
        while($class=array_pop($ancestry)) {
            if(class_exists($class."_Controller")) {
                break;
            }
        }
        
        $controller=($class!==null ? "{$class}_Controller":"ContentController");
        
        return $controller::create($this)->customise($previewFieldMap);
    }
}
?>