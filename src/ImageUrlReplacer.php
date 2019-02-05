<?php

namespace DOMUtilForWebP;

//use Sunra\PhpSimple\HtmlDomParser;

/**
 *  Highly configurable class for replacing image URLs in HTML (both src and srcset syntax)
 *
 *  Based on http://simplehtmldom.sourceforge.net/ - a library for easily manipulating HTML by means of a DOM.
 *  The great thing about this library is that it supports working on invalid HTML and it only applies the changes you
 *  make - very gently.
 *
 * TODO: Check out how ewww does it
 *
 *  Behaviour can be customized by overriding the public methods (replaceUrl, $searchInTags, etc)
 *
 *  Default behaviour:
 *  - The modified URL is the same as the original, with ".webp" appended                   (replaceUrl)
 *  - Limits to these tags: <img>, <source>, <input> and <iframe>                           ($searchInTags)
 *  - Limits to these attributes: "src", "src-set" and any attribute starting with "data-"  (attributeFilter)
 *  - Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either)    (replaceUrl)
 *
 *
 */
class ImageUrlReplacer
{

    public static $searchInTags = ['img', 'source', 'input', 'iframe'];

    /**
     *
     * @return webp url or, if URL should not be changed, return nothing
     **/
    public function replaceUrl($url)
    {
        if (!preg_match('#(png|jpe?g)$#', $url)) {
            return;
        }
        return $url . '.webp';
    }

    public function replaceUrlOr($url, $returnValueIfDenied)
    {
        $url = $this->replaceUrl($url);
        return (isset($url) ? $url : $returnValueIfDenied);
    }

    /*
    public function isValidUrl($url)
    {
        return preg_match('#(png|jpe?g)$#', $url);
    }*/

    public function handleSrc($attrValue)
    {
        return $this->replaceUrlOr($attrValue, $attrValue);
    }

    public function handleSrcSet($attrValue)
    {
        // $attrValue is ie: <img data-x="1.jpg 1000w, 2.jpg">
        $srcsetArr = explode(',', $attrValue);
        foreach ($srcsetArr as $i => $srcSetEntry) {
            // $srcSetEntry is ie "image.jpg 520w", but can also lack width, ie just "image.jpg"
            $srcSetEntry = trim($srcSetEntry);
            $entryParts = preg_split('/\s+/', $srcSetEntry);
            if (count($entryParts) == 2) {
                list($src, $width) = $entryParts;
            } else {
                $src = $srcSetEntry;
                $width = null;
            }

            $webpUrl = $this->replaceUrlOr($src, false);
            if ($webpUrl !== false) {
                $srcsetArr[$i] = $webpUrl . (isset($width) ? ' ' . $width : '');
            }
        }
        return implode(', ', $srcsetArr);
    }

    public function looksLikeSrcSet($value)
    {
        if (preg_match('#\s\d*w#', $value)) {
            return true;
        }
        return false;
    }

    public function handleAttribute($value)
    {
        if (self::looksLikeSrcSet($value)) {
            return self::handleSrcSet($value);
        }
        return self::handleSrc($value);
    }

    public function attributeFilter($attrName)
    {
        $attrName = strtolower($attrName);
        if (($attrName == 'src') || ($attrName == 'srcset') || (strpos($attrName, 'data-') === 0)) {
            return true;
        }
        return false;
    }

    public function processCSSRegExCallback($matches)
    {
        list($all, $pre, $quote, $url, $post) = $matches;
        return $pre . $this->replaceUrlOr($url, $url) . $post;
    }

    public function processCSS($css)
    {
        $declarations = explode(';', $css);
        foreach ($declarations as $i => &$declaration) {
            if (preg_match('#(background(-image)?)\\s*:#', $declaration)) {
                // https://regexr.com/46qdg
                //$regex = '#(url\s*\(([\"\']?))([^\'\";\)]*)(\2\s*\))#';
                $parts = explode(',', $declaration);
                //print_r($parts);
                foreach ($parts as &$part) {
                    //echo 'part:' . $part . "\n";
                    $regex = '#(url\\s*\\(([\\"\\\']?))([^\\\'\\";\\)]*)(\\2\\s*\\))#';
                    $part = preg_replace_callback($regex, 'self::processCSSRegExCallback', $part);
                    //echo 'result:' . $part . "\n";
                }
                $declarations[$i] = implode($parts, ',');
            }
        }
        return implode(';', $declarations);
    }

    public function replaceHtml($html)
    {
        if ($html == '') {
            return '';
        }

        // https://stackoverflow.com/questions/4812691/preserve-line-breaks-simple-html-dom-parser

        // function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET,
        //    $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)

        //$dom = HtmlDomParser::str_get_html($html, false, false, 'UTF-8', false);
        $dom = str_get_html($html, false, false, 'UTF-8', false);

        // Replace attributes (src, srcset, data-src, etc)
        foreach (self::$searchInTags as $tagName) {
            $elems = $dom->find($tagName);
            foreach ($elems as $index => $elem) {
                $attributes = $elem->getAllAttributes();
                foreach ($elem->getAllAttributes() as $attrName => $attrValue) {
                    if ($this->attributeFilter($attrName)) {
                        $elem->setAttribute($attrName, $this->handleAttribute($attrValue));
                    }
                }
            }
        }

        // Replace <style> elements
        $elems = $dom->find('style');
        foreach ($elems as $index => $elem) {
            $css = $this->processCSS($elem->innertext);
            if ($css != $elem->innertext) {
                $elem->innertext = $css;
            }
        }

        // Replace "style attributes
        $elems = $dom->find('*[style]');
        foreach ($elems as $index => $elem) {
            $css = $this->processCSS($elem->style);
            if ($css != $elem->style) {
                $elem->style = $css;
            }
        }

        return $dom->save();
    }

    /* Main replacer function */
    public static function replace($html)
    {
        if (!function_exists('str_get_html')) {
            require_once 'simple_html_dom/simple_html_dom.inc';
        }
        $iur = new static();
        return $iur->replaceHtml($html);
    }
}
