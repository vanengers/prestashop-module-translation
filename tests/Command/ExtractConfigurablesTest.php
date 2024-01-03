<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Vanengers\PrestashopModuleTranslation\Command\ExtractCommand;
use Vanengers\PrestashopModuleTranslation\Tests\Helper\ReflectionHelper;

class ExtractConfigurablesTest extends TestCase
{
    private $args = [
        '--extraction_dir' => __DIR__.'/../Mocks/test_extraction_dir',
        '--module_name' => 'test_module_name',
        '--deepl_key' => 'test_deepl_key',
        '--translations_config_file' => 'test_translations_config_file.json',
        '--translations_xliff_dump_folder' => __DIR__.'/../Mocks/xlifftest',
        '--base_locale' => 'nl-NL',
        '--translate_to' => ['de-DE','fr-FR'],
        '--formality' => 'more',
        '--extraction_types' => ['php', 'smarty', 'twig'],
        '--prestashop_translations_config_file' => __DIR__.'/../Mocks/test.yml',
    ];

    public function testConfigPassedCorrectly()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $command->initialize(
            $input,
            new ConsoleOutput()
        );

        $this->assertEquals($args['--extraction_dir'], ReflectionHelper::getProperty($command, 'extraction_dir'));
        $this->assertEquals($args['--module_name'], ReflectionHelper::getProperty($command, 'module_name'));
        $this->assertEquals($args['--deepl_key'], ReflectionHelper::getProperty($command, 'deepl_key'));
        $this->assertEquals($args['--translations_config_file'], ReflectionHelper::getProperty($command, 'translations_config_file'));
        $this->assertEquals($args['--translations_xliff_dump_folder'], ReflectionHelper::getProperty($command, 'translations_xliff_dump_folder'));
        $this->assertEquals($args['--base_locale'], ReflectionHelper::getProperty($command, 'base_locale'));
        $this->assertContains(reset($args['--translate_to']), ReflectionHelper::getProperty($command, 'translate_to'));
        $this->assertContains(end($args['--translate_to']), ReflectionHelper::getProperty($command, 'translate_to'));
        $this->assertEquals($args['--formality'], ReflectionHelper::getProperty($command, 'formality'));
        $this->assertContains(reset($args['--extraction_types']), ReflectionHelper::getProperty($command, 'extraction_types'));
        $this->assertContains(end($args['--extraction_types']), ReflectionHelper::getProperty($command, 'extraction_types'));
        $this->assertEquals($args['--prestashop_translations_config_file'], ReflectionHelper::getProperty($command, 'prestashop_translations_config_file'));
    }

    public function testConfigNonExistingExtractionDirThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--extraction_dir'] = 'test_extraction_dir';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Extraction dir does not exist');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigNonExtractionDirThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--extraction_dir']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Extraction dir does not exist');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigEmptyDeeplKeyThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--deepl_key'] = '';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Empty deepl key, please enter a valid API key for Deepl');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigNoDeeplKeyThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--deepl_key']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Empty deepl key, please enter a valid API key for Deepl');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigInvalidBaseLocaleThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--base_locale'] = 'hgbs-gndr7';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid locale:');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigNoBaseLocaleThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--base_locale']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Option base_locale is required');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigNoModuleNameThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--module_name']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Option module_name is required');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigInvalidTranslateToLocaleThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--translate_to'] = 'hgbs-gndr7';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid translate to locale:');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigNoTranslateToLocaleThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--translate_to']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Option translate_to is required');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigInvalidFormalityThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--formality'] = 'blabla';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid formality:');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigInvalidExtractionTypeThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--extraction_types'] = 'blabla';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid extraction type:');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testConfigPsTranslationThrowsException()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        $args['--prestashop_translations_config_file'] = 'blabla';

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PrestaShop translations config file does not exist:');

        $command->initialize(
            $input,
            new ConsoleOutput()
        );
    }

    public function testWeCanDoWithoutATranslationConfigFile()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--translations_config_file']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $command->initialize(
            $input,
            new ConsoleOutput()
        );

        $this->assertTrue(true);
    }

    public function testWeCanDoWithoutAXliffFolderSetting()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--translations_xliff_dump_folder']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $command->initialize(
            $input,
            new ConsoleOutput()
        );

        $this->assertTrue(true);
    }

    public function testWeCanDoWithoutAFormalitySetting()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--formality']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $command->initialize(
            $input,
            new ConsoleOutput()
        );

        $this->assertTrue(true);
    }

    public function testWeCanDoWithoutAExtraction_typesSetting()
    {
        $command = new ExtractCommand();
        ReflectionHelper::setMethodAccessToPublic($command, 'configure')
            ->invoke($command,NULL);

        $args = $this->args;
        unset($args['--extraction_types']);

        $input = new ArrayInput($args);
        $input->bind($command->getDefinition());

        $command->initialize(
            $input,
            new ConsoleOutput()
        );

        $this->assertTrue(true);
    }
}
