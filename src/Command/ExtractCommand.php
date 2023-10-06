<?php

namespace Vanengers\PrestashopModuleTranslation\Command;

use AppBundle\AppBundle;
use AppBundle\Extract\Dumper\XliffFileDumper;
use AppBundle\Services\TranslationFileLoader\XliffFileLoader;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\TranslationToolsBundle\Configuration;
use PrestaShop\TranslationToolsBundle\DependencyInjection\TranslationToolsExtension;
use PrestaShop\TranslationToolsBundle\Translation\Compiler\Smarty\TranslationTemplateCompiler;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\ChainExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\PhpExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\SmartyExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\TwigExtractor;
use PrestaShop\TranslationToolsBundle\TranslationToolsBundle;
use PrestaShop\TranslationToolsBundle\Twig\Extension\AppExtension;
use PrestaShop\TranslationToolsBundle\Twig\Extension\TranslationExtension;
use PrestaShopBundle\Translation\Translator;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Loader\ChainLoader;

class ExtractCommand extends Command
{
    /** @var XliffFileDumper $xliffFileDumper */
    private XliffFileDumper $xliffFileDumper;

    /** @var ChainExtractor $chainExtractor*/
    private ChainExtractor $chainExtractor;

    /** @var MessageCatalogue[] $catalogs  */
    private array $catalogs = [];

    /** @var MessageCatalogue|null $addedStrings  */
    private MessageCatalogue|null $addedStrings = null;

    /** @var string $locale */
    private string $locale = '';

    /** @var string $moduleName */
    private string $moduleName = '';

    /** @var string $moduleFolder */
    private string $moduleFolder = '';

    /** @var string exportPath */
    private string $exportPath = '';

    /** @var string translationDirDump */
    private string $translationDirDump;

    /** @var OutputInterface|null output */
    private OutputInterface|null $output = null;

    public function __construct() {
        parent::__construct();
        $this->xliffFileDumper = new XliffFileDumper();
        $this->chainExtractor = new ChainExtractor();

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
        $containerBuilder = new ContainerBuilder($parameterBag);
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

        $twigExtractor = new TwigExtractor($env);

        $this->chainExtractor->addExtractor("php", new PhpExtractor());
        $this->chainExtractor->addExtractor("twig", $twigExtractor);
        //$this->chainExtractor->addExtractor("smarty", new SmartyExtractor(new TranslationTemplateCompiler()));
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    protected function configure(): void
    {
        $this->setName('extract')
            ->addArgument('module', InputArgument::REQUIRED, 'Name of the module')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to extract', 'nl-NL')
            ->addOption('translations_sub_folder', 't', InputOption::VALUE_OPTIONAL, 'Translations subfolder', 'translations')
            ->setDescription('Extract translation');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $this->moduleName = $input->getArgument('module');
        $this->moduleFolder = realpath(dirname($this->moduleName));
        $this->locale = $input->getOption('locale'); // reverted to default: nl-NL
        $this->translationDirDump = $input->getOption('translations_sub_folder');

        $this->addedStrings = new MessageCatalogue($this->locale);

        $this->exportPath = sprintf('%s%s%s', $this->moduleFolder, DIRECTORY_SEPARATOR, $this->translationDirDump);
        if (!file_exists($this->exportPath)) {
            mkdir($this->exportPath, 0777, true);
        }

        Configuration::fromYamlFile(__DIR__ . '/../../config/translation.yml');

        parent::initialize($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initialize($input, $output);
        $output->writeln(sprintf('Extracting Translations for locale <info>%s</info>', $this->locale));

        $this->tryLoadExistingCatalog();

        $extractedCatalog = $this->extract();
        $this->filterCatalogue($extractedCatalog);

        $this->tranlateNewString();

        $this->exportToXlfFiles();

        return 0;
    }

    /**
     * @return MessageCatalogue
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    protected function extract(): MessageCatalogue
    {
        $catalog = new MessageCatalogue($this->locale);
        $this->chainExtractor->extract($this->moduleFolder, $catalog);

        return $catalog;
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function tryLoadExistingCatalog(): void
    {
        $loader = new XliffFileLoader();
        $finder = new Finder();
        $finder->ignoreUnreadableDirs();

        $finder->files()->in($this->moduleFolder.'/'.$this->translationDirDump.'/')->name('*.xlf');

        foreach ($finder as $file) {
            $this->output->writeln('<info>Loading from catalog: ' . $file->getFilename() . '</info>');
            $fileName = $file->getFilename();
            $domainName = $this->buildDomainName($fileName);
            $locale = $this->buildLocale($fileName);
            $catalog = new MessageCatalogue($locale);
            $catalog->addCatalogue(
                $loader->load($file->getPathname(), $locale, $domainName)
            );
            $this->catalogs[$locale] = $catalog;
        }
    }

    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function buildDomainName(string $fileName): string
    {
        $return = $this->extractFromFileName($fileName);
        $explode = explode('.', $return);
        return str_replace('.'.$explode[count($explode)-1], '', $return);
    }

    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function extractFromFileName(string $fileName): string
    {
        $baseName = substr($fileName, 0, -4);
        // explode CamelCaseWords into Camel.Case.Words
        $return = preg_replace('/((?<=[a-z0-9])[A-Z])/', '.\1', $baseName);
        if (!is_string($return)) {
            throw new RuntimeException('Unexpected replacement return: ' . print_r($return, true));
        }

        return $return;
    }

    /**
     * @param string $fileName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public function buildLocale(string $fileName): string
    {
        $return = $this->extractFromFileName($fileName);
        $explode = explode('.', $return);
        return $explode[count($explode)-1];
    }

    /**
     * @param MessageCatalogue $extractedCatalog
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function filterCatalogue(MessageCatalogue $extractedCatalog): void
    {
        $domainPattern = $this->getDomainFromModulePathName($this->moduleName);
        $filteredCatalog = $this->filterWhereDomain($extractedCatalog, $domainPattern);

        $currentCatalog = $this->catalogs[$this->locale] ?? new MessageCatalogue($this->locale);
        foreach($filteredCatalog->all() as $domain => $messages) {
            $metadata = $filteredCatalog->getMetadata('', '');
            foreach($messages as $message) {
                if (!isset($currentCatalog->all()[$domain][$message])) {
                    $currentCatalog->add([$message => $message], $domain);
                    $currentCatalog->setMetadata($message, $metadata[$domain][$message], $domain);
                    $this->output->writeln('<info>Added new translation in domain: '.$domain.' : ' . $message . '</info>');
                }
            }
        }
        $this->catalogs[$this->locale] = $currentCatalog;
    }

    /**
     * @param MessageCatalogue $catalog
     * @param string $domainPattern
     * @return MessageCatalogue
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function filterWhereDomain(MessageCatalogue $catalog, string $domainPattern) : MessageCatalogue
    {
        $newCatalogue = new MessageCatalogue($catalog->getLocale());
        $metadata = $catalog->getMetadata('', '');
        foreach ($catalog->all() as $domain => $messages) {
            if (str_contains($domain, $domainPattern)) {
                $newCatalogue->add(
                    $messages,
                    $domain
                );

                if (isset($metadata[$domain])) {
                    foreach ($metadata[$domain] as $key => $value) {
                        $newCatalogue->setMetadata($key, $value, $domain);
                    }
                }
            }
        }

        return $newCatalogue;
    }

    /**
     * @param mixed $moduleName
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function getDomainFromModulePathName(mixed $moduleName)
    {
        $explode = explode('/', $moduleName);
        return ucfirst(strtolower($explode[count($explode)-1]));
    }

    private function tranlateNewString()
    {

    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function exportToXlfFiles()
    {
        foreach($this->catalogs as $catalog) {
            $this->xliffFileDumper->dump($catalog, [
                'path' => $this->exportPath,
                'root_dir' => dirname($this->moduleFolder),
                'default_locale' => $catalog->getLocale(),
            ]);
            foreach($catalog->getDomains() as $domain) {
                $this->output->writeln('<info>Dump ' . $this->exportPath . ' - '. $domain . ' - '.$catalog->getLocale() .'</info>');
            }
        }
    }
}