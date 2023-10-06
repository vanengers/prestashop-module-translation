<?php

namespace Vanengers\PrestashopModuleTranslation\Helper;

use PrestaShop\TranslationToolsBundle\Smarty;
use PrestaShop\TranslationToolsBundle\Translation\Compiler\Smarty\TranslationTemplateCompiler;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\SmartyExtractor;
use Smarty_Internal_Template;
use Smarty_Internal_Templatelexer;
use Smarty_Internal_Templateparser;
use SmartyException;

class SmartyBuilder
{
    /**
     * @return SmartyExtractor
     * @throws SmartyException
     * @since 06-10-2023
     * @author George van Engers <george@dewebsmid.nl>
     */
    public static function build() : SmartyExtractor
    {
        $smarty = new Smarty();
        $translationTemplateCompiler = new TranslationTemplateCompiler(Smarty_Internal_Templatelexer::class, Smarty_Internal_Templateparser::class, $smarty);
        $translationTemplateCompiler->template = new Smarty_Internal_Template('module', $smarty);

        $smartyLexer = new Smarty_Internal_Templatelexer('',$translationTemplateCompiler);
        $smartyParser = new Smarty_Internal_Templateparser($smartyLexer, $translationTemplateCompiler);
        return new SmartyExtractor($translationTemplateCompiler, SmartyExtractor::INCLUDE_EXTERNAL_MODULES);
    }
}