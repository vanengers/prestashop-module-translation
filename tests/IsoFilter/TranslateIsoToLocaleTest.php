<?php

namespace IsoFilter;

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

    public function testCanTranslateBritisch()
    {
        $locale = 'en-GB';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('en-gb', $iso);
    }

    public function testCanTranslateBritischLower()
    {
        $locale = 'en-gb';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

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

    public function testCanTranslateUs()
    {
        $locale = 'en-US';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('en-us', $iso);
    }

    public function testCanTranslateUsLower()
    {
        $locale = 'en-us';
        $iso = IsoFilter::getIsoByLocaleDeepL($locale);

        $this->assertEquals('en-us', $iso);
    }
}
