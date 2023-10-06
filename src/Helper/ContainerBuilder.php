<?php

namespace Vanengers\PrestashopModuleTranslation\Helper;

use AppBundle\AppBundle;
use PrestaShop\TranslationToolsBundle\TranslationToolsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ContainerBuilder
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public static function build() : \Symfony\Component\DependencyInjection\ContainerBuilder
    {
        $parameterBag = new ParameterBag();
        $parameterBag->add([
            'kernel.root_dir' => __DIR__ . '/../../../../../app',
            'kernel.project_dir' => __DIR__ . '/../../../../../',
            'kernel.environment' => 'dev',
            'kernel.debug' => true,
            'kernel.bundles' => [
                'FrameworkBundle' => FrameworkBundle::class,
                'TranslationToolsBundle' => TranslationToolsBundle::class,
                'TwigBundle' => TwigBundle::class,
                'MonologBundle' => MonologBundle::class,
                'AppBundle' => AppBundle::class,
            ],
            'kernel.cache_dir' => __DIR__ . '/../../../../../var/cache/dev',
            'kernel.logs_dir' => __DIR__ . '/../../../../../var/logs',
            'kernel.bundles_metadata' => [],
        ]);
        $containerBuilder = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new AppBundle(),
            new TranslationToolsBundle(),
        ];

        foreach($bundles as $bundle) {
            $bundle->build($containerBuilder);
        }

        return $containerBuilder;
    }
}