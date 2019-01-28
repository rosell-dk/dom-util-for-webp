# dom-util-for-webp
Replace image URLs found in HTML

This library can do two things:

1) Replace image URLs in HTML
2) Replace *&lt;img&gt;* tags with *&lt;picture&gt;* tags, adding webp versions to sources (TODO)


## Replacing image URLs in HTML

The *ImageUrlReplacer::replace($html)* method accepts a piece of HTML and returns HTML where where all image URLs have been replaced.

```php
$modifiedHtml = ImageUrlReplacerCustomReplacer::replace($html);
```

*Input:*
```html
<img src="image.jpg">
<img src="1.jpg" srcset="2.jpg 1000w">
<picture>
    <source src="1.jpg">
    <source src="2.png">
    <source src="3.gif"> <!-- gifs are skipped in default behaviour -->
    <source src="4.jpg?width=200"> <!-- urls with query string are skipped in default behaviour -->
</picture>
<div style="background-image: url('image.jpeg')"></div>
<style>
#hero {
    background: lightblue url("image.png") no-repeat fixed center;;
}
</style>
<input type="button" src="1.jpg">
<img data-src="image.jpg"> <!-- any attribute starting with "data-" are replaced (if it ends with "jpg", "jpeg" or "png"). For lazy-loading -->
```

*Output*:
```html
<img src="image.jpg.webp">
<img src="1.jpg.webp" srcset="2.jpg.webp 1000w">
<picture>
    <source src="1.jpg.webp">
    <source src="2.jpg.webp">
    <source src="3.gif"> <!-- gifs are skipped in default behaviour -->
    <source src="4.jpg?width=200"> <!-- urls with query string are skipped in default behaviour -->
</picture>
<div style="background-image: url('image.jpeg.webp')"></div>
<style>
#hero {
    background: lightblue url("image.png.webp") no-repeat fixed center;;
}
</style>
<input type="button" src="1.jpg.webp">
<img data-src="image.jpg.webp"> <!-- any attribute starting with "data-" are replaced (if it ends with "jpg", "jpeg" or "png"). For lazy-loading -->
```

Default behaviour of *ImageUrlReplacer::replace*:
- The modified URL is the same as the original, with ".webp" appended (to change, override the `replaceUrl` function)
- Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either) (to change, override the `replaceUrl` function)
- Attribute search/replace limits to these tags: *&lt;img&gt;*, *&lt;source&gt;*, *&lt;input&gt;* and *&lt;iframe&gt;* (to change, override the `$searchInTags` property)
- Attribute search/replace limits to these attributes: "src", "src-set" and any attribute starting with "data-" (to change, override the `attributeFilter` function)
- Urls inside styles are replaced too (*background-image* and *background* properties)

The behaviour can be modified by extending *ImageUrlReplacer* and overriding public methods such as *replaceUrl*

### Example: Customized behaviour

```php
class ImageUrlReplacerCustomReplacer extends ImageUrlReplacer
{
    public function replaceUrl($url) {
        // Only accept urls ending with "png", "jpg", "jpeg"  and "gif"
        if (!preg_match('#(png|jpe?g|gif)$#', $url)) {
            return;
        }

        // Only accept full urls (beginning with http:// or https://)
        if (!preg_match('#^https?://#', $url)) {
            return;
        }

        // Simply append ".webp" after current extension.
        // This strategy ensures that "logo.jpg" and "logo.gif" gets counterparts with unique names
        return $url . '.webp';
    }

    public function attributeFilter($attrName) {
        // Don't allow any "data-" attribute, but limit to attributes that smells like they are used for images
        // The following rule matches all attributes used for lazy loading images that we know of
        return preg_match('#^(src|srcset|(data-[^=]*(lazy|small|slide|img|large|src|thumb|source|set|bg-url)[^=]*))$#i', $attrName);

        // If you want to limit it further, only allowing attributes known to be used for lazy load,
        // use the following regex instead:
        //return preg_match('#^(src|srcset|data-(src|srcset|cvpsrc|cvpset|thumb|bg-url|large_image|lazyload|source-url|srcsmall|srclarge|srcfull|slide-img|lazy-original))$#i', $attrName);
    }
}

$modifiedHtml = ImageUrlReplacerCustomReplacer::replace($html);
```




## Replacing *&lt;img&gt;* tags with *&lt;picture&gt;* tags, adding webp versions to sources (TODO)
