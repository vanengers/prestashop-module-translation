<?php

namespace Vanengers\PrestashopModuleTranslation\Translate;

class IsoFilter
{
    /** @var Lang[] $languages */
    private static array $languages = [];

    /**
     * @param array $isos
     * @return array
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function filterValidLanguageIso(array $isos)
    {
        $data = [];

        self::loadLanguages();

        foreach($isos as $iso) {
            if (self::isValidIso($iso)) {
                $data[] = $iso;
            }
        }

        return $data;
    }

    /**
     * @param array $locales
     * @return array
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function filterValidLanguageLocale(array $locales)
    {
        $data = [];

        self::loadLanguages();

        foreach($locales as $locale) {
            if (self::isValidLocale($locale)) {
                $data[] = $locale;
            }
        }

        return $data;
    }

    /**
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private static function loadLanguages()
    {
        if (empty(self::$languages)) {
            $data = json_decode(file_get_contents(__DIR__ . '/../../config/languages.json'), true);
            foreach ($data as $row) {
                self::$languages[] = new Lang($row['iso_code'], $row['name'], $row['locale']);
            }
        }
    }

    /**
     * @return array
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function getLanguages()
    {
        self::loadLanguages();
        return self::$languages;
    }

    /**
     * @param mixed $iso
     * @return bool
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function isValidIso(mixed $iso)
    {
        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getIso() == $iso) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $locale
     * @return bool
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function isValidLocale(mixed $locale)
    {
        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getLocale() == $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $iso
     * @return false
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function getLocaleByIso(string $iso)
    {
        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getIso() == $iso) {
                return $lang->getLocale();
            }
        }

        return false;
    }

    /**
     * @param string $locale
     * @return false
     * @author George van Engers <vanengers@gmail.com>
     * @since 07-10-2023
     */
    public static function getIsoByLocale(string $locale)
    {
        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getLocale() == $locale) {
                return $lang->getIso();
            }
        }

        return false;
    }

    /**
     * @param string $locale
     * @return false|string
     * @author George van Engers <vanengers@gmail.com>
     * @since 07-10-2023
     */
    public static function getIsoByLocaleDeepL(string $locale)
    {
        $deeplTrans = [
            'gb' => 'en-gb',
            'en' => 'en-us',
            'pt' => 'pt-pt'
        ];

        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getLocale() == $locale) {
                if (array_key_exists($lang->getIso(), $deeplTrans)) {
                    return $deeplTrans[$lang->getIso()];
                }
                return $lang->getIso();
            }
        }

        return false;
    }
}