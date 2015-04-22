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


### Handling Duplicate Assets
Kapost sends an attached asset everytime a page is published, so there are three options for handling files with a duplicate name under the KapostService.duplicate_assets configuration option.

* ``rename`` Rename the asset until a unique name is found
* ``overwrite`` Overwrite the existing file with the new file, _be warned you may end up overwriting an asset you don't want overwritten_.
* ``ignore`` The service simply ignores the asset and tells Kapost that there was an error explaining to rename the file and try again.
