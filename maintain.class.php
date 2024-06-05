<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * This class is used to expose maintenance methods to the plugins manager
 * It must extends PluginMaintain and be named "PLUGINID_maintain"
 * where PLUGINID is the directory name of your plugin.
 */
class quick_fav_maintain extends PluginMaintain
{
  private $table;
  private $dir;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id); // always call parent constructor

    global $prefixeTable;

    // Class members can't be declared with computed values so initialization is done here
    $this->table = $prefixeTable . 'favorites_category';
  }

  /**
   * Plugin installation
   *
   * Perform here all needed step for the plugin installation such as create default config,
   * add database tables, add fields to existing tables, create local folders...
   */
  function install($plugin_version, &$errors=array())
  {
    // add a new table
    pwg_query('
CREATE TABLE IF NOT EXISTS `'. $this->table .'` (
  `user_id` mediumint(8) unsigned NOT NULL,
  `category_id` smallint(5) unsigned NOT NULL,
  `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `favorites_category_ui1` (`user_id`, `category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;');
  }

  /**
   * Plugin activation
   *
   * This function is triggered after installation, by manual activation or after a plugin update
   * for this last case you must manage updates tasks of your plugin in this function
   */
  function activate($plugin_version, &$errors=array())
  {
  }

  /**
   * Plugin deactivation
   *
   * Triggered before uninstallation or by manual deactivation
   */
  function deactivate()
  {
  }

  /**
   * Plugin (auto)update
   *
   * This function is called when Piwigo detects that the registered version of
   * the plugin is older than the version exposed in main.inc.php
   * Thus it's called after a plugin update from admin panel or a manual update by FTP
   */
  function update($old_version, $new_version, &$errors=array())
  {
    // I (mistic100) chosed to handle install and update in the same method
    // you are free to do otherwize
    $this->install($new_version, $errors);
  }

  /**
   * Plugin uninstallation
   *
   * Perform here all cleaning tasks when the plugin is removed
   * you should revert all changes made in 'install'
   */
  function uninstall()
  {
    // delete table
    pwg_query('DROP TABLE `'. $this->table .'`;');
  }
}