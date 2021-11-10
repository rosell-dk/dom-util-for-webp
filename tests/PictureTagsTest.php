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

    public static function runPictureTagsTest($me, $html, $expectedOutput)
    {
        $output = PictureTags::replace($html);
        $me->assertEquals($expectedOutput, $output);
    }

    public function testBasic()
    {
        // most basic (src)
        self::runPictureTagsTest($this,
            '<img src="1.png" alt="hello">',
            '<picture>' .
                '<source srcset="1.png.webp" type="image/webp">' .
                '<img src="1.png" alt="hello" class="webpexpress-processed">' .
            '</picture>'
        );
    }

    public function testSrcAndSrcSet()
    {
        // both src and srcset - also very common
        self::runPictureTagsTest($this,
            '<img srcset="src-and-srcset.jpg 1000w" src="3.jpg">',
            '<picture>' .
                '<source srcset="src-and-srcset.jpg.webp 1000w" type="image/webp">' .
                '<img srcset="src-and-srcset.jpg 1000w" src="3.jpg" class="webpexpress-processed">' .
            '</picture>'
        );
    }

    public function testTheRest()
    {
        // TODO: Create individual methods for these tests, like above - eases finding out which that fails
        $tests = [
            [
                // sizes attribute must be copied to source element and kept on img element
                '<img srcset="sizes.jpg 1000w" src="3.jpg" sizes="(max-width: 492px) 100vw, 492px">',
                '<picture>' .
                    '<source srcset="sizes.jpg.webp 1000w" sizes="(max-width: 492px) 100vw, 492px" type="image/webp">' .
                    '<img srcset="sizes.jpg 1000w" src="3.jpg" sizes="(max-width: 492px) 100vw, 492px" class="webpexpress-processed">' .
                '</picture>'
            ],
            [
                // srcset contains images that are not available as webp. These are removed from srcset on the source element
                '<img srcset="1.jpg 1000w, 2.gif 999w" src="3.jpg">',
                '<picture>' .
                    '<source srcset="1.jpg.webp 1000w" type="image/webp">' .
                    '<img srcset="1.jpg 1000w, 2.gif 999w" src="3.jpg" class="webpexpress-processed">' .
                '</picture>'
            ],
            /*[
                // TODO: when both sizes and data-sizes are set, copy both to source element
                '<img srcset="sizes.jpg 1000w" src="3.jpg" sizes="(max-width: 50px) 50vw, 150px" data-sizes="(max-width: 492px) 100vw, 492px">',
                '<picture><source srcset="sizes.jpg.webp 1000w" sizes="(max-width: 50px) 50vw, 150px" data-sizes="(max-width: 492px) 100vw, 492px" type="image/webp"><img srcset="sizes.jpg 1000w" src="3.jpg" sizes="(max-width: 50px) 50vw, 150px" data-sizes="(max-width: 492px) 100vw, 492px" class="webpexpress-processed"></picture>'
            ],*/
            [
                // when both srcset and data-srcset are set, keep both (was fixed in #26)
                '<img srcset="1.jpg 480w, 2.jpg 800w" data-lazy-srcset="1.jpg 480w, 2.jpg 800w">',
                '<picture><source srcset="1.jpg.webp 480w, 2.jpg.webp 800w" data-lazy-srcset="1.jpg.webp 480w, 2.jpg.webp 800w" type="image/webp"><img srcset="1.jpg 480w, 2.jpg 800w" data-lazy-srcset="1.jpg 480w, 2.jpg 800w" class="webpexpress-processed"></picture>'
            ],
            [
                // skip an image if its "src" attribute has a data url (svg+xml has given error)
                '<img src="data:image/svg+xml,...jpg">',
                '<img src="data:image/svg+xml,...jpg">'
            ],
            [
                // remove "data:" urls from srcset in source
                '<img srcset="1.jpg 100w, data:image/gif;base64,R0lGOD.jpg 777w" src="3.jpg">',
                '<picture>' .
                    '<source srcset="1.jpg.webp 100w" type="image/webp">' .
                    '<img srcset="1.jpg 100w, data:image/gif;base64,R0lGOD.jpg 777w" src="3.jpg" class="webpexpress-processed">' .
                '</picture>'
            ],
            [
                // existing classes on the img must be kept
                '<img srcset="2.jpg 1000w" class="hero">',
                '<picture><source srcset="2.jpg.webp 1000w" type="image/webp"><img srcset="2.jpg 1000w" class="hero webpexpress-processed"></picture>'
            ],
            [
                // multiple sizes in srcset
                '<img srcset="3.jpg 1000w, 4.jpg 2000w">',
                '<picture><source srcset="3.jpg.webp 1000w, 4.jpg.webp 2000w" type="image/webp"><img srcset="3.jpg 1000w, 4.jpg 2000w" class="webpexpress-processed"></picture>'
            ],
            [
                // multiple images in srcset, one of them missing width. Missing width should be kept
                '<img srcset="5.jpg 1000w, 6.jpg">',
                '<picture><source srcset="5.jpg.webp 1000w, 6.jpg.webp" type="image/webp"><img srcset="5.jpg 1000w, 6.jpg" class="webpexpress-processed"></picture>'
            ],
            [
                // we have invalid html, as src is required. Best not to mess with invalid html, so no replacement!
                '<img data-lazy-src="no-src-attr-in-img.jpg">',
                '<img data-lazy-src="no-src-attr-in-img.jpg">',
                //'<picture><source data-lazy-src="9.jpg.webp" type="image/webp"><img data-lazy-src="9.jpg" class="webpexpress-processed"></picture>'
            ],
            [
                // also invalid html, ignored!
                '<img data-lazy-srcset="1.jpg 480w, 2.jpg 800w">',
                '<img data-lazy-srcset="1.jpg 480w, 2.jpg 800w">'
            ],
            [
                // minor: upperase attribute SRC will become lowercase...
                '<img SRC="uppercase1.jpg">',
                '<picture><source srcset="uppercase1.jpg.webp" type="image/webp"><img src="uppercase1.jpg" class="webpexpress-processed"></picture>'
            ],
            [
                // minor: uppercase tag name also becomes lowercase
                '<IMG SRC="uppercase2.jpg">',
                '<picture><source srcset="uppercase2.jpg.webp" type="image/webp"><img src="uppercase2.jpg" class="webpexpress-processed"></picture>'
            ],
            [
                // real-world example (wordpress)
                '<figure class="wp-block-image"><img src="12.jpg" alt="" class="wp-image-6" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px"></figure>',
                '<figure class="wp-block-image"><picture><source srcset="12.jpg.webp 492w, 12-300x265.jpg.webp 300w" sizes="(max-width: 492px) 100vw, 492px" type="image/webp"><img src="12.jpg" alt="" class="wp-image-6 webpexpress-processed" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px"></picture></figure>'
            ],
            ['<img srcset="13a.jpg 1x, 13b.jpg 2x" class="hero">', '<picture><source srcset="13a.jpg.webp 1x, 13b.jpg.webp 2x" type="image/webp"><img srcset="13a.jpg 1x, 13b.jpg 2x" class="hero webpexpress-processed"></picture>'],
            [
                // the img inside picture should not be become yet another picture tag.
                // right now, anything in picture tags are left untouched.
                // however, we should add source(s). See #25
                '<picture><img src="img-in-existing-picture.png"></picture>',
                '<picture><img src="img-in-existing-picture.png"></picture>'
            ],
            [
                '<img loading="lazy" width="1055" height="700" src="https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80.jpg" alt="" title="Patricia-80" srcset="https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80.jpg 1055w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-980x650.jpg 980w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-480x318.jpg 480w" sizes="(min-width: 0px) and (max-width: 480px) 480px, (min-width: 481px) and (max-width: 980px) 980px, (min-width: 981px) 1055px, 100vw" class="wp-image-191978" />',
                '<picture>' .
                  '<source srcset="https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80.jpg.webp 1055w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-980x650.jpg.webp 980w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-480x318.jpg.webp 480w" sizes="(min-width: 0px) and (max-width: 480px) 480px, (min-width: 481px) and (max-width: 980px) 980px, (min-width: 981px) 1055px, 100vw" type="image/webp">' .
                  '<img loading="lazy" width="1055" height="700" src="https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80.jpg" alt="" title="Patricia-80" srcset="https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80.jpg 1055w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-980x650.jpg 980w, https://techpoint.africa/wp-content/uploads/2021/08/Patricia-80-480x318.jpg 480w" sizes="(min-width: 0px) and (max-width: 480px) 480px, (min-width: 481px) and (max-width: 980px) 980px, (min-width: 981px) 1055px, 100vw" class="wp-image-191978 webpexpress-processed">' .
                  '</picture>'
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
