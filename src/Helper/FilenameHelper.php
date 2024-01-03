<?php

namespace Vanengers\PrestashopModuleTranslation\Helper;

class FilenameHelper
{
    /**
     * @param $moduleName
     * @return string
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function getDomainFromModulePathName($moduleName): string
    {
        $explode = explode('/', $moduleName);
        return ucfirst(strtolower($explode[count($explode)-1]));
    }
}