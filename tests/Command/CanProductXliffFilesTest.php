<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Vanengers\PrestashopModuleTranslation\Command\ExtractCommand;
use Vanengers\PrestashopModuleTranslation\Tests\Helper\ReflectionHelper;
use Vanengers\PrestashopModuleTranslation\Tests\Mocks\Deepl\DeeplTranslatorMock;

class CanProductXliffFilesTest extends TestCase
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

    public function testExtractTranslatablesSaveIntoTranslationsFileTestActuallyTranslated()
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
        $finder = (new Finder())->in($args['--translations_xliff_dump_folder'])->directories();
        foreach($finder as $f) {
            foreach($translationsProcessed as $iso_code => $translation) {
                if ($iso_code == $f->getFilename()) {
                    $filename = str_replace('\\', '/', $f->getPath() . '/' . $f->getFilename());
                    $toExpectFolder = str_replace('\\', '/', $args['--translations_xliff_dump_folder'] . '/' . $iso_code);
                    $this->assertEquals($toExpectFolder, $filename);
                    $finderF = (new Finder())->in($args['--translations_xliff_dump_folder'])->files();
                    $this->assertTrue($finderF->hasResults() && $finderF->count() > 0);
                }
            }
        }
        $fs->remove($args['--translations_config_file']);
        $fs->remove($args['--translations_xliff_dump_folder']);
    }

    public function testExtractTranslatablesSaveIntoTranslationsFileTestActuallyTranslatedInSomeObsucreFolderSet()
    {
        $args = $this->args;

        $translations = json_decode(file_get_contents(__DIR__.'/../Mocks/extraction_test/translations_test.json'), true);

        $args = [
            '--extraction_dir' => __DIR__.'/../Mocks/extraction_test/ps_test_module',
            '--module_name' => 'ps_test_module',
            '--deepl_key' => 'test_deepl_key',
            '--translations_config_file' => __DIR__.'/../Mocks/extraction_test/translations_test-tempfile.json',
            '--translations_xliff_dump_folder' => __DIR__.'/../Mocks/extraction_test/testjantje23/subtest1/tsuib/translations_blabla',
            '--base_locale' => 'en-GB',
            '--translate_to' => ['nl-NL'],
            '--formality' => 'more',
            '--extraction_types' => ['php', 'smarty', 'twig'],
        ];

        $input = new ArrayInput($args);
        $input->bind($this->command->getDefinition());

        $this->command->run(
            $input,
            new ConsoleOutput()
        );

        $translationsProcessed = json_decode(file_get_contents($args['--translations_config_file']), true);

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $finder = (new Finder())->in($args['--translations_xliff_dump_folder'])->directories();
        foreach($finder as $f) {
            foreach($translationsProcessed as $iso_code => $translation) {
                if ($iso_code == $f->getFilename()) {
                    $filename = str_replace('\\', '/', $f->getPath() . '/' . $f->getFilename());
                    $toExpectFolder = str_replace('\\', '/', $args['--translations_xliff_dump_folder'] . '/' . $iso_code);
                    $this->assertEquals($toExpectFolder, $filename);
                    $finderF = (new Finder())->in($args['--translations_xliff_dump_folder'])->files();
                    $this->assertTrue($finderF->hasResults() && $finderF->count() > 0);
                }
            }
        }
        $fs->remove($args['--translations_config_file']);
        $fs->remove($args['--translations_xliff_dump_folder']);
        $fs->remove( __DIR__.'/../Mocks/extraction_test/testjantje23');
    }

    public function testXliffFilesContainALLTheTranslationsIntTotal()
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
        $finder = (new Finder())->in($args['--translations_xliff_dump_folder'])->directories();

        $transExisting = [];
        foreach($translations as $iso_code => $translation) {
            foreach ($translation as $translated) {
                $transExisting[$translated] = false;
            }
        }

        foreach($finder as $f) {
            foreach($translations as $iso_code => $translation) {
                if ($iso_code == $f->getFilename()) {
                    $filename = str_replace('\\', '/', $f->getPath() . '/' . $f->getFilename());
                    $toExpectFolder = str_replace('\\', '/', $args['--translations_xliff_dump_folder'] . '/' . $iso_code);
                    $this->assertEquals($toExpectFolder, $filename);
                    $finderF = (new Finder())->in($args['--translations_xliff_dump_folder'])->files();
                    $this->assertTrue($finderF->hasResults() && $finderF->count() > 0);

                    $finderF = (new Finder())->in($filename)->files();
                    foreach($finderF as $file) {
                        $contentsXlifFile = file_get_contents($file->getPath().'/'. $file->getFilename());
                        foreach($transExisting as $translated => $marked) {
                            if (!$marked && str_contains($contentsXlifFile, $translated)) {
                                $transExisting[$translated] = true;
                            }
                        }
                    }

                }
            }
        }

        foreach($transExisting as $trans => $marked) {
            $this->assertTrue($marked, 'Translation "'.$trans.'" not found in any xliff file');
        }

        $fs->remove($args['--translations_config_file']);
        $fs->remove($args['--translations_xliff_dump_folder']);
    }
}
