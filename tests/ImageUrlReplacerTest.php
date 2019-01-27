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
use DOMUtilForWebP\ImageUrlReplacer;

class ImageUrlReplacerPassThrough extends ImageUrlReplacer
{
    public function handleAttribute($attrValue) {
        return $attrValue;
    }
}

class ImageUrlReplacerStar extends ImageUrlReplacer
{
    public function handleAttribute($attrValue) {
        return "*";
    }
}

class ImageUrlReplacerAppendWebP extends ImageUrlReplacer
{
    public function handleAttribute($attrValue) {
        return $attrValue . '.webp';
    }
}

class ImageUrlReplacerLooksLikeSrcSet extends ImageUrlReplacer
{
    public function handleAttribute($attrValue) {
        return $this->looksLikeSrcSet($attrValue) ? 'yes' : 'no';
    }
}

class ImageUrlReplacerCustomReplacer extends ImageUrlReplacer
{
    public function replaceUrl($url) {
        return $url . '.***';
    }
}

class ImageUrlReplacerCustomReplacer2 extends ImageUrlReplacer
{
    public function replaceUrl($url) {
        if (!preg_match('#(png|jpe?g|gif)$#', $url)) {  // here we also accept gif
            return;
        }
        return $url . '.webp';
    }
}


class ImageUrlReplacerTest extends TestCase
{

    public function testUntouched()
    {

        // Here we basically test that the DOM manipulation tool is gentle and doesn't alter
        // anything other that what it is told to.

        $untouchedTests = [
            'a', 'a',
            'a<p></p>b<p></p>c',
            '',
            '<body!><p><!-- bad html here!--><img src="http://example.com/2.jpg"></p></a>',
            '<img src="/3.jpg">',
            '<img src="http://example.com/4.jpeg" alt="">',
            '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US"><head profile="http://gmpg.org/xfn/11"><meta charset="utf-8" /></head><body></body></html>',
            '<H1>hi</H1>',
            'blah<BR>blah<br>blah',
            "<pre>hello\nline</pre>",
            "① <p>并來朝貢</p>"
        ];

        foreach ($untouchedTests as $html) {
            $this->assertEquals($html, ImageUrlReplacerPassThrough::replace($html));
        }
    }

    public function testBasic1()
    {

        $starTests = [
            ['<img src="http://example.com/1.jpg">', '<img src="*">'],
            ['<body!><p><!-- bad html here!--><img src="http://example.com/2.jpg"></p></a>', '<body!><p><!-- bad html here!--><img src="*"></p></a>'],
            ['<img src="/3.jpg">', '<img src="*">'],
            ['<img src="http://example.com/4.jpeg" alt="">', '<img src="*" alt="">'],
            ['', ''],
            ['a', 'a'],
            ['a<p></p>b<p></p>c', 'a<p></p>b<p></p>c'],
            ['<img src="xx" alt="并來朝貢">', '<img src="*" alt="并來朝貢">'],
            ['<并來><img data-x="aoeu"></并來>', '<并來><img data-x="*"></并來>'],
        ];

        foreach ($starTests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacerStar::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }


    public function testBasic2()
    {
        $appendWebPTests = [
            ['<img src="http://example.com/1.jpg">', '<img src="http://example.com/1.jpg.webp">'],
            ['<img src="3.jpg"><img src="4.jpg">', '<img src="3.jpg.webp"><img src="4.jpg.webp">'],
            ['<img src="5.jpg" data-src="6.jpg">', '<img src="5.jpg.webp" data-src="6.jpg.webp">'],
            ['<img src="7.jpg" data-cvpsrc="8.jpg">', '<img src="7.jpg.webp" data-cvpsrc="8.jpg.webp">'],
            ['<img src="/5.jpg">', '<img src="/5.jpg.webp">'],
            ['<img src="/6.jpg"/>', '<img src="/6.jpg.webp"/>'],
            ['<img src = "/7.jpg">', '<img src = "/7.jpg.webp">'],
            ['<img src=/8.jpg alt="">', '<img src=/8.jpg.webp alt="">'],
            ['<img src=/9.jpg>', '<img src=/9.jpg.webp>'],
            ['<img src=/10.jpg alt="hello">', '<img src=/10.jpg.webp alt="hello">'],
            ['<img src=/11.jpg />', '<img src=/11.jpg.webp />'],
            //['<img src=/12_.jpg/>', '<img src=/12_.jpg.webp>'],
            ['<input type="image" src="/flamingo13.jpg">', '<input type="image" src="/flamingo13.jpg.webp">'],
            ['<iframe src="/image14.jpg"></iframe>', '<iframe src="/image14.jpg.webp"></iframe>'],
            ['<img data-cvpsrc="/15.jpg">', '<img data-cvpsrc="/15.jpg.webp">'],
            ['<picture><source src="16.jpg"><img src="17.jpg"></picture>', '<picture><source src="16.jpg.webp"><img src="17.jpg.webp"></picture>'],
            ['<img src="18.jpg" srcset="19.jpg 1000w">', '<img src="18.jpg.webp" srcset="19.jpg 1000w.webp">'],
            ['', ''],
//            ['<img src="http://example.com/102.jpg" srcset="http://example.com/103.jpg 1000w">', '<img src="http://example.com/102.jpg.webp" srcset="http://example.com/103.jpg.webp 1000w">']
        ];

        foreach ($appendWebPTests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacerAppendWebP::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }


    public function testSrcSetDetection()
    {

        $isSrcSettests = [
            ['<img data-x="1.jpg 1000w">', '<img data-x="yes">'],
            ['<img data-x="2.jpg">', '<img data-x="no">'],
            ['<img src="3.jpg" data-x="/4.jpg 1000w,/header.jpg 1000w, /header.jpg 2000w">', '<img src="no" data-x="yes">'],
            ['<img data-x="5.jpg 1000w, 6.jpg">', '<img data-x="yes">'],
        ];

        foreach ($isSrcSettests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacerLooksLikeSrcSet::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }


    public function testCustomUrlReplacer()
    {
        $tests = [
            ['<img data-x="1.jpg">', '<img data-x="1.jpg.***">'],
        ];

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacerCustomReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function testCustomUrlReplacer2()
    {
        $tests = [
            ['<img src="1.gif">', '<img src="1.gif.webp">'],    // gif is alright with in this custom validator
            ['<img src="1.buff">', '<img src="1.buff">'],
        ];

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacerCustomReplacer2::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }


    public function testCSS()
    {
        $tests = [
            ['<style>a {color:white}; b {color: black}</style>', '<style>a {color:white}; b {color: black}</style>'],
            ['<style>background: url("/image.jpg"); a {}</style>', '<style>background: url("/image.jpg.webp"); a {}</style>'],
            ['<style>a {};background-image: url("/image.jpg")</style>', '<style>a {};background-image: url("/image.jpg.webp")</style>'],
            ['<style>background-image: url("/image.jpg"), url("/image2.png"));</style>', '<style>background-image: url("/image.jpg.webp"), url("/image2.png.webp"));</style>'],
            ['<style>background-image:url(/image.jpg), url("/image2.png"));</style>', '<style>background-image:url(/image.jpg.webp), url("/image2.png.webp"));</style>'],
            ['<p style="background-image:url(/image.jpg)"></p>', '<p style="background-image:url(/image.jpg.webp)"></p>'],
            ['<p style="a:{},background:url(/image.jpg)"></p>', '<p style="a:{},background:url(/image.jpg.webp)"></p>'],
            ["<style>background-image:\nurl(/image.jpg);</style>", "<style>background-image:\nurl(/image.jpg.webp);</style>"],
        ];

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function testWholeEngine()
    {
        $tests = [
            ['<img data-x="1.png">', '<img data-x="1.png.webp">'],
            ['<img data-x="2.jpg 1000w">', '<img data-x="2.jpg.webp 1000w">'],
            ['<img data-x="3.jpg 1000w, 4.jpg 2000w">', '<img data-x="3.jpg.webp 1000w, 4.jpg.webp 2000w">'],
            ['<img data-x="5.jpg 1000w, 6.jpg">', '<img data-x="5.jpg.webp 1000w, 6.jpg.webp">'],
            ['<img data-x="7.gif 1000w, 8.jpg">', '<img data-x="7.gif 1000w, 8.jpg.webp">'],
            ['<img data-lazy-original="9.jpg">', '<img data-lazy-original="9.jpg.webp">'],
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
            $output = ImageUrlReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }
}
