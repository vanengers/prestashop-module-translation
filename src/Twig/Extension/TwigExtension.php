<?php

namespace Vanengers\PrestashopModuleTranslation\Twig\Extension;

use Symfony\Component\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{

    /**
     * AppExtension constructor.
     */
    public function __construct()
    {

    }

    /**
     * We need to define and reset each twig function as the definition
     * of theses function is stored in PrestaShop codebase.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('configuration', [$this, 'configuration']),
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