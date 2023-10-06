<?php

namespace Vanengers\PrestashopModuleTranslation\Translate;

class Lang
{
    private string $iso;
    private string $name;
    private string $locale;

    public function __construct(string $iso, string $name, string $locale)
    {
        $this->iso = $iso;
        $this->name = $name;
        $this->locale = $locale;
    }

    public function getIso(): string
    {
        return $this->iso;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}