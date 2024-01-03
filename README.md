# prestashop-module-translation
Extract from your PrestaShop module PHP, Twig and Smarty files and translate them with Deepl.
Export them to Xlf files to be automatically imported at module installation.
Uses languages locales from PrestaShop.

## Requirements
You need to add a custom repository to your composer.json
``"repositories": [
{
"type": "git",
"url": "https://github.com/PrestaShopCorp/module-translation-tool.git",
"symlink": false
}
],``

## Install
`` composer require --dev vanengers/prestashop-module-translation``

## Usage
``` 
php vendor/bin/extract --extraction_dir=./path/to/ps_module --module_name=PS_MODULE --deepl_key=DEEPLAPIKEY --translate_to=de-DE,ru-RU,it-IT,es-ES,pl-PL --base_locale=en-GB
```

## Optional Parameters
``` --translations_config_file=./path/to/translations_config.json``` <br>
The saved translations.json file from a previous run. <br>

```--translations_xliff_dump_folder=./path/to/ps_module/translations``` <br>
The files generated for PrestaShop, this should be inside the module/translations folder. <br>

```--deepl_key=DEEPLAPIKEY``` <br>
A valid Deepl API key with usage leftover. <br>

```--formality=more``` <br>
See https://www.deepl.com/nl/docs-api/translate-text for formality options
This option does not apply to all languages.

```--extraction_types=php,twig,smarty``` <br>
Use this to extract only certain file types. <br>

## Remarks
```--translate_to=de-DE,ru-RU,it-IT,es-ES,pl-PL``` <br>
The DEEPL iso codes required format: ISO 3166-1 see: https://en.wikipedia.org/wiki/ISO_3166-1 <br>


## Create your Deepl api key at DeepL
https://www.deepl.com/nl/pro-api?cta=header-pro-api

