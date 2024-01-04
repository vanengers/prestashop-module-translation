<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Mocks\Deepl;

use DeepL\InvalidContentException;
use DeepL\Language;
use DeepL\TextResult;
use DeepL\Translator;
use DeepL\Usage;
use JsonException;

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
        return $this->getLanguages(file_get_contents(__DIR__.'/sourceLanguages.json'));
    }

    public function  getTargetLanguages(): array
    {
        return $this->getLanguages(file_get_contents(__DIR__.'/targetLanguages.json'));
    }

    public function getLanguages($content): array
    {
        try {
            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $result = [];
        foreach ($decoded as $lang) {
            $name = $lang['name'];
            $code = $lang['language'];
            $supportsFormality = array_key_exists('supports_formality', $lang) ?
                $lang['supports_formality'] : null;
            $result[] = new Language($name, $code, $supportsFormality);
        }
        return $result;
    }
}