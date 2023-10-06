<?php

namespace Vanengers\PrestashopModuleTranslation\Command;

use AppBundle\Command\BaseCommand;
use AppBundle\Extract\Dumper\XliffFileDumper;
use AppBundle\Services\TranslationFileLoader\XliffFileLoader;
use PrestaShop\TranslationToolsBundle\Configuration;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\ChainExtractor;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\PhpExtractor;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Loader\ChainLoader;

class ExtractCommand extends BaseCommand
{
    protected static $defaultName = 'extract';

    /**
     * @var XliffFileDumper
     */
    private XliffFileDumper $xliffFileDumper;

    /**
     * @var ChainExtractor
     */
    private ChainExtractor $chainExtractor;

    /** @var MessageCatalogue[] $catalogs  */
    private array $catalogs = [];

    /** @var MessageCatalogue|null $addedStrings  */
    private MessageCatalogue|null $addedStrings = null;

    public function __construct(
        string $translationDirDump,
        string $locale,
    ) {
        parent::__construct($translationDirDump);
        $this->locale = $locale;
        $this->xliffFileDumper = new XliffFileDumper();
        $this->chainExtractor = new ChainExtractor();

        $this->chainExtractor->addExtractor("php", new PhpExtractor());
        //$this->chainExtractor->addExtractor("php", new TwigExtractor( new Environment(new ChainLoader())));
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    protected function configure(): void
    {
        $this
            ->setName('extract')
            ->addArgument('module', InputArgument::REQUIRED, 'Name of the module')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to extract', 'nl-NL')
            ->setDescription('Extract translation');
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

        $moduleName = $input->getArgument('module');
        $output->writeln(sprintf('Extracting Translations for locale <info>%s</info>', $this->locale));

        $moduleFolder = realpath(dirname($moduleName));
        $version = $this->getVersionFromModuleFolder($moduleFolder, $moduleName);
        $output->writeln('<info>Found version ' . $version->getVersion() . ' of the module</info>');

        $this->locale = $input->getOption('locale'); // reverted to default: nl-NL

        $this->tryLoadExistingCatalog($moduleFolder);

        if (!isset($this->catalogs[$this->locale])) {
            $this->catalogs[$this->locale] = new MessageCatalogue($this->locale);
        }

        $this->addedStrings = new MessageCatalogue($this->locale);

        $extractedCatalog = $this->extract($moduleFolder);
        $this->filterCatalogue($extractedCatalog, $this->getDomainFromModulePathName($moduleName));

        $this->tranlateNewString();

        $path = sprintf('%s%s%s', $moduleFolder, DIRECTORY_SEPARATOR, 'translations');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        foreach($this->catalogs as $catalog) {
            $this->xliffFileDumper->dump($catalog, [
                'path' => $path,
                'root_dir' => dirname($moduleFolder),
                'default_locale' => $catalog->getLocale(),
            ]);
            foreach($catalog->getDomains() as $domain) {
                $output->writeln('<info>Dump ' . $path . ' - '. $domain . ' - '.$catalog->getLocale() .'</info>');
            }
        }

        // Raw translated strings in all languages,, save the translations for later use ..
        // so we never have to pay for duplicate translations within the same module!

        return 0;
    }

    /**
     * @param string $catalogRelativePath
     * @return MessageCatalogue
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    protected function extract(string $catalogRelativePath): MessageCatalogue
    {
        $catalog = new MessageCatalogue($this->locale);
        $this->chainExtractor->extract($catalogRelativePath, $catalog);

        return $catalog;
    }

    /**
     * @param string $catalogRelativePath
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function tryLoadExistingCatalog(string $catalogRelativePath): void
    {
        $loader = new XliffFileLoader();
        $finder = new Finder();
        $finder->ignoreUnreadableDirs();

        $finder->files()->in($catalogRelativePath.'/translations/')->name('*.xlf');

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
            throw new \RuntimeException('Unexpected replacement return: ' . print_r($return, true));
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
     * @param string $domainPattern
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function filterCatalogue(MessageCatalogue $extractedCatalog, string $domainPattern): void
    {
        $newCatalogs = [];
        $filteredCatalog = $this->filterWhereDomain($extractedCatalog, $domainPattern);

        // we check here if there are any changes in the catalog
        // if so.. we add them to the catalog.. and add them to the other Catalog translations

        // BUT we only translate the actual NEW ones!

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
            /*var_dump([
                'domain' => $domain,
                '$domainPattern' => $domainPattern,
                'str_contains' => str_contains($domain, $domainPattern),
            ]);die; */
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
        // "Modules. ../prestashopmodulebase/prestashopmodulebase"
        //"Modules.Prestashopmodulebase.Prestashopmodulebase"
        $explode = explode('/', $moduleName);
        return ucfirst(strtolower($explode[count($explode)-1]));
    }

    private function tranlateNewString()
    {

    }
}