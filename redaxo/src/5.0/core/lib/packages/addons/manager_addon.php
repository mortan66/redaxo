<?php

class rex_addonManager extends rex_packageManager
{
  static protected $class;

  /**
   * Constructor
   *
   * @param rex_addon $addon Addon
   */
  protected function __construct(rex_addon $addon)
  {
    parent::__construct($addon, 'addon_');
  }

  /* (non-PHPdoc)
   * @see rex_packageManager::checkRequirements()
   */
  protected function checkRequirements()
  {
    $state = parent::checkRequirements();

    if($state !== true)
      return $state;

    foreach($this->package->getRegisteredPlugins() as $plugin)
    {
      // do not use isAvailable() here, because parent addon isn't activated
      if($plugin->getProperty('status', false))
      {
        $pluginManager = rex_pluginManager::factory($plugin);
        $pluginManager->loadPackageInfos();
        $return = $pluginManager->checkRequirements();
        if(is_string($return) && !empty($return))
        {
          $pluginManager->deactivate();
        }
      }
    }

    return $state;
  }

  /* (non-PHPdoc)
   * @see rex_packageManager::checkDependencies()
   */
  protected function checkDependencies()
  {
    global $REX;

    $i18nPrefix = 'addon_dependencies_error_';
    $state = array();

    foreach(rex_addon::getAvailableAddons() as $addonName => $addon)
    {
      $requirements = $addon->getProperty('requires', array());
      if(isset($requirements['addons']) && is_array($requirements['addons']))
      {
        foreach($requirements['addons'] as $depName => $depAttr)
        {
          if($depName == $this->package->getName())
          {
            $state[] = rex_i18n::msg($i18nPrefix .'addon', $addonName);
          }
        }
      }

      // check if another Plugin which is installed, depends on the addon being un-installed
      foreach($addon->getAvailablePlugins() as $pluginName => $plugin)
      {
        $requirements = $plugin->getProperty('requires', array());
        if(isset($requirements['addons']) && is_array($requirements['addons']))
        {
          foreach($requirements['addons'] as $depName => $depAttr)
          {
            if($depName == $this->package->getName())
            {
              $state[] = rex_i18n::msg($i18nPrefix .'plugin', $addonName, $pluginName);
            }
          }
        }
      }
    }

    return empty($state) ? true : implode('<br />', $state);
  }

	/* (non-PHPdoc)
	 * @see rex_packageManager::addToPackageOrder()
	 */
	protected function addToPackageOrder()
  {
    parent::addToPackageOrder();

    foreach($this->package->getAvailablePlugins() as $plugin)
    {
      $pluginManager = rex_pluginManager::factory($plugin);
      $pluginManager->addToPackageOrder();
    }
  }

  /* (non-PHPdoc)
   * @see rex_packageManager::removeFromPackageOrder()
   */
  protected function removeFromPackageOrder()
  {
    parent::removeFromPackageOrder();

    foreach($this->package->getRegisteredPlugins() as $plugin)
    {
      $pluginManager = rex_pluginManager::factory($plugin);
      $pluginManager->removeFromPackageOrder($plugin);
    }
  }
}