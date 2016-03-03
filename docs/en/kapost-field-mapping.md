Kapost Field Mapping for Page extensions
----
This is a list of the mapping of the basic fields from Kapost to SilverStripe.

| Kapost        | SilverStripe  |
|---------------|---------------|
| Title | Title (if SS_Title is missing or empty), MenuTitle |
| Content WYSYWIG/Description | Content |
| SS_Title ([custom field](configuring-kapost.md#defining-seo-fields)) | Title, MenuTitle (if Kapost's Title field is empty) |
| SS_MetaDescription ([custom field](configuring-kapost.md#defining-seo-fields)) | MetaDescription |
| SS_KapostConversionNotes ([custom field](configuring-kapost.md#using-conversion-notes)) | *audit display only* |
| kapost_author | *audit display only* |
| kapost_author_avatar | *audit display only* |
| kapost_post_id | KapostRefID |

Kapost Field Mapping for File Media
----
| Kapost | SilverStripe |
|--------|--------------|
| Title | *ignored* |
| Caption | *ignored* |
| Alt Text | Title |
