<?php

namespace Vanengers\PrestashopModuleTranslation\Command;

use AppBundle\Extract\Dumper\XliffFileDumper;
use DeepL\DeepLException;
use DeepL\Translator;
use Exception;
use PrestaShop\TranslationToolsBundle\Configuration;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\ChainExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\PhpExtractor;
use SmartyException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;
use Vanengers\PrestashopModuleTranslation\Helper\ContainerBuilder;
use Vanengers\PrestashopModuleTranslation\Helper\FilenameHelper;
use Vanengers\PrestashopModuleTranslation\Helper\SmartyBuilder;
use Vanengers\PrestashopModuleTranslation\Helper\TwigBuilder;
use Vanengers\PrestashopModuleTranslation\Translate\IsoFilter;
use Vanengers\PrestashopModuleTranslation\Translate\TranslationManager;
use Vanengers\SymfonyConsoleCommandLib\AbstractConsoleCommand;
use Vanengers\SymfonyConsoleCommandLib\Param\Option;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;

class ExtractCommand extends AbstractConsoleCommand
{
    /** @var ?XliffFileDumper $xliffFileDumper */
    private ?XliffFileDumper $xliffFileDumper;

    /** @var ?ChainExtractor $chainExtractor*/
    private ?ChainExtractor $chainExtractor;

    /** @var MessageCatalogue[] $catalogs  */
    private array $catalogs = [];

    /** @var ?MessageCatalogue $extractedCatalog */
    private ?MessageCatalogue $extractedCatalog = null;

    /** @var ?SymfonyContainerBuilder containerBuilder */
    private ?SymfonyContainerBuilder $containerBuilder;

    /** @var string $module_name */
    private string $module_name = '';

    /** @var string $extraction_dir */
    private string $extraction_dir = '';

    /** @var string $deepl_key */
    private string $deepl_key = '';

    /** @var string $translations_config_file */
    private string $translations_config_file = '';

    /** @var string $translations_xliff_dump_folder */
    private string $translations_xliff_dump_folder = '';

    /** @var string $base_locale */
    private string $base_locale = '';

    /** @var string[] $translate_to */
    private array $translate_to = [];

    /** @var string $formality */
    private string $formality = '';

    /** @var array $extraction_types */
    private array $extraction_types = [];

    /** @var string $prestashop_translations_config_file */
    private string $prestashop_translations_config_file = '';

    /** @var Filesystem fs */
    private Filesystem $fs;

    /** @var ?Translator $translator */
    private ?Translator $translator = null;

    public function __construct()
    {
        parent::__construct();
        $this->xliffFileDumper = new XliffFileDumper();
        $this->chainExtractor = new ChainExtractor();

        $this->containerBuilder = ContainerBuilder::build();
    }

    public function getCommandName(): string
    {
        return "extract";
    }

    public function getCommandDescription(): string
    {
        return "Extract translateables from a PrestaShop module and translate them using DeepL";
    }

    /**
     * @throws Exception
     */
    public function getOptions(): array
    {
        return [
            new Option(
                'extraction_dir',
                'Directory to extract translations from',
                'string',
                './'.rand(34521, 3452345).'NONEXISTING'.rand(34521, 3452345),
                true
            ),
            new Option(
                'module_name',
                'Name of the PrestaShop module',
                'string',
                null,
                true
            ),
            new Option(
                'deepl_key',
                'A valid and active Deepl Api key',
                'string',
                '',
                true
            ),
            new Option(
                'translations_config_file',
                'Path to the translations config file, where the translated strings are stored for retrieval',
                'string',
                './config/translations.json',
                true
            ),
            new Option(
                'translations_xliff_dump_folder',
                'Subfolder to store the xliff translation files in',
                'string',
                './translations',
                true
            ),
            new Option(
                'base_locale',
                'Translate from locale',
                'string',
                null,
                true
            ),
            new Option(
                'translate_to',
                'Iso codes to translate to',
                'array',
                null,
                true
            ),
            new Option(
                'formality',
                'Type of translation formality, e.g. more or less',
                'string',
                'more',
                true
            ),
            new Option(
                'extraction_types',
                'Type of extractors to use, e.g. php,smarty,twig',
                'array',
                ['php', 'smarty', 'twig'],
                true
            ),
            new Option(
                'prestashop_translations_config_file',
                'Yaml file for PrestaShop translations configurables (e.g. cache dir and folders to translate and/or exclude)',
                'string',
                __DIR__ . '/../../config/translation.yml',
                true
            ),
        ];
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws SmartyException|DeepLException
     * @since 06-10-2023
     * @author George van Engers <vanengers@gmail.com>
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->fs = new Filesystem();
        $this->initializeExtractionTypes();
        $this->validateInput();
        Configuration::fromYamlFile($this->prestashop_translations_config_file);
        $this->setTranslator();
    }

    /**
     * @return void
     * @throws SmartyException
     * @since 03-01-2024
     * @author George van Engers <vanengers@gmail.com>
     */
    private function initializeExtractionTypes(): void
    {
        if (in_array('php', $this->extraction_types)) {
            $this->chainExtractor->addExtractor("php", new PhpExtractor());
        }

        if (in_array('twig', $this->extraction_types)) {
            $this->chainExtractor->addExtractor("twig", TwigBuilder::build($this->containerBuilder));
        }

        if (in_array('smarty', $this->extraction_types)) {
            $this->chainExtractor->addExtractor("smarty", SmartyBuilder::build());
        }
    }

    /**
     * @return int
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public function executeCommand(): int
    {
        $this->extractedCatalog = $this->extract();
        $this->filterCatalogue();
        $this->initTranslations();
        $this->exportToXlfFiles();

        return 0;
    }

    /**
     * @return MessageCatalogue
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    protected function extract(): MessageCatalogue
    {
        $catalog = new MessageCatalogue($this->base_locale);
        $this->chainExtractor->extract($this->extraction_dir, $catalog);

        return $catalog;
    }

    /**
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function filterCatalogue(): void
    {
        $domainPattern = FilenameHelper::getDomainFromModulePathName($this->module_name);
        $filteredCatalog = $this->filterWhereDomain($this->extractedCatalog, $domainPattern);
        $this->extractedCatalog = $filteredCatalog;
    }

    /**
     * @param MessageCatalogue $catalog
     * @param string $domainPattern
     * @return MessageCatalogue
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function filterWhereDomain(MessageCatalogue $catalog, string $domainPattern) : MessageCatalogue
    {
        $newCatalogue = new MessageCatalogue($catalog->getLocale());
        $metadata = $catalog->getMetadata('', '');
        foreach ($catalog->all() as $domain => $messages) {
            if (str_contains(strtolower($domain), strtolower($domainPattern))) {
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
     * @return void
     * @throws Exception
     * @author George van Engers <vanengers@gmail.com>
     * @since 07-10-2023
     */
    private function initTranslations(): void
    {
        $manager = new TranslationManager($this->extractedCatalog, $this->catalogs, $this->base_locale,
            $this->translate_to, $this->translator, $this->formality, $this->translations_config_file
        );
        $manager->setOutput($this->io);
        $manager->init();

        $this->catalogs = $manager->getNewCatalogs();
    }

    /**
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function exportToXlfFiles(): void
    {
        if (!$this->fs->exists($this->translations_xliff_dump_folder)) {
            $this->fs->mkdir($this->translations_xliff_dump_folder);
        }

        foreach($this->catalogs as $catalog) {
            $this->xliffFileDumper->dump($catalog, [
                'path' => $this->translations_xliff_dump_folder,
                'root_dir' => $this->extraction_dir,
                'default_locale' => $catalog->getLocale(),
            ]);
        }
        $this->io->writeln('<info>Dumped catalogs to XLS files ' . $this->translations_xliff_dump_folder .'</info>');
    }

    /**
     * @return void
     * @throws Exception
     * @since 03-01-2024
     * @author George van Engers <george@dewebsmid.nl>
     */
    private function validateInput()
    {
        if (!$this->fs->exists($this->extraction_dir)) {
            throw new Exception('Extraction dir does not exist: ' . $this->extraction_dir);
        }

        if (empty($this->deepl_key)) {
            throw new Exception('Empty deepl key, please enter a valid API key for Deepl');
        }

        // translations_config_file will be created, but can also be empty for saving in the root of the module

        // translations_xliff_dump_folder will be created, but can also be empty for saving in the root of the module

        if (!IsoFilter::isValidLocale($this->base_locale)) {
            throw new Exception('Invalid locale: ' . $this->base_locale);
        }

        foreach ($this->translate_to as $locale) {
            if (!IsoFilter::isValidLocale($locale)) {
                throw new Exception('Invalid translate to locale: ' . $locale);
            }
        }

        if (!in_array($this->formality, ['more', 'less'])) {
            throw new Exception('Invalid formality: ' . $this->formality);
        }

        foreach ($this->extraction_types as $type) {
            if (!in_array($type, ['php', 'smarty', 'twig'])) {
                throw new Exception('Invalid extraction type: ' . $type);
            }
        }

        if (!$this->fs->exists($this->prestashop_translations_config_file)) {
            throw new Exception('PrestaShop translations config file does not exist: ' . $this->prestashop_translations_config_file);
        }
    }

    /**
     * @return void
     * @throws DeepLException
     * @since 03-01-2024
     * @author George van Engers <george@dewebsmid.nl>
     */
    private function setTranslator()
    {
        if ($this->translator == null) {
            $this->translator = new Translator($this->deepl_key);
        }
    }
}