<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Application;

use DI\Container;
use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Application\Kernel\PluginList;
use Piwik\Application\Kernel\PluginList\IniPluginList;
use Piwik\Application\Kernel\GlobalSettingsProvider\IniSettingsProvider;
use Piwik\Container\ContainerFactory;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;

/**
 * TODO
 */
class Environment
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $definitions;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var GlobalSettingsProvider
     */
    private $globalSettingsProvider;

    /**
     * @var PluginList
     */
    private $pluginList;

    public function __construct($environment, array $definitions = array())
    {
        $this->environment = $environment;
        $this->definitions = $definitions;
    }

    public function init()
    {
        $this->container = $this->createContainer();

        StaticContainer::set($this->container);

        Piwik::postEvent('Environment.bootstrapped'); // this event should be removed eventually
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @link http://php-di.org/doc/container-configuration.html
     */
    private function createContainer()
    {
        $pluginList = $this->getPluginListCached();
        $settings = $this->getGlobalSettingsCached();
        $definitions = array_merge(StaticContainer::getDefinitons(), $this->definitions);

        $containerFactory = new ContainerFactory($pluginList, $settings, $this->environment, $definitions);
        return $containerFactory->create();
    }

    protected function getGlobalSettingsCached()
    {
        if ($this->globalSettingsProvider === null) {
            $this->globalSettingsProvider = $this->getGlobalSettings();
        }
        return $this->globalSettingsProvider;
    }

    protected function getPluginListCached()
    {
        if ($this->pluginList === null) {
            $this->pluginList = $this->getPluginList();
        }
        return $this->pluginList;
    }

    protected function getGlobalSettings()
    {
        return IniSettingsProvider::getSingletonInstance();
    }

    protected function getPluginList()
    {
        return new IniPluginList($this->getGlobalSettingsCached()); // TODO: in tracker should only load tracker plugins.
    }
}