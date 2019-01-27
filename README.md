# dom-util-for-webp
Replace image URLs found in HTML

This library can do two things:

1) Replace image URLs in HTML
2) Replace *img* tags with *picture* tags, adding webp versions to sources (TODO)


## Replacing image URLs in HTML

The *ImageUrlReplacer* class allows you to change all image URLs in a piece of HTML.

The behaviour can be modified by overriding public methods such as *replaceUrl*

Default behaviour:
- The modified URL is the same as the original, with ".webp" appended (to change, override the `replaceUrl` function)
- Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either) (to change, override the `replaceUrl` function)
- Attribute search/replace limits to these tags: &lt;img&gt;, &lt;source>, &lt;input&gt; and &lt;iframe&gt; (to change, override the `$searchInTags` property)
- Attribute search/replace limits to these attributes: "src", "src-set" and any attribute starting with "data-" (to change, override the `attributeFilter` function)
- Urls inside styles are replaced too (*background-image* and *background* properties)

Example:

*Input:*
```html
<img src="image.jpg">
<img src="1.jpg" srcset="2.jpg 1000w">
<div style="background-image: url('image.jpeg')">
<style>
#image {
    background-image: url(image.jpeg);
}
</style>
```

*Output*:
```html
<img src="image.jpg.webp">
<img src="1.jpg.webp" srcset="2.jpg.webp 1000w">
<div style="background-image: url('image.jpeg.webp')">
<style>
#image {
    background-image: url("image.jpeg.webp");
}
</style>
```
