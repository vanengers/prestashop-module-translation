<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\IsoFilter;

use PHPUnit\Framework\TestCase;
use Vanengers\PrestashopModuleTranslation\Translate\IsoFilter;

class TranslateIsoToLocaleTest extends TestCase
{
    public function testCanTranslateLocale()
    {
        $locale = 'nl-NL';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('nl', $iso);
    }

    public function testCanTranslateLocaleLower()
    {
        $locale = 'nl-nl';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('nl', $iso);
    }

    public function testCanTranslateBritischSource()
    {
        $locale = 'en-GB';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('gb', $iso);
    }

    public function testCanTranslateBritischLowerSource()
    {
        $locale = 'en-gb';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('gb', $iso);
    }

    public function testCanTranslateBritischTarget()
    {
        $locale = 'en-GB';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale, true);

        $this->assertEquals('en-gb', $iso);
    }

    public function testCanTranslateBritischLowerTarget()
    {
        $locale = 'en-gb';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale, true);

        $this->assertEquals('en-gb', $iso);
    }

    public function testCanTranslateEn()
    {
        $locale = 'en-EN';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('', $iso);
    }

    public function testCanTranslateEnLower()
    {
        $locale = 'en-en';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('', $iso);
    }

    public function testCanTranslateUsSource()
    {
        $locale = 'en-US';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('en', $iso);
    }

    public function testCanTranslateUsLowerSource()
    {
        $locale = 'en-us';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('en', $iso);
    }

    public function testCanTranslateUsTarget()
    {
        $locale = 'en-US';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale, true);

        $this->assertEquals('en-us', $iso);
    }

    public function testCanTranslateUsLowerTarget()
    {
        $locale = 'en-us';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale, true);

        $this->assertEquals('en-us', $iso);
    }
}
