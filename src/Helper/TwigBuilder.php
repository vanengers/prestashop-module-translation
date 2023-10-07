<?php

namespace Vanengers\PrestashopModuleTranslation\Helper;

use PrestaShop\TranslationToolsBundle\DependencyInjection\TranslationToolsExtension;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\TwigExtractor;
use PrestaShop\TranslationToolsBundle\Twig\Extension\TranslationExtension;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Twig\Environment;
use Twig\Loader\ChainLoader;

class TwigBuilder
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     * @return TwigExtractor
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public static function build(\Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder) : TwigExtractor
    {
        $ext = new TranslationToolsExtension();
        $ext1 = new TranslationExtension();
        $ext->load([], $containerBuilder);
        $ext2 = new TwigExtension();
        $ext2->load([], $containerBuilder);
        $ext3 = new \Vanengers\PrestashopModuleTranslation\Twig\Extension\TwigExtension();

        $chainLoader = new ChainLoader();
        $env = new Environment($chainLoader);
        $env->enableDebug();
        $env->addExtension($ext1);
        $env->addExtension($ext3);

        return new TwigExtractor($env);
    }
}