<?php

namespace Vanengers\PrestashopModuleTranslation\Translate;

use DeepL\DeepLException;
use DeepL\Language;
use DeepL\Translator;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;
use Throwable;

class TranslationManager
{

    /** @var MessageCatalogue $extractedCatalogue */
    private MessageCatalogue $extractedCatalogue;

    /** @var array $translateTo */
    private array $translateTo;

    /** @var string $locale */
    private string $locale;

    /** @var array|mixed $translations */
    private array $translations = [];


    /** @var MessageCatalogue[] $catalogs */
    private array $catalogs;

    /** @var ?OutputInterface $output */
    private ?OutputInterface $output = null;

    /** @var Translator $translator */
    private Translator $translator;

    /** @var Filesystem fs */
    private Filesystem $fs;

    /** @var string formality */
    private string $formality;

    /** @var string apiKey */
    private string $apiKey;


    /** @var string translations_config_file */
    private string $translations_config_file;

    public function __construct(MessageCatalogue $extracted, array $catalogs, string $locale, array $translateTo = [],
        ?Translator $translator = null, string $formality = 'more', string $translations_config_file = ''
    )
    {
        $this->extractedCatalogue = $extracted;
        $this->translateTo = $translateTo;
        $this->locale = $locale;
        $this->catalogs = $catalogs;
        $this->formality = $formality;
        $this->translations_config_file = $translations_config_file;
        $this->translator = $translator;

        $this->fs = new Filesystem();
    }

    /**
     * @return void
     * @throws Exception
     * @since 07-10-2023
     * @author George van Engers <vanengers@gmail.com>
     */
    public function init(): void
    {
        try {
            $this->translator->getUsage();
        }
        catch (DeepLException $e) {
            throw new Exception('FATAL ERROR; CANNOT CONTINUE: Most-likely an Invalid API key | Or other DeepL error');
        }

        $this->initTranslations();
        $this->syncUpCatalogues();
        $this->saveTranslationsToDisk();
    }

    /**
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function initTranslations(): void
    {
        if (!$this->fs->exists($this->translations_config_file)) {
            $this->fs->dumpFile($this->translations_config_file, json_encode([], JSON_PRETTY_PRINT));
        }
        $translations = json_decode(file_get_contents($this->translations_config_file), true);
        $this->translations = $translations ?? [];
    }

    /**
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function saveTranslationsToDisk(): void
    {
        $this->output->writeln('<info>Saving translations to disk: '.$this->translations_config_file.'</info>');
        file_put_contents($this->translations_config_file, json_encode($this->translations, JSON_PRETTY_PRINT));
    }

    /**
     * @return array|MessageCatalogue[]
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public function getNewCatalogs(): array
    {
        return $this->catalogs;
    }

    /**
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function syncUpCatalogues(): void
    {
        $this->output->writeln('<info>SyncUp catalogues'.'</info>');

        foreach($this->translateTo as $locale) {
            if (!array_key_exists($locale, $this->catalogs)) {
                $this->catalogs[$locale] = new MessageCatalogue($locale);
            }
        }

        if (!array_key_exists($this->locale, $this->catalogs)) {
            $this->catalogs[$this->locale] = new MessageCatalogue($this->locale);
        }

        foreach($this->extractedCatalogue->all() as $domain => $messages) {
            $allMetaData = $this->extractedCatalogue->getMetadata('', '');
            foreach($messages as $id => $message) {
                $meta = $allMetaData[$domain][$message] ?? [];
                foreach($this->catalogs as $loc => $catalog) {
                    if (!$catalog->has($id, $domain)) {
                        $catalog->add([$id => $this->translate($id, $message, $loc)], $domain);
                        $catalog->setMetadata($id, $meta, $domain);
                    }
                    if (isset($metadata[$domain])) {
                        foreach ($metadata[$domain] as $key => $value) {
                            $catalog->setMetadata($key, $value, $domain);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @param string $id
     * @param string $message
     * @param string $getLocale
     * @return string
     * @author George van Engers <vanengers@gmail.com>
     * @since 06-10-2023
     */
    private function translate(string $id, string $message, string $getLocale) : string
    {
        if (array_key_exists($getLocale, $this->translations)) {
            if (array_key_exists($id, $this->translations[$getLocale])) {
                return $this->translations[$getLocale][$id];
            }
        }

        if ($getLocale != $this->locale) {
            $message = $this->remoteTranslate($message, $getLocale);
            $this->output->writeln('<comment>'.$id. ' --> '.$message.'</comment>');
        }

        $this->translations[$getLocale][$id] = $message;
        return $this->translations[$getLocale][$id];
    }

    /** @var bool[] $canceledRemoteTranslations */
    private array $canceledRemoteTranslations = [];

    /**
     * @param string $message
     * @param string $locale
     * @return string
     * @author George van Engers <vanengers@gmail.com>
     * @since 07-10-2023
     */
    private function remoteTranslate(string $message, string $locale) : string
    {
        if (!$this->verifyTranslatable($locale)) {
            if (!array_key_exists($locale, $this->canceledRemoteTranslations)) {
                $this->output->writeln('<fg=black;bg=white>'.$locale.' is not supported by DeepL; messages not translated remotely; still saved default locale</>');
                $this->canceledRemoteTranslations[$locale] = true;
            }

            return $message;
        }

        $options = [];
        if ($this->targetLangs[IsoFilter::getIsoByLocaleDeepL($locale)]->supportsFormality) {
            $options['formality'] = $this->formality;
        }
        $result = $this->translator->translateText($message,
            IsoFilter::getIsoByLocaleDeepL($this->locale), IsoFilter::getIsoByLocaleDeepL($locale), $options);


        if ($result) {
            $message = $result->text;
        }

        return $message;
    }

    /** @var Language[] $sourceLangs */
    private array $sourceLangs = [];

    /** @var Language[] $targetLangs */
    private array $targetLangs = [];

    /** @var bool[] $_localeVerfiyCache */
    private array $_localeVerfiyCache = [];

    /**
     * @param string $locale
     * @return bool
     * @throws DeepLException
     * @since 03-01-2024
     * @author George van Engers <george@dewebsmid.nl>
     */
    private function verifyTranslatable(string $locale)
    {
        if (array_key_exists($locale, $this->_localeVerfiyCache)) {
            return $this->_localeVerfiyCache[$locale];
        }

        $source = IsoFilter::getIsoByLocaleDeepL(explode(',',$this->locale)[0]);
        $target = IsoFilter::getIsoByLocaleDeepL($locale);

        if (empty($this->sourceLangs)) {
            $this->sourceLangs = $this->arrayMap($this->translator->getSourceLanguages());
        }
        $src = false;
        foreach ($this->sourceLangs as $lang) {
            if (strtolower($lang->code) == strtolower($source)) {
                $src = true;
                break;
            }
        }
        if (empty($this->targetLangs)) {
            $this->targetLangs = $this->arrayMap($this->translator->getTargetLanguages());
        }
        $trg = false;
        foreach ($this->targetLangs as $lang) {
            if (strtolower($lang->code) == strtolower($target)) {
                $trg = true;
                break;
            }
        }

        $this->_localeVerfiyCache[$locale] = $src && $trg;
        return $this->_localeVerfiyCache[$locale];
    }

    /**
     * @param Language[] $getTargetLanguages
     * @return Language[]
     * @author George van Engers <vanengers@gmail.com>
     * @since 07-10-2023
     */
    private function arrayMap(array $getTargetLanguages): array
    {
        $data = [];
        foreach($getTargetLanguages as $lang) {
            $data[strtolower($lang->code)] = $lang;
        }
        return $data;
    }


}