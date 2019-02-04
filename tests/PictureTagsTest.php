<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace DOMUtilForWebPTests;

use PHPUnit\Framework\TestCase;
use Sunra\PhpSimple\HtmlDomParser;
use DOMUtilForWebP\PictureTags;


class PictureTagsCustomReplacer extends PictureTagsTest
{
    public function replaceUrl($url) {
        if (!preg_match('#(png|jpe?g|gif)$#', $url)) {  // here we also accept gif
            return;
        }
        return $url . '.webp';
    }
}

class PictureTagsTest extends TestCase
{

    public function testUntouched()
    {

        // Here we basically test that the DOM manipulation tool is gentle and doesn't alter
        // anything other that what it is told to.

        $untouchedTests = [
            'a', 'a',
            'a<p></p>b<p></p>c',
            '',
            '<body!><p><!-- bad html here!--></p></a>',
            '<img src="3.jpg.tiff">',
            //'<img src="http://example.com/4.jpeg" alt="">',
            '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US"><head profile="http://gmpg.org/xfn/11"><meta charset="utf-8" /></head><body></body></html>',
            '<H1>hi</H1>',
            'blah<BR>blah<br>blah',
            "<pre>hello\nline</pre>",
            "① <p>并來朝貢</p>"
        ];

        foreach ($untouchedTests as $html) {
            $this->assertEquals($html, PictureTags::replace($html));
        }
    }

    public function testWholeEngine()
    {
        $tests = [
            ['<img src="1.png">', '<picture class="webpexpress-processed"><source src="1.png.webp" type="image/webp"><source src="1.png"><img src="1.png" class="webpexpress-processed"></picture>'],
            ['<img srcset="2.jpg 1000w">', '<picture class="webpexpress-processed"><source srcset="2.jpg.webp 1000w" type="image/webp"><source srcset="2.jpg 1000w"><img srcset="2.jpg 1000w" class="webpexpress-processed"></picture>'],
            ['<img srcset="3.jpg 1000w" src="3.jpg">', '<picture class="webpexpress-processed"><source srcset="3.jpg.webp 1000w" type="image/webp"><source srcset="3.jpg 1000w"><img srcset="3.jpg 1000w" src="3.jpg" class="webpexpress-processed"></picture>'],
            ['<img srcset="3.jpg 1000w, 4.jpg 2000w">', '<picture class="webpexpress-processed"><source srcset="3.jpg.webp 1000w, 4.jpg.webp 2000w" type="image/webp"><source srcset="3.jpg 1000w, 4.jpg 2000w"><img srcset="3.jpg 1000w, 4.jpg 2000w" class="webpexpress-processed"></picture>'],
            ['<img srcset="5.jpg 1000w, 6.jpg">', '<picture class="webpexpress-processed"><source srcset="5.jpg.webp 1000w, 6.jpg.webp" type="image/webp"><source srcset="5.jpg 1000w, 6.jpg"><img srcset="5.jpg 1000w, 6.jpg" class="webpexpress-processed"></picture>'],
            ['<img srcset="7.gif 1000w, 8.jpg">', '<picture class="webpexpress-processed"><source srcset="8.jpg.webp" type="image/webp"><source srcset="7.gif 1000w, 8.jpg"><img srcset="7.gif 1000w, 8.jpg" class="webpexpress-processed"></picture>'],
            ['<img data-lazy-src="9.jpg">', '<picture class="webpexpress-processed"><source data-lazy-src="9.jpg.webp" type="image/webp"><source data-lazy-src="9.jpg"><img data-lazy-src="9.jpg" class="webpexpress-processed"></picture>'],
            ['<img SRC="10.jpg">', '<picture class="webpexpress-processed"><source src="10.jpg.webp" type="image/webp"><source src="10.jpg"><img src="10.jpg" class="webpexpress-processed"></picture>'],
            ['<IMG SRC="11.jpg">', '<picture class="webpexpress-processed"><source src="11.jpg.webp" type="image/webp"><source src="11.jpg"><img src="11.jpg" class="webpexpress-processed"></picture>'],
        ];


        $theseShouldBeLeftUntouchedTests = [
            '<img src="7.gif">',                // wrong ext
            '<img src="8.jpg.webp">',           // wrong ext again (its the last part that counts)
            '<img src="9.jpg?width=200">',      // leave these guys alone
            '<img src="10.jpglilo">',           // wrong ext once again
            'src="header.jpeg"',                // whats that, not in tag!
            '<script src="http://example.com/script.js?preload=image.jpg">',        // wrong tag
            '<img><script src="http://example.com/script.js?preload=image.jpg">',   // wrong tag
        ];

        foreach ($theseShouldBeLeftUntouchedTests as $skipThis) {
            $tests[] = [$skipThis, $skipThis];
        }

        foreach ($tests as list($html, $expectedOutput)) {
            $output = PictureTags::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }
}
