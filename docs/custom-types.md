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


        if($source instanceof KapostResource) {
            $resource=Resource::get()->filter('KapostRefID', Convert::raw2sql($ource->KapostRefID))->first();
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
 * @param {}
public function doConvertReplaceResource(KapostObject $source, $data, Form $form) {
    //@TODO Handle merging of the Kapost object to the existing resource, do not return anything if there is an error. Simply set the error on the form for the user

    return 'admin/resources/Resource/EditForm/field/Resource/item/'.$destination->ID.'/edit';
}


/**
 * Used to use the Kapost object to create a new resource
 * @param {}
public function doConvertNewResource(KapostObject $source, $data, Form $form) {
    //@TODO Handle converting of the Kapost object to the existing resource, do not return anything if there is an error. Simply set the error on the form for the user

    return 'admin/resources/Resource/EditForm/field/Resource/item/'.$destination->ID.'/edit';
}
```

That should be it, you should now have a new custom object that can have it's content delivered from Kapost.
