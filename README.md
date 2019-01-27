# dom-util-for-webp
Replace image URLs found in HTML

This library can do two things:

1) Replace image URLs in HTML
2) Replace *img* tags with *picture* tags, adding webp versions to sources (TODO)


## Replacing image URLs in HTML

The *ImageUrlReplacer* class allows you to change all image URLs in a piece of HTML.

The behaviour can be modified setting public properties such as *$urlReplacerFunction* and *$attributeFilterFunction*. This behaviour will likely change in 0.2, where we change to classic OOP (to change behaviour, you extend the class)

Default behaviour:
- The modified URL is the same as the original, with ".webp" appended                   ($urlReplacerFunction)
- Limits to these tags: <img>, <source>, <input> and <iframe>                           ($searchInTags)
- Limits to these attributes: "src", "src-set" and any attribute starting with "data-"  ($attributeFilterFunction)
- Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either)    ($urlReplacerFunction)
