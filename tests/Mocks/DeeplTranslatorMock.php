<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Mocks;

use DeepL\InvalidContentException;
use JsonException;
use Vanengers\CatalogTranslator\Client\DeeplClient;
use Vanengers\CatalogTranslator\Iso\Lang;

class DeeplTranslatorMock extends DeeplClient
{

    public function __construct(array $options = [])
    {
        parent::__construct(['api_key'=>'bALBLA']);
    }

    public function translate(string $text, string $source, string $target): string
    {
        return $target.$text;
    }

    public function getSourceLanguages(): array
    {
        return $this->getLanguages(file_get_contents(__DIR__ . '/sourceLanguages.json'));
    }

    public function getTargetLanguages(): array
    {
        return $this->getLanguages(file_get_contents(__DIR__.'/targetLanguages.json'), true);
    }

    public function canConnect(): bool
    {
        return true;
    }

    private function getLanguages($content, $target = false): array
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
            $result[] = new Lang($code, $name, $this->fromIsoToLocale($code, $target));
        }
        return $result;
    }
}