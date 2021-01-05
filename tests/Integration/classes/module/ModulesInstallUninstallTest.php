<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

namespace PrestaShop\PrestaShop\Tests\Integration;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use ModuleCore;
use Context;
use Employee;

class ModulesInstallUninstallTest extends IntegrationTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        ModuleCore::updateTranslationsAfterInstall(false);
        Context::getContext()->employee = new Employee();
        Context::getContext()->employee->id = 1;
        Context::getContext()->employee->id_profile = _PS_ADMIN_PROFILE_;
    }

    public function listModulesOnDisk()
    {
        $modules = array();

        foreach (scandir(_PS_MODULE_DIR_) as $entry) {
            if ($entry[0] !== '.') {
                if (file_exists(_PS_MODULE_DIR_.$entry.DIRECTORY_SEPARATOR.$entry.'.php')) {
                    $modules[] = array($entry);
                }
            }
        }

        return $modules;
    }

    /**
     * @dataProvider listModulesOnDisk
     * @group slow
     */
    public function testInstallationAndUnInstallation($moduleName)
    {
        // Ignore ps_checkout module for unit testing due to a fatal error:
        // Starting test 'PrestaShop\PrestaShop\Tests\Integration\ModulesInstallUninstallTest::testInstallationAndUnInstallation with data set #4 ('ps_checkout')'.
        // PHP Fatal error:  Call to undefined method Symfony\Component\Yaml\Parser::parseFile() in /home/vagrant/code/prestashops/github-PrestaShop-1.6/modules/ps_checkout/vendor/symfony/dependency-injection/Loader/YamlFileLoader.php on line 664
        if ($moduleName === "ps_checkout") {
            return;
        }

        $module = ModuleCore::getInstanceByName($moduleName);
        if ($module->id) {
            $this->assertTrue((bool)$module->uninstall(), 'Module uninstall failed : '.$moduleName);
            $this->assertTrue((bool)$module->install(), 'Module install failed : '.$moduleName);
        } else {
            $this->assertTrue((bool)$module->install(), 'Module install failed : '.$moduleName);
            $this->assertTrue((bool)$module->uninstall(), 'Module uninstall failed : '.$moduleName);
        }
    }
}
