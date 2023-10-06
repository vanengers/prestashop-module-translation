<?php

namespace Vanengers\PrestashopModuleTranslation\Translate;

use DeepL\Translator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationManager
{

    /** @var MessageCatalogue $extractedCatalogue */
    private MessageCatalogue $extractedCatalogue;

    /** @var array $translateTo */
    private array $translateTo;

    /** @var string $locale */
    private string $locale;

    /** @var MessageCatalogue $newStrings */
    private MessageCatalogue $newStrings;

    /** @var array|mixed $translations */
    private array $translations = [];

    /** @var string $moduleFolder */
    private string $moduleFolder;

    /** @var MessageCatalogue[] $catalogs */
    private array $catalogs;

    /** @var ?OutputInterface $output */
    private ?OutputInterface $output = null;

    /** @var Translator $translator */
    private Translator $translator;

    public function __construct(MessageCatalogue $extracted, MessageCatalogue $newStrings, array $catalogs, string $moduleFolder,
                                string $locale, array $translateTo = [], string $api = '', string $formality = 'more')
    {
        $this->extractedCatalogue = $extracted;
        $this->newStrings = $newStrings;
        $this->translateTo = $translateTo;
        $this->locale = $locale;
        $this->moduleFolder = $moduleFolder;
        $this->catalogs = $catalogs;
        $this->formality = $formality;

        $this->translator = new Translator($api);
    }

    public function doStuff()
    {
        $this->initTranslations();
        $this->syncUpCatalogues();

        // test
        // this is only from the extracted Catalogue
        // we need to translate the addedStrings
        // and add them to the catalogue of the locale

        // when a new string is added.. it is also added to the catalogue allready.

        // other catalogs are NOT YET generated..
        // only the ones that are parsed..

        // so we need to translate the addedString

/*
        $needsToBeTranslated = [];

        $all = $this->newStrings->all();
        foreach($all as $domain => $messages) {
            foreach ($messages as $key => $value) {
                if (!array_key_exists($key, $this->translations[$this->locale])) {
                    $needsToBeTranslated[$this->locale][$key] = $value;
                }
                $this->translations[$this->locale][$key] = $value;
            }
        }

        var_dump($needsToBeTranslated);
        // Exception to needs to be translated in the $this->locale, which needs no translation

        var_dump($this->translateTo);

        foreach ($this->translateTo as $iso) {
            $locale = IsoFilter::getLocaleByIso($iso);
            if (!array_key_exists($locale, $this->translations)) {
                // we have nothing from this locale, so recreate from Catalogue from $this->locale
            }

            // we don't know whats missing the the catalogue of the translateTo catalogue.
            // also we should remove any shit from the catalogues that are invalid -> non-existing in the fresh extract

            // then we check the extracted Catalogue with everything that is missing from the translateTo catalogue
            // then all catalogues are in sync! (but none are really translated yet!)

            // We should sync up the translations.json
            // we set the removed items to: removed:true (so we still save the translations)
            // for new languages, we sync up with the extractCatalogue -> translations $this->locale

            // then when all locales in the translations are synced up...

            // we RUN THE NEW STRINGS TO THE translator and save then in the translations.json for every language in translateTo
            // then we put the tranlated strings in translations.json and save then.
            // after that we put the translations into the catalogues preserving the Meta-Data but using the actual translated String!
        }

        */

        $this->saveTranslationsToDisk();
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function initTranslations(): void
    {
        $this->output->writeln('<info>Initializing translations</info>');
        $translationsFileExists = file_exists($this->moduleFolder.'/config/translations.json');
        if (!$translationsFileExists) {
            file_put_contents($this->moduleFolder.'/config/translations.json', json_encode([], JSON_PRETTY_PRINT));
        }
        $translations = json_decode(file_get_contents($this->moduleFolder.'/config/translations.json'), true);
        $this->translations = $translations ?? [];
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function saveTranslationsToDisk(): void
    {
        $this->output->writeln('<info>Saving translations to disk: '.$this->moduleFolder.'/config/translations.json'.'</info>');
        file_put_contents($this->moduleFolder.'/config/translations.json', json_encode($this->translations, JSON_PRETTY_PRINT));
    }

    /**
     * @return array|MessageCatalogue[]
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public function getNewCatalogs()
    {
        return $this->catalogs;
    }

    /**
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function syncUpCatalogues()
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

        // there is no deletion of strings that are removed from the extracted catalogue
        // so we rebuild the Catalogues every thing from scratch..
        // meaning, local XLF files are useless.. we only use the translations.json file
        // we leave all other catalogues intact
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param int|string $id
     * @param mixed $message
     * @param int|string $domain
     * @param string|null $getLocale
     * @return string
     * @author George van Engers <george@dewebsmid.nl>
     * @since 06-10-2023
     */
    private function translate(int|string $id, mixed $message, string $getLocale) : string
    {
        if (array_key_exists($getLocale, $this->translations)) {
            if (array_key_exists($id, $this->translations[$getLocale])) {
                // we don;t need no stink'n translation for this, we have it
                return $this->translations[$getLocale][$id];
            }
        }



        if ($getLocale != $this->locale) {
            // WE SHALL TRANSLATE THIS STUFF! ONLY IF NOT ORIGINAL,, ORIGINAL DONT NEED TO TRANSLATE
            $result = null;
            try {
                $result = $this->translator->translateText($message, IsoFilter::getIsoByLocaleDeepL($this->locale), $getLocale, ['formality' => $this->formality]);
            }
            catch (\Exception $e) {
                $result = $this->translator->translateText($message, IsoFilter::getIsoByLocaleDeepL($this->locale), IsoFilter::getIsoByLocaleDeepL($getLocale));
            }

            if ($result) {
                $message = $result->text;
            }

            $this->output->writeln('<info>'.$id. ' --> '.$message.'</info>');
        }

        $this->translations[$getLocale][$id] = $message;

        return $this->translations[$getLocale][$id];
    }


}