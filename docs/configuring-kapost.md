Configuring Kapost
----
### Setting up the app
1. In your Kapost account go to Settings > App Center
2. Click on Install more apps
3. Choose CMS then select XML-RPC, select your instance and click install
4. Fill in the form for the new XML-RPC, you need to use a username/password that has "Kapost API Access" permission or an administrator.
5. In the Endpoint field you need to use ``http://mywebsite.com/kapost-service/`` (you can also use https:// of course), for the url field set that to the home page of your site ``http://mywebsite.com/``.

### Creating the content type in Kapost
1. In Kapost go to Settings > Content Types and ckick the New Type button.
2. In the Field Name field, you need to put ``Page`` by default, if you are using a custom Kapost Object ([see here for instructions]()) you should put the name of your custom type you are using in your code.
3. After filling out the tombstone information for the new type set the primary destination to the App you configured in the process above.
