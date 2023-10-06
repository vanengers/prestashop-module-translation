<?php

namespace Vanengers\PrestashopModuleTranslation\Helper;

use RuntimeException;

class FilenameHelper
{
    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public static function buildDomainName(string $fileName): string
    {
        $return = self::extractFromFileName($fileName);
        $explode = explode('.', $return);
        return str_replace('.'.$explode[count($explode)-1], '', $return);
    }

    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public static function buildLocale(string $fileName): string
    {
        $return = self::extractFromFileName($fileName);
        $explode = explode('.', $return);
        return $explode[count($explode)-1];
    }

    /**
     * @param mixed $moduleName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public static function getDomainFromModulePathName(mixed $moduleName)
    {
        $explode = explode('/', $moduleName);
        return ucfirst(strtolower($explode[count($explode)-1]));
    }

    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public static function extractFromFileName(string $fileName): string
    {
        $baseName = substr($fileName, 0, -4);
        // explode CamelCaseWords into Camel.Case.Words
        $return = preg_replace('/((?<=[a-z0-9])[A-Z])/', '.\1', $baseName);
        if (!is_string($return)) {
            throw new RuntimeException('Unexpected replacement return: ' . print_r($return, true));
        }

        return $return;
    }
}