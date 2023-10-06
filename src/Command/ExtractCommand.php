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
use Vanengers\PrestashopModuleTranslation\Helper\FilenameHelper;
use Vanengers\PrestashopModuleTranslation\Helper\SmartyBuilder;
use Vanengers\PrestashopModuleTranslation\Helper\TwigBuilder;

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

        $containerBuilder = \Vanengers\PrestashopModuleTranslation\Helper\ContainerBuilder::build();

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
            $domainName = FilenameHelper::buildDomainName($fileName);
            $locale = FilenameHelper::buildLocale($fileName);
            $catalog = new MessageCatalogue($locale);
            $catalog->addCatalogue(
                $loader->load($file->getPathname(), $locale, $domainName)
            );
            $this->catalogs[$locale] = $catalog;
        }
    }

    /**
     * @param MessageCatalogue $extractedCatalog
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function filterCatalogue(MessageCatalogue $extractedCatalog): void
    {
        $domainPattern = FilenameHelper::getDomainFromModulePathName($this->moduleName);
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