Extending the Basics
----
This module provides support for basic page types in SilverStripe, aka the default Page class in a raw installer. This documentation will help you get to the point were you have custom page types as well as support for more advanced extensions such as non-page data objects. It assumes you understand how to use the SilverStripe [extensions api](http://docs.silverstripe.org/en/developer_guides/extending/extensions/).


### Available Extension Points
#### KapostService
 - ``newPost($blog_id, array $content, int $publish)`` Allows for overriding of the metaWeblog.newPost handling & response. What is returned to Kapost is the first non-empty result from extensions. This should return the post's id (for example KapostPage_10) on success, throw an error response or return null. See the [custom types documentation](custom-types.md) for more information.
 - ``updateNewKapostPage(KapostObject $obj, $blog_id, array $content, int $publish)`` Allows for setting of custom page extension fields based on the data from Kapost. Note that *you must call write* on the KapostObject to save your changes.
 - ``editPost($content_id, array $content, int $publish)`` Allows for overriding of the metaWeblog.editPost handling & response. What is returned to Kapost is the first non-empty result from extensions. This should return true on success, throw an error response or return null.
 - ``updateEditKapostPage(KapostObject $kapostObj, $content_id, array $content, int $publish)`` Allows for setting of custom page extension fields based on the data from Kapost. Note that *you must call write* on the KapostObject to save your changes.
 - ``getPost($content_id)`` Allows for overriding the metaWeblog.getPost handling & response to Kapost about the requested content. This can be used for sending information about non-page extension Kapost objects it should return an array similar to what is [defined here](https://gist.github.com/icebreaker/546f4223dc07a9e2e6e9#metawebloggetpost). What is returned to Kapost is the first non-empty result from extensions.
 - ``updatePageMeta(Page $page)`` Allows for modification of the page meta data to be added to the response to Kapost. This should return a map of field's to it's content.
 - ``getCategories($blog_id)`` Allows for adding in of additional categories to be sent to Kapost, this should return an array of categories similar to what is [defined here](https://gist.github.com/icebreaker/546f4223dc07a9e2e6e9#metawebloggetcategories).
 - ``updateNewMediaAsset($blog_id, array $content, File $mediaFile)`` Allows for modification of the File object that represents the media asset from Kapost.

#### KapostObject
 - ``updateCMSFields(FieldList $fields)`` Allows extensions to add cms fields to KapostObject and it's decedents.

#### KapostGridFieldDetailForm_ItemRequest
 - ``updateConvertObjectForm(Form $form, KapostObject $source)`` Allows extensions to adjust the form in the Convert Object lightbox.
 - ``doConvert{conversion_mode}(KapostObject $source, array $data, Form $form)`` Allows extensions to provide handing of conversions of custom KapostObject extensions. ``conversion_mode`` is replaced by the mode in the request, you must explicitly allow this mode in the KapostAdmin.extra_conversion_modes configuration option. This extension point should return the cms relative url to edit the final object (ex. admin/pages/edit/show/1) and it is handled on a first returned basis. When the lightbox closes the user will be directed to this url in the cms.
 - ``updateNewPageConversion(Page $destination, KapostObject $source, array $data, Form $form)`` Allows extensions to alter the destination page when creating a new page from the Kapost Object. Note that you *must call write on the page* to save your changes.
 - ``updateReplacePageConversion(Page $destination, KapostObject $source, array $data, Form $form)`` Allows extensions to alter the destination page when replacing a new page with the Kapost Object. Note that you *must call write on the page* to save your changes.