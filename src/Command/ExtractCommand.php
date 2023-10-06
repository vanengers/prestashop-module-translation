<?php

namespace Vanengers\PrestashopModuleTranslation\Command;

use AppBundle\Extract\Dumper\XliffFileDumper;
use AppBundle\Services\TranslationFileLoader\XliffFileLoader;
use PrestaShop\TranslationToolsBundle\Configuration;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\ChainExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\PhpExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Vanengers\PrestashopModuleTranslation\Helper\ContainerBuilder;
use Vanengers\PrestashopModuleTranslation\Helper\FilenameHelper;
use Vanengers\PrestashopModuleTranslation\Helper\SmartyBuilder;
use Vanengers\PrestashopModuleTranslation\Helper\TwigBuilder;
use Vanengers\PrestashopModuleTranslation\Translate\IsoFilter;
use Vanengers\PrestashopModuleTranslation\Translate\TranslationManager;

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

    /** @var string[] toTranslate */
    private array $toTranslate = [];

    /** @var MessageCatalogue|null $extractedCatalog */
    private MessageCatalogue|null $extractedCatalog = null;

    /** @var string $pikey */
    private ?string $apikey = null;

    /** @var ?string $formality */
    private ?string $formality;

    public function __construct() {
        parent::__construct();
        $this->xliffFileDumper = new XliffFileDumper();
        $this->chainExtractor = new ChainExtractor();

        $containerBuilder = ContainerBuilder::build();

        $this->chainExtractor->addExtractor("php", new PhpExtractor());
        $this->chainExtractor->addExtractor("twig", TwigBuilder::build($containerBuilder));
        $this->chainExtractor->addExtractor("smarty", SmartyBuilder::build());
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
            ->addArgument('apikey', InputArgument::REQUIRED, 'ApiKey for DeepL')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to extract', 'nl-NL')
            ->addOption('translations_sub_folder', 't', InputOption::VALUE_OPTIONAL, 'Translations subfolder', 'translations')
            ->addOption('translate_to', 'i', InputOption::VALUE_OPTIONAL, 'iso\'s to translate to', '')
            ->addOption('formality', 'd', InputOption::VALUE_OPTIONAL, 'DeepL formalityu setting', 'more')
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
        $this->apikey = $input->getArgument('apikey');
        $this->moduleFolder = realpath(dirname($this->moduleName));
        $this->locale = $input->getOption('locale'); // reverted to default: nl-NL
        $this->translationDirDump = $input->getOption('translations_sub_folder');
        $this->formality = $input->getOption('formality');
        $this->toTranslate = IsoFilter::filterValidLanguageLocale(explode(',', $input->getOption('translate_to')));

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

        //$this->tryLoadExistingCatalog();

        $this->extractedCatalog = $this->extract();
        $this->filterCatalogue();
        $this->initTranslations();
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
            $domainName = FilenameHelper::buildDomainName($fileName);
            $locale = FilenameHelper::buildLocale($fileName);
            if($locale != $this->locale) {
                continue;
            }
            if (isset($this->catalogs[$locale])) {
                $catalog = $this->catalogs[$locale];
            } else {
                $catalog = new MessageCatalogue($locale);
                $this->catalogs[$locale] = $catalog;
            }
            $catalog->addCatalogue(
                $loader->load($file->getPathname(), $locale, $domainName)
            );
            $this->catalogs[$locale] = $catalog;
        }
    }

    /**
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function filterCatalogue(): void
    {
        $domainPattern = FilenameHelper::getDomainFromModulePathName($this->moduleName);
        $filteredCatalog = $this->filterWhereDomain($this->extractedCatalog, $domainPattern);

        $this->extractedCatalog = $filteredCatalog;
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

    private function initTranslations()
    {
        $manager = new TranslationManager($this->extractedCatalog, $this->addedStrings, $this->catalogs,
            $this->moduleFolder, $this->locale, $this->toTranslate, $this->apikey, $this->formality);
        $manager->setOutput($this->output);
        $manager->doStuff();

        $this->catalogs = $manager->getNewCatalogs();
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
        }
        $this->output->writeln('<info>Dumped catalogs to XLS files ' . $this->exportPath .'</info>');
    }
}