<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace DOMUtilForWebPTests;

use PHPUnit\Framework\TestCase;
use DOMUtilForWebP\PictureTags;

/*
class PictureTagsCustomReplacer extends PictureTagsTest
{
    public function replaceUrl($url) {
        if (!preg_match('#(png|jpe?g|gif)$#', $url)) {  // here we also accept gif
            return;
        }
        return $url . '.webp';
    }
}

// TODO: Test with pixel density descriptor (ie "2x"). https://developer.mozilla.org/en-US/docs/Web/HTML/Element/source
*/
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
            [
                '<img src="1.png" alt="hello">',
                '<picture><source srcset="1.png.webp" type="image/webp"><img src="1.png" alt="hello" class="webpexpress-processed"></picture>'
            ],
            ['<img srcset="2.jpg 1000w" class="hero">', '<picture><source srcset="2.jpg.webp 1000w" type="image/webp"><img srcset="2.jpg 1000w" class="hero webpexpress-processed"></picture>'],
            ['<img srcset="3.jpg 1000w" src="3.jpg">', '<picture><source srcset="3.jpg.webp 1000w" type="image/webp"><img srcset="3.jpg 1000w" src="3.jpg" class="webpexpress-processed"></picture>'],
            ['<img srcset="3.jpg 1000w, 4.jpg 2000w">', '<picture><source srcset="3.jpg.webp 1000w, 4.jpg.webp 2000w" type="image/webp"><img srcset="3.jpg 1000w, 4.jpg 2000w" class="webpexpress-processed"></picture>'],
            ['<img srcset="5.jpg 1000w, 6.jpg">', '<picture><source srcset="5.jpg.webp 1000w, 6.jpg.webp" type="image/webp"><img srcset="5.jpg 1000w, 6.jpg" class="webpexpress-processed"></picture>'],
            ['<img srcset="7.gif 1000w, 8.jpg">', '<picture><source srcset="8.jpg.webp" type="image/webp"><img srcset="7.gif 1000w, 8.jpg" class="webpexpress-processed"></picture>'],
            ['<img data-lazy-src="9.jpg">', '<picture><source data-lazy-src="9.jpg.webp" type="image/webp"><img data-lazy-src="9.jpg" class="webpexpress-processed"></picture>'],
            ['<img SRC="10.jpg">', '<picture><source srcset="10.jpg.webp" type="image/webp"><img src="10.jpg" class="webpexpress-processed"></picture>'],
            ['<IMG SRC="11.jpg">', '<picture><source srcset="11.jpg.webp" type="image/webp"><img src="11.jpg" class="webpexpress-processed"></picture>'],
            [
              '<figure class="wp-block-image"><img src="12.jpg" alt="" class="wp-image-6" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px"></figure>',
              '<figure class="wp-block-image"><picture><source srcset="12.jpg.webp 492w, 12-300x265.jpg.webp 300w" sizes="(max-width: 492px) 100vw, 492px" type="image/webp"><img src="12.jpg" alt="" class="wp-image-6 webpexpress-processed" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px"></picture></figure>'
            ],
            ['<img srcset="13a.jpg 1x, 13b.jpg 2x" class="hero">', '<picture><source srcset="13a.jpg.webp 1x, 13b.jpg.webp 2x" type="image/webp"><img srcset="13a.jpg 1x, 13b.jpg 2x" class="hero webpexpress-processed"></picture>'],
            [
                "<img src=\"1.png\">\n<img srcset=\"3.jpg 1000w\" src=\"3.jpg\">\n<img data-lazy-src=\"9.jpg\" style=\"border:2px solid red\" class=\"something\">\n<figure class=\"wp-block-image\">\n  <img src=\"12.jpg\" alt=\"\" class=\"wp-image-6\" srcset=\"12.jpg 492w, 12-300x265.jpg 300w\" sizes=\"(max-width: 492px) 100vw, 492px\">\n</figure>",
                "<picture><source srcset=\"1.png.webp\" type=\"image/webp\"><img src=\"1.png\" class=\"webpexpress-processed\"></picture>\n<picture><source srcset=\"3.jpg.webp 1000w\" type=\"image/webp\"><img srcset=\"3.jpg 1000w\" src=\"3.jpg\" class=\"webpexpress-processed\"></picture>\n<picture><source data-lazy-src=\"9.jpg.webp\" type=\"image/webp\"><img data-lazy-src=\"9.jpg\" style=\"border:2px solid red\" class=\"something webpexpress-processed\"></picture>\n<figure class=\"wp-block-image\">\n  <picture><source srcset=\"12.jpg.webp 492w, 12-300x265.jpg.webp 300w\" sizes=\"(max-width: 492px) 100vw, 492px\" type=\"image/webp\"><img src=\"12.jpg\" alt=\"\" class=\"wp-image-6 webpexpress-processed\" srcset=\"12.jpg 492w, 12-300x265.jpg 300w\" sizes=\"(max-width: 492px) 100vw, 492px\"></picture>\n</figure>"
            ],
            [
              '<picture><img src="1.png"</picture><img src="2.png">', // the img inside picture should not be altered
              '<picture><img src="1.png"</picture><picture><source srcset="2.png.webp" type="image/webp"><img src="2.png" class="webpexpress-processed"></picture>'
            ]
        ];


        $theseShouldBeLeftUntouchedTests = [
            '<img src="7.gif">',                // wrong ext
            '<img src="8.jpg.webp">',           // wrong ext again (its the last part that counts)
            '<img src="9.jpg?width=200">',      // leave these guys alone
            '<img src="10.jpglilo">',           // wrong ext once again
            'src="header.jpeg"',                // whats that, not in tag!
            '<script src="http://example.com/script.js?preload=image.jpg">',        // wrong tag
            '<img><script src="http://example.com/script.js?preload=image.jpg">',   // wrong tag
            '<div><picture><source srcset="1.png.webp" type="image/webp"><img src="1.png" alt="hello"></picture></div>hello<picture><img src="2.png"></picture>' // inside picture
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
