<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 *  \defgroup   doliconstdoc     Module DoliConstDoc
 *  \brief      DoliConstDoc module descriptor.
 *
 *  \file       core/modules/modDoliConstDoc.class.php
 *  \ingroup    doliconstdoc
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 * Description and activation class for module DoliConstDoc.
 */
class modDoliConstDoc extends DolibarrModules
{
	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Reserved range 185150–185169 (Anatole Conseil) — see https://wiki.dolibarr.org/index.php/List_of_modules_id
		$this->numero = 185159;

		$this->rights_class = 'doliconstdoc';
		$this->family = "technic";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Browse and activate Dolibarr global constants with embedded doc.";
		$this->descriptionlong = "DoliConstDoc embeds a documented catalog of Dolibarr's global constants and exposes a browser, search, hidden-settings activation form, and a hook on admin/const.php.";

		$this->editor_name = 'DoliConstDoc contributors';
		$this->editor_url = '';

		$this->version = '0.1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'technic';

		$this->module_parts = array(
			'hooks' => array('admincenter', 'adminconst'),
		);

		$this->dirs = array();
		$this->config_page_url = array("setup.php@doliconstdoc");

		$this->hidden = getDolGlobalInt('MODULE_DOLICONSTDOC_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		$this->langfiles = array("doliconstdoc@doliconstdoc");

		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(21, 0);
		$this->need_javascript_ajax = 0;

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// (key, type, value, desc, visible, scope, deleteonunactive)
		$this->const = array(
			0 => array('DOLICONSTDOC_VERSION', 'chaine', '', 'Version of the embedded constant documentation', 0, 'current', 0),
		);

		if (!isModEnabled("doliconstdoc")) {
			$conf->doliconstdoc = new stdClass();
			$conf->doliconstdoc->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = 1851590;
		$this->rights[$r][1] = 'Read DoliConstDoc catalog and activate hidden settings';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = '';
		$r++;

		// Menu — under Home → Admin tools
		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu'   => 'fk_mainmenu=home,fk_leftmenu=admintools',
			'type'      => 'left',
			'titre'     => 'DoliConstDoc',
			'mainmenu'  => 'home',
			'leftmenu'  => 'doliconstdoc',
			'url'       => '/custom/doliconstdoc/list.php',
			'langs'     => 'doliconstdoc@doliconstdoc',
			'position'  => 130,
			'enabled'   => "isModEnabled('doliconstdoc')",
			'perms'     => '$user->hasRight("doliconstdoc", "read")',
			'target'    => '',
			'user'      => 2,
		);
		$this->menu[$r++] = array(
			'fk_menu'   => 'fk_mainmenu=home,fk_leftmenu=doliconstdoc',
			'type'      => 'left',
			'titre'     => 'DoliConstDocBrowser',
			'mainmenu'  => 'home',
			'leftmenu'  => 'doliconstdoc_browser',
			'url'       => '/custom/doliconstdoc/list.php',
			'langs'     => 'doliconstdoc@doliconstdoc',
			'position'  => 131,
			'enabled'   => "isModEnabled('doliconstdoc')",
			'perms'     => '$user->hasRight("doliconstdoc", "read")',
			'target'    => '',
			'user'      => 2,
		);
		$this->menu[$r++] = array(
			'fk_menu'   => 'fk_mainmenu=home,fk_leftmenu=doliconstdoc',
			'type'      => 'left',
			'titre'     => 'Settings',
			'mainmenu'  => 'home',
			'leftmenu'  => 'doliconstdoc_setup',
			'url'       => '/custom/doliconstdoc/admin/setup.php',
			'langs'     => 'doliconstdoc@doliconstdoc',
			'position'  => 132,
			'enabled'   => "isModEnabled('doliconstdoc')",
			'perms'     => '$user->admin',
			'target'    => '',
			'user'      => 2,
		);
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param  string $options Options
	 * @return int             1 ok, 0 ko
	 */
	public function init($options = '')
	{
		$sql = array();
		$result = $this->_load_tables('/doliconstdoc/sql/');
		if ($result < 0) {
			return -1;
		}

		// Load embedded SQL data if table looks empty.
		$res = $this->db->query("SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."doliconstdoc_constants");
		$empty = true;
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj && (int) $obj->n > 0) {
				$empty = false;
			}
		}
		if ($empty) {
			$dataFile = dol_buildpath('/doliconstdoc/data/doliconstdoc.sql', 0);
			if (is_file($dataFile)) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
				run_sql($dataFile, 1, 0, 1, '', 'default', 0, 0);
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 * @param  string $options Options
	 * @return int             1 ok, 0 ko
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
