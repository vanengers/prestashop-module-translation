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
    protected static $defaultName = 'prestashop:translation:extract';

    /**
     * @var string
     */
    private $locale;

    /**
     * @var XliffFileDumper
     */
    private $xliffFileDumper;

    /**
     * @var ChainExtractor
     */
    private $chainExtractor;

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

    protected function configure()
    {
        $this
            ->setName('prestashop:translation:extract')
            ->addArgument('module', InputArgument::REQUIRED, 'Name of the module')
            ->addOption('from-scratch', null, InputOption::VALUE_OPTIONAL, 'Build the catalogue from scratch instead of incrementally', false)
            ->addOption('default_locale', null, InputOption::VALUE_OPTIONAL, 'Default locale', 'en-US')
            ->addOption('domain_pattern', null, InputOption::VALUE_OPTIONAL, 'A regex to filter domain names', '#^Modules*|messages#')
            ->setDescription('Extract translation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = $input->getArgument('module');
        $buildFromScratch = (bool) $input->getOption('from-scratch');

        $output->writeln(sprintf('Extracting Translations for locale <info>%s</info>', $this->locale));

        $moduleFolder = realpath(dirname($moduleName));
        $version = $this->getVersionFromModuleFolder($moduleFolder, $moduleName);

        $output->writeln('<info>Found version ' . $version->getVersion() . ' of the module</info>');

        $catalog = $this->extract($buildFromScratch, $moduleFolder);
        $catalog = $this->filterCatalogue($catalog, 'Modules.'.ucfirst(strtolower($moduleName)));

        $locale = 'nl-NL';


        $path = sprintf('%s%s%s', $moduleFolder, DIRECTORY_SEPARATOR, 'translations');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $this->xliffFileDumper->dump($catalog, [
            'path' => $path,
            'root_dir' => dirname($moduleFolder),
            'default_locale' => $locale,
        ]);

        $output->writeln('<info>Dump ' . $path . '</info>');

        return 0;
    }

    protected function extract(bool $buildFromScratch, string $catalogRelativePath): MessageCatalogue
    {
        $catalog = new MessageCatalogue($this->locale);
        $this->chainExtractor->extract($catalogRelativePath, $catalog);

        return $catalog;
    }

    /**
     * Loads the existing catalog into the provided one
     */
    private function loadExistingCatalog(MessageCatalogue $catalog, string $catalogRelativePath)
    {
        $catalogPath = dirname($this->configFilePath) . $catalogRelativePath;

        $loader = new XliffFileLoader();

        $finder = new Finder();

        $finder->ignoreUnreadableDirs();

        $finder->files()->in($catalogPath)->name('*.xlf');

        foreach ($finder as $file) {
            $fileName = $file->getFilename();
            $domainName = $this->buildDomainName($fileName);
            $catalog->addCatalogue(
                $loader->load($file->getPathname(), $this->locale, $domainName)
            );
        }
    }

    /**
     * Builds a domain name like My.Domain.Name from a filename like MyDomainName.xlf
     */
    private function buildDomainName(string $fileName): string
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
     * Filter the catalogue given with the domain matching the pattern.
     */
    private function filterCatalogue(MessageCatalogue $catalog, string $domainPattern): MessageCatalogue
    {
        $newCatalogue = new MessageCatalogue($catalog->getLocale());
        $metadata = $catalog->getMetadata('', $domainPattern);

        foreach ($catalog->all() as $domain => $messages) {
            var_dump([
                'domain' => $domain,
                'pattern' => $domainPattern,
                'contains' => str_contains($domain, $domainPattern),
            ]);
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
}