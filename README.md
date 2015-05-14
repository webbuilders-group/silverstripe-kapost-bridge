Kapost Bridge
=================
[![Build Status](https://travis-ci.org/webbuilders-group/silverstripe-kapost-bridge.png)](https://travis-ci.org/webbuilders-group/silverstripe-kapost-bridge)

Bridge for Kapost driven content authoring, provides support for basic content pages. But also provides a flexible api to allow for custom content types.

## Maintainer Contact
* Ed Chipman ([UndefinedOffset](https://github.com/UndefinedOffset))

## Requirements
* SilverStripe CMS 3.1.x
* [phpxmlrpc 3.0.x](https://github.com/gggeek/phpxmlrpc)


## Installation
__Composer (recommended):__
```
composer require webbuilders-group/silverstripe-kapost-bridge
```


If you prefer you may also install manually:
* Download the module from here https://github.com/webbuilders-group/silverstripe-kapost-bridge/archive/master.zip
* Extract the downloaded archive into your site root so that the destination folder is called kapost-bridge, opening the extracted folder should contain _config.php in the root along with other files/folders
* Run dev/build?flush=all to regenerate the manifest
* Download phpxmlrpc's [latest 3.0.x release](https://github.com/gggeek/phpxmlrpc/releases) and include in your SilverStripe install note that since you're installing manually you may run into some issues.


## Documentation
The documentation for the module (including how to add custom types and the extension points) can be found [here](docs).


## The Publish Twice Rule (Kapost Analytics)
In order for Kapost Analytics to work properly you need to have the full url. Unfortunatly since we cannot know the end published url before the Kapost Object is converted (or the url changes because of the changes in Kapost) we need to publish from Kapost again after you convert the object in SilverStripe. This will allow SilverStripe to let Kapost know the next time it tries to publish what the actual published url is from SilverStripe instead of the cms url. Technically you could also simply update the url in Kapost as well instead of publishing again after converting but if you do it does not change automatically with the next publish.


## Configuration Options
```yml
KapostAdmin:
    extra_conversion_modes: (empty) #Array of name's for extra conversion modes (see documentation for information on how to define these)
    kapost_base_url: null #Set this to a string of the base url for your Kapost account for example https://example.kapost.com/

KapostService:
    check_user_agent: true #If set to true when the service is called the user agent of the request is checked to see if it is Kapost's XML-RPC user agent
    authenticator_class: "MemberAuthenticator" #Authenticator to be used for authenticating the Kapost account
    authenticator_username_field: "Email" #Field the authenticator is expecting the username to be in
    kapost_media_folder: "kapost-media" #Assets folder to place the Kapost attached media assets
    duplicate_assets: "rename" #What to do with duplicate assets valid options rename, overwrite, ignore see bellow for more information
    preview_expiry: 10 #Preview expiry window in minutes, once this time elapses the Kapost content author must click preview again or they will recieve a 404 message on the site.

KapostConversionHistory:
    expires_days: 30 #Number of days that conversion history records are kept

```

## Making Pages Managed through Kapost Readonly
In some cases you may want to have the Page editing section of the cms have pages marked as readonly when their content came from Kapost. There are are a couple of fields by default that are made editable URL Segment and Extra Meta Additional fields you want editable by the cms admin can be added tp the config option ``YourPageType.non_readonly_fields``. As well Kapost includes a script tag in any WYSYWIG field to avoid confusing your analytics in Kapost and any potential collisions with the CMS's UI you should make any HTML fields safe via the config option ``YourPageType.make_safe_wysiwyg_fields`` by default the Content field is included. To enable the readonly conversion on the CMS's page editing section you need to add the bellow to your config. Note this only applies to the Content tab, the Settings tab is uneffected.

```yml
CMSPageEditController:
    extensions:
        - "KapostPageEditControllerExtension"
```


### Handling Duplicate Assets
Kapost sends an attached asset everytime a page is published, so there are three options for handling files with a duplicate name under the KapostService.duplicate_assets configuration option.

* ``rename`` Rename the asset until a unique name is found
* ``overwrite`` Overwrite the existing file with the new file, _be warned you may end up overwriting an asset you don't want overwritten_.
* ``ignore`` The service simply ignores the asset and tells Kapost that there was an error explaining to rename the file and try again.
