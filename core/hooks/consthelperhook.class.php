<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    core/hooks/consthelperhook.class.php
 * \ingroup doliconstdoc
 * \brief   Adds a banner pointing at the DoliConstDoc browser.
 *
 * NOTE on admin/const.php: core admin/const.php does not call executeHooks(),
 * so we cannot inject directly there in stock Dolibarr. We hook the admin
 * home (admincenter context) which is the entry point users hit before
 * admin/const.php. A core file patch (eg via the HotPatcher module) can wire
 * a real hook into admin/const.php if desired.
 */

class ConstHelperHook
{
	/** @var DoliDB */
	public $db;
	/** @var array */
	public $results = array();
	/** @var string */
	public $resprints;

	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * printTopRightMenu hook — admincenter context.
	 *
	 * @param  array  $parameters Hook parameters
	 * @param  object $object     Object
	 * @param  string $action     Action
	 * @return int                0 keep going, 1 replace
	 */
	public function printTopRightMenu($parameters, &$object, &$action)
	{
		return 0;
	}

	/**
	 * Render banner inside the admin home content area.
	 * Triggered on context admincenter via printAdminTabsHead / addStatisticLine.
	 *
	 * @param  array  $parameters Hook parameters
	 * @param  object $object     Object (maybe null)
	 * @param  string $action     Action
	 * @param  HookManager $hookmanager Hook manager
	 * @return int                0 keep going
	 */
	public function addStatisticLine($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db;
		if (!is_array($hookmanager->contextarray) || !in_array('admincenter', $hookmanager->contextarray, true)) {
			return 0;
		}
		$langs->load('doliconstdoc@doliconstdoc');
		$count = 0;
		$res = $db->query("SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."doliconstdoc_constants");
		if ($res) {
			$obj = $db->fetch_object($res);
			$count = (int) $obj->n;
		}
		$url = dol_buildpath('/doliconstdoc/browser.php', 1);
		$this->resprints = '<div class="info" style="margin:8px 0;"><span class="fa fa-book"></span> '
			.dol_escape_htmltag($langs->trans('DoliConstDocBannerText', $count))
			.' <a class="butAction" style="margin-left:8px;" href="'.$url.'">'.dol_escape_htmltag($langs->trans('DoliConstDocBannerOpen')).'</a></div>';
		return 0;
	}
}
