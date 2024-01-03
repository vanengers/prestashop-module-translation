<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Mocks;

use DeepL\Language;
use DeepL\TextResult;
use DeepL\Translator;
use DeepL\Usage;

class DeeplTranslatorMock extends Translator
{
    public function __construct(string $authKey, array $options = [])
    {
        parent::__construct($authKey, $options);
    }

    public function getUsage(): Usage
    {
        return new Usage('{ "character_count": 0, "character_limit": 0 }');
    }

    public function translateText($texts, ?string $sourceLang, string $targetLang, array $options = [])
    {
        $text = is_array($texts) ? $texts[0] : $texts;
        return new TextResult($targetLang.$text, $sourceLang);
    }

    public function getSourceLanguages(): array
    {
        return [
            new Language('Netherlands', 'nl', true),
            new Language('English', 'en-gb', true),
        ];
    }

    public function  getTargetLanguages(): array
    {
        return $this->getSourceLanguages();
    }
}