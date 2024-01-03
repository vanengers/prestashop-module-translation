<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Mocks\extraction_test\ps_test_module;

class ps_test_module extends \Module
{
    public function __construct()
    {
        $this->name = 'ps_test_module';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Vanengers';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans('This is the test Display Name', [], 'Modules.ps_test_module.Config');
        $this->description = $this->trans('This is the test Description', [], 'Modules.ps_test_module.Config');
    }
}