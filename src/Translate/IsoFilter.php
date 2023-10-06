<?php

namespace Vanengers\PrestashopModuleTranslation\Translate;

class IsoFilter
{
    private static array $languages = [];

    /**
     * @param array $isos
     * @return array
     * @author George van Engers <george@dewebsmid.nl>
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
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private static function loadLanguages()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../../data/languages.json'), true);
        foreach($data as $row) {
            self::$languages[] = new Lang($row['iso_code'], $row['name'], $row['locale']);
        }
    }

    /**
     * @return array
     * @author George van Engers <george@dewebsmid.nl>
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
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private static function isValidIso(mixed $iso)
    {
        self::loadLanguages();
        foreach(self::$languages as $lang) {
            if ($lang->getIso() == $iso) {
                return true;
            }
        }

        return false;
    }
}