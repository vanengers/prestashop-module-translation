# prestashop-module-translation
Extract from your PrestaShop module PHP, Twig and Smarty files and translate them with Deepl.
Export them to Xlf files to be automatically imported at module installation.
Uses languages locales from PrestaShop.

## Install
`` composer require --dev vanengers/prestashop-module-translation``

## Useage
`` php vendor/bin/extract extract MODULENAME DEEPLAPIKEY --translate_to=de-DE,ru-RU,it-IT,es-ES,pl-PL --locale=en-GB
``

### Optional Parameters
``--translations_sub_folder=translations``
``--formality=more`` 
See https://www.deepl.com/nl/docs-api/translate-text for formality options
This option does not apply to all languages.

### Create your Deepl api key at DeepL
https://www.deepl.com/nl/pro-api?cta=header-pro-api

