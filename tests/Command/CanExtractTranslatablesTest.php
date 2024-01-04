<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Vanengers\PrestashopModuleTranslation\Command\ExtractCommand;
use Vanengers\PrestashopModuleTranslation\Tests\Helper\ReflectionHelper;
use Vanengers\PrestashopModuleTranslation\Tests\Mocks\Deepl\DeeplTranslatorMock;

class CanExtractTranslatablesTest extends TestCase
{
    private $args = [
        '--extraction_dir' => __DIR__.'/../Mocks/extraction_test/ps_test_module',
        '--module_name' => 'ps_test_module',
        '--deepl_key' => 'test_deepl_key',
        '--translations_config_file' => __DIR__.'/../Mocks/extraction_test/translations_test-tempfile.json',
        '--translations_xliff_dump_folder' => __DIR__.'/../Mocks/extraction_test/ps_test_module/translations_blabla',
        '--base_locale' => 'en-GB',
        '--translate_to' => ['nl-NL'],
        '--formality' => 'more',
        '--extraction_types' => ['php', 'smarty', 'twig'],
    ];

    private ?ExtractCommand $command = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ExtractCommand();
        ReflectionHelper::setProperty($this->command, 'translator', new DeeplTranslatorMock('not_used'));
    }

    public function testExtractTranslatablesAndSaveIntoTranslationsFile()
    {
        $args = $this->args;

        $translations = json_decode(file_get_contents(__DIR__.'/../Mocks/extraction_test/translations_test.json'), true);

        $input = new ArrayInput($args);
        $input->bind($this->command->getDefinition());

        $this->command->run(
            $input,
            new ConsoleOutput()
        );

        $translationsProcessed = json_decode(file_get_contents($args['--translations_config_file']), true);

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->remove($args['--translations_config_file']);
        $fs->remove($args['--translations_xliff_dump_folder']);

        $this->assertCount(2, $translationsProcessed);
    }
}
