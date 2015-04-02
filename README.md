Kapost Bridge
=================
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

KapostService:
    authenticator_class: "MemberAuthenticator" #Authenticator to be used for authenticating the Kapost account
    authenticator_username_field: "Email" #Field the authenticator is expecting the username to be in
    kapost_media_folder: "kapost-media" #Assets folder to place the Kapost attached media assets

```
