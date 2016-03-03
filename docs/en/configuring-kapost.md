Configuring Kapost
----
### Setting up the app
1. In your Kapost account go to Settings > App Center
2. Click on Install more apps
3. Choose CMS then select XML-RPC, select your instance and click install
4. Fill in the form for the new XML-RPC, you need to use a username/password that has "Kapost API Access" permission or an administrator.
5. In the Endpoint field you need to use ``http://mywebsite.com/kapost-service/`` (you can also use https:// of course), for the url field set that to the home page of your site ``http://mywebsite.com/``.

### Creating the content type in Kapost
1. In Kapost go to Settings > Content Types and click the New Type button.
2. In the Field Name field, you need to put ``Page`` by default, if you are using a custom Kapost Object ([see here for instructions](custom-types.md)) you should put the name of your custom type you are using in your code.
3. After filling out the base information for the new type set the primary destination to the App you configured in the process above.


### Defining SEO Fields
Because of how Kapost handles/supports the SEO fields it has built in, this module cannot use or access them. So in order to have Kapost populate SilverStripe's ``MetaDescription`` field as well as have the ``MenuTitle`` be different from ``Title`` for basic pages you need to create two custom fields text fields under Settings > Custom Fields. Set the field name's based on the table bellow.

| Kapost Field Name  | Maps to SilverStripe field |
|--------------------|----------------------------|
| SS_Title           | Title (optional, falls back to Kapost's title field) |
| SS_MetaDescription | MetaDescription            |


### Using Conversion Notes
Sometimes you have content types that need some additional information from the Kapost Author. Maybe this information is as simple as where a page appears in the tree or maybe it's more complex if you have additional functionality. To do this you can add a custom field (Settings > Custom Fields in Kapost) to your content types with a field name of ``SS_KapostConversionNotes``. When this field is handled by the Kapost Bridge it will appear in the conversion dialog as an icon of the Kapost Author's avatar. When that icon is clicked a balloon will open containing the notes from the Kapost Author. This field is by default discarded after the conversion is completed.
