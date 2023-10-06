<?php

namespace Vanengers\PrestashopModuleTranslation\Twig\Extension;

use Symfony\Component\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension
{

    /**
     * AppExtension constructor.
     */
    public function __construct()
    {

    }

    public function getFilters()
    {
        return [
            new TwigFilter('entity_field', [$this, 'configuration']),
            new TwigFilter('date_format_full', [$this, 'configuration']),
            new TwigFilter('date_format_lite', [$this, 'configuration']),
            new TwigFilter('price_format', [$this, 'configuration']),
            new TwigFilter('arrayCast', [$this, 'configuration']),
            new TwigFilter('intCast', [$this, 'configuration']),
            new TwigFilter('unsetElement', [$this, 'configuration']),
            new TwigFilter('array_pluck', [$this, 'configuration']),
            new TwigFilter('renderhook', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFilter('renderhooksarray', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFilter('hooksarraycontent', [$this, 'configuration']),
            new TwigFilter('configuration', [$this, 'configuration'], ['deprecated' => true]),
        ];
    }

    /**
     * We need to define and reset each twig function as the definition
     * of theses function is stored in PrestaShop codebase.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_color_bright', [$this, 'configuration']),
            new TwigFunction('documentation_link', [$this, 'configuration']),
            new TwigFunction('column_content', [$this, 'configuration'], ['is_safe' => ['html'],]),
            new TwigFunction('column_header', [$this, 'configuration'], ['is_safe' => ['html'],]),
            new TwigFunction('is_ordering_column', [$this, 'configuration'], ['is_safe' => ['html'],]),
            new TwigFunction('js_router_metadata', [$this, 'configuration']),
            new TwigFunction('multistoreHeader', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFunction('multistoreProductHeader', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFunction('multistore_url', [$this, 'configuration']),
            new TwigFunction('multistore_group_url', [$this, 'configuration']),
            new TwigFunction('multistore_shop_url', [$this, 'configuration']),
            new TwigFunction('number', [$this, 'configuration']),
            new TwigFunction('pathWithBackUrl', [$this, 'configuration']),
            new TwigFunction('get_context_iso_code', [$this, 'configuration']),
            new TwigFunction('arrayCast', [$this, 'configuration']),
            new TwigFunction('intCast', [$this, 'configuration']),
            new TwigFunction('unsetElement', [$this, 'configuration']),
            new TwigFunction('array_pluck', [$this, 'configuration']),
            new TwigFunction('renderhook', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFunction('renderhooksarray', [$this, 'configuration'], ['is_safe' => ['html']]),
            new TwigFunction('hooksarraycontent', [$this, 'configuration']),
            new TwigFunction('getLegacyLayout', [$this, 'configuration']),
            new TwigFunction('getAdminLink', [$this, 'configuration']),
            new TwigFunction('youtube_link', [$this, 'configuration']),
            new TwigFunction('configuration', [$this, 'configuration']),
            new TwigFunction('format_date', [$this, 'configuration']),
            new TwigFunction('getTranslationsTree', [$this, 'configuration']),
            new TwigFunction('getTranslationsForms', [$this, 'configuration']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('formview', static function ($value) { return true; }),
            new TwigTest('form', static function ($value) { return true; }),
        ];
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function configuration($string)
    {
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'app';
    }
}