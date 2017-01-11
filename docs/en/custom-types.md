Defining Custom Kapost Object Types
----
In each of the following cases you will need to create a SilverStripe extension that is bound to the ``KapostGridFieldDetailForm_ItemRequest`` class. In Kapost's "Content Types" section you need to make sure that the "Field Name" field matches the destination class. So for a basic/default SilverStripe page the value in this field would be simply "Page". If you had a custom page type called "MyPage" then it would be "MyPage".

### For Page Extensions
With page extensions adjusting the handling of custom fields is very easy. You simply need to attach to the ``updateNewPageConversion`` and ``updateReplacePageConversion`` methods. In both case you are given the following arguments:
1. The destination SiteTree decedent instance
2. The source Kapost object
3. The data submitted in the form
4. The conversion form

With these you should be able to modify the fields on the destination object with no problem. If you have a custom page type you want to support you need to make sure that you extend the KapostPage class naming it to match the destination page type. So if you had a custom page type called "MyPage" your KapostPage extension should be called "KapostMyPage". You can technically name the KapostPage extension what you want however you will need to override the KapostPage::getDestinationClass() method to return the destination class you want. You would then define the extra fields as you would with a normal DataObject, in theory these can one to one with the SiteTree extension.


### For Custom Objects
In the case of custom Kapost objects (those who don't extend Page) you need to handle the entire conversion process manually. First you will need to modify the convert object form to add additional options to the left side and likely to the right side of the form. For example say we have a custom DataObject called Resource that we want to be able to populate or create from Kapost. The custom DataObject and KapostObject defined as follows:
```php
class Resource extends DataObject {
    private static $db=array(
                            'Title'=>'Varchar(255)',
                            'Content'=>'HTMLText',
                            'ResourceContent'=>'Text',
                            'KapostRefID'=>'Varchar(255)',
                            'Published'=>'Boolean'
                        );

    private static $defaults=array(
                                    'Published'=>false
                                );
}

class KapostResource extends KapostObject {
    private static $db=array(
                            'ResourceContent'=>'Text'
                        );

    /**
     * Validation to be performed when object is being ingested from Kapost
     * @return ValidationResult
     * @see KapostObject::validate()
     */
    public function validate_incoming() {
        $validator=parent::validate_incoming();

        /*** Any validation logic you want to perform when an object is being ingested from Kapost ***/

        return $validator;
    }
}
```

So we want to do two things, we'll need to add new options to the radio buttons in the convert object form, then we'll also need to add a dropdown to the right side for the resource to overwrite. We'll also want to have the form pre-select the object to overwrite if it is an existing resource. To do this we'll need to have the following in our KapostGridFieldDetailForm_ItemRequest extension:

```php
class KapostResourceSupport extends Extension {
    public function updateConvertObjectForm(Form $form, KapostObject $source) {
        $convertTypeRadio=$form->Fields()->dataFieldByName('ConvertMode');
        if($convertTypeRadio) {
            $convertTypes=$convertTypeRadio->getSource();
            $convertTypes['ReplaceResource']='Replace an existing Resource';
            $convertTypes['NewResource']='New Resource';

            $convertTypeRadio->setSource($convertTypes);
        }

        $form->Fields()->insertAfter($resourceIDField=new DropdownField('ReplaceResourceID', 'Replaces this resource', Resource::get()->map()), 'ParentPageID');
        $resourceIDField->setForm($form);

        if($source instanceof KapostResource) {
            $resource=Resource::get()->filter('KapostRefID', Convert::raw2sql($source->KapostRefID))->first();
            if(!empty($resource) && $resource!==false && $resource->ID>0) {
                $resourceIDField->setValue($resource->ID);
                $convertTypeRadio->setValue('ReplaceResource');
            }else {
                $convertTypeRadio->setValue('NewResource');
            }
        }

        Requirements::javascript('path/to/ResourceFormScript.js');
    }
}
```

```javascript
//ResourceFormScript.js
(function($) {
    $.entwine('kapost', function($) {
        $('#Form_ConvertObjectForm_ConvertMode').entwine({
            updateVisibleFields: function() {
                var selectedVal=$('#Form_ConvertObjectForm_ConvertMode input.radio:checked').val();

                if(selectedVal=='ReplaceResource') {
                    $('#Form_ConvertObjectForm #ReplaceResourceID').show();
                }else {
                    $('#Form_ConvertObjectForm #ReplaceResourceID').hide();
                }

                this._super();
            }
        });
    });
})(jQuery);
```

Now once the form has been updated to support what we need we'll need to allow the conversion modes in the config layer.

```yml
KapostAdmin:
    extra_conversion_modes:
        - "ReplaceResource"
        - "NewResource"
```

That being done and the manifest flushed we now need to write the handlers for the conversion method in our KapostResourceSupport class. Our custom methods should return the relative url to the CMS section that allows editing of the converted object. For example the stub bellow will cover this:
```php
/**
 * Used to use the Kapost object to replace an existing resource
 * @param {KapostObject} $source Source Kapost Object
 * @param {array} $data Submitted form data
 * @param {Form} $form Submitting form
 * @return {string} URL to redirect to on completion, returns nothing on error but sets a message on the form
 */
public function doConvertReplaceResource(KapostObject $source, $data, Form $form) {
    //@TODO Handle merging of the Kapost object to the existing resource, do not return anything if there is an error. Simply set the error on the form for the user

    return 'admin/resources/Resource/EditForm/field/Resource/item/'.$destination->ID.'/edit';
}


/**
 * Used to use the Kapost object to create a new resource
 * @param {KapostObject} $source Source Kapost Object
 * @param {array} $data Submitted form data
 * @param {Form} $form Submitting form
 * @return {string} URL to redirect to on completion, returns nothing on error but sets a message on the form
 */
public function doConvertNewResource(KapostObject $source, $data, Form $form) {
    //@TODO Handle converting of the Kapost object to the existing resource, do not return anything if there is an error. Simply set the error on the form for the user

    return 'admin/resources/Resource/EditForm/field/Resource/item/'.$destination->ID.'/edit';
}
```

That should be it, you should now have a new custom object that can have it's content delivered from Kapost. Make sure that you store the ``kapost_post_id`` key under the ``custom_fields`` key in the KapostResource object's ``KapostRefID`` field this is displayed and linked in the audit system.

#### Conversion History Records
In your custom object if you want the destination link to appear you must define the ``CMSEditLink`` on our ``Resource`` class.

```php
/**
 * Gets the link to the edit screen for the resource
 * @return {string} Relative link to the edit screen for the resource
 */
public function CMSEditLink() {
    return 'admin/resources/Resource/EditForm/field/Resource/item/'.$this->ID.'/edit';
}
```

In some cases there maybe a need to extend the ``KapostConversionHistory`` class for a particular ``KapostObject`` extension. If that is the case you must override the ``KapostObject::createConversionHistory($destinationID)`` method on your ``KapostObject`` extension. It is not necessary to do this all of the time but you may need to store extra information on a particular history record for example.

```php
/**
 * Used for recording a conversion history record
 * @param {int} $destinationID ID of the destination object when converting
 * @return {KapostConversionHistory}
 */
public function createConversionHistory($destinationID) {
    $obj=new KapostResourceConversionHistory();
    $obj->Title=$this->Title;
    $obj->KapostChangeType=$this->KapostChangeType;
    $obj->KapostRefID=$this->KapostRefID;
    $obj->KapostAuthor=$this->KapostAuthor;
    $obj->DestinationType=$this->ClassNameNice;
    $obj->DestinationID=$destinationID;
    $obj->write();

    return $obj;
}
```


#### Kapost Preview Support
To enable preview support in Kapost you need to override the ``renderPreview`` method on the custom ``KapostObject`` extension. In the case of the example we've been building we need to add the following method to the ``KapostResource`` class.

```php
/**
 * Handles rendering of the preview for this object
 * @return {string} Preview to be rendered
 */
public function renderPreview() {
    return KapostResourcePreview_Controller::create($this)
                ->setRequest(Controller::curr()->getRequest())
                ->customise(array(
                                'IsPreview'=>true,
                                'Children'=>false,
                                'Menu'=>false
                            ))->renderWith(array('Resource', 'Page'));
}
```
Obviously you will need to define the ``KapostResourcePreview_Controller``, you could also simply use the ``Page_Controller`` or just render the object with what ever template you are currently using for rendering your custom object.

#### Validating Incoming Content
When defining a custom type you may want to perform additional validation on the incoming content. This can be done by overriding the ``KapostObject::validate_incoming()`` method and defining your own rules. However you must call this before or after you write your object in your ``newPost`` or ``editPost`` methods. For example:

```php
/**
 * Handle ingestion of a new custom Kapost object
 * @param {int $blog_id Identifier for the current site
 * @param {array} $content Content from Kapost to apply to the Kapost Object
 * @param {int} $publish 0 or 1 depending on whether to publish the post or not
 * @param {bool} $isPreview Is preview mode or not (defaults to false)
 * @return {string|PhpXmlRpc\Response}
 */
public function newPost($blog_id, $content, $publish, $isPreview) {
    if(array_key_exists('custom_fields', $content) && class_exists('Kapost'.$content['custom_fields']['kapost_custom_type']) && ('Kapost'.$content['custom_fields']['kapost_custom_type']=='KapostResource' || is_subclass_of('Kapost'.$content['custom_fields']['kapost_custom_type'], 'KapostResource'))) {
        $obj=new KapostResource();

        //@TODO Handle populating of your kapost object

        //Write the object
        $obj->write();


        //Validate the incoming content
        $valid=$obj->validate_incoming();
        if(!$valid->valid()) {
            return new PhpXmlRpc\Response(0, 400, $valid->message());
        }

        return (array_key_exists('custom_fields', $content) ? $content['custom_fields']['kapost_post_id']:$className.'_'.$obj->ID);
    }
}

/**
 * Handle ingestion of an edit request for a custom Kapost object
 * @param int $content_id Identifier for the content item
 * @param array $content Content from Kapost to apply to the Kapost Object
 * @param int $publish 0 or 1 depending on whether to publish the post or not
 * @param bool $isPreview Is preview mode or not (defaults to false)
 */
protected function editResource($content_id, $content, $publish, $isPreview=false) {
    if(array_key_exists('custom_fields', $content) && class_exists('Kapost'.$content['custom_fields']['kapost_custom_type']) && ('Kapost'.$content['custom_fields']['kapost_custom_type']=='KapostResource' || is_subclass_of('Kapost'.$content['custom_fields']['kapost_custom_type'], 'KapostResource'))) {
        $obj=KapostResource::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
        if(!empty($obj) && $obj!==false && $obj->exists()) {
            $obj=new KapostResource();
        }

        //@TODO Handle populating of your kapost object

        //Write the object
        $obj->write();


        //Validate the incoming content
        $valid=$obj->validate_incoming();
        if(!$valid->valid()) {
            return new PhpXmlRpc\Response(0, 400, $valid->message());
        }

        return true;
    }
}
```
