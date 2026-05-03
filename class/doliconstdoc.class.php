<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    class/doliconstdoc.class.php
 * \ingroup doliconstdoc
 * \brief   Data access for doliconstdoc constants table.
 */

class DoliConstDoc
{
	/** @var DoliDB */
	public $db;

	public $rowid;
	public $name;
	public $type;
	public $default_value;
	public $module;
	public $purpose;
	public $description;
	public $impact;
	public $possible_values;
	public $hidden_setting;
	public $hidden_setting_guess;
	public $admin_ui_files;
	public $conf_php_wiring;
	public $doc_quality;
	public $confidence;

	const TABLE = 'doliconstdoc_constants';

	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Fetch one row by name.
	 *
	 * @param  string $name Constant name
	 * @return int          1 ok, 0 not found, -1 ko
	 */
	public function fetchByName($name)
	{
		$sql = "SELECT rowid, name, type, default_value, module, purpose, description, impact,";
		$sql .= " possible_values, hidden_setting, hidden_setting_guess, admin_ui_files,";
		$sql .= " conf_php_wiring, doc_quality, confidence";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE;
		$sql .= " WHERE name = '".$this->db->escape($name)."'";
		$res = $this->db->query($sql);
		if (!$res) {
			return -1;
		}
		if ($this->db->num_rows($res) === 0) {
			return 0;
		}
		$obj = $this->db->fetch_object($res);
		$this->fillFrom($obj);
		return 1;
	}

	/**
	 * List with filters.
	 *
	 * @param  array $filters search, module, type, hidden_only, standard_only
	 * @param  string $sortfield Field name
	 * @param  string $sortorder asc/desc
	 * @param  int   $limit  Page size
	 * @param  int   $offset Page offset
	 * @return array{rows:array<self>, total:int}
	 */
	public function fetchList($filters = array(), $sortfield = 'name', $sortorder = 'asc', $limit = 50, $offset = 0)
	{
		$where = ' WHERE 1=1';
		if (!empty($filters['search'])) {
			$q = $this->db->escape($filters['search']);
			$where .= " AND (name LIKE '%".$q."%' OR purpose LIKE '%".$q."%' OR description LIKE '%".$q."%')";
		}
		if (!empty($filters['module'])) {
			$where .= " AND module = '".$this->db->escape($filters['module'])."'";
		}
		if (!empty($filters['type'])) {
			$where .= " AND type = '".$this->db->escape($filters['type'])."'";
		}
		if (!empty($filters['hidden_only'])) {
			$where .= " AND hidden_setting = 1";
		} elseif (!empty($filters['standard_only'])) {
			$where .= " AND hidden_setting = 0";
		}

		$allowedSort = array('name', 'module', 'type', 'doc_quality');
		if (!in_array($sortfield, $allowedSort, true)) {
			$sortfield = 'name';
		}
		$sortorder = strtolower($sortorder) === 'desc' ? 'DESC' : 'ASC';

		$totalSql = "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX.self::TABLE.$where;
		$total = 0;
		$resT = $this->db->query($totalSql);
		if ($resT) {
			$objT = $this->db->fetch_object($resT);
			$total = (int) $objT->n;
		}

		$sql = "SELECT rowid, name, type, default_value, module, purpose, description, impact,";
		$sql .= " possible_values, hidden_setting, hidden_setting_guess, admin_ui_files,";
		$sql .= " conf_php_wiring, doc_quality, confidence";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE.$where;
		$sql .= " ORDER BY ".$sortfield." ".$sortorder;
		$sql .= " LIMIT ".((int) $limit)." OFFSET ".((int) $offset);

		$rows = array();
		$res = $this->db->query($sql);
		if ($res) {
			while (($obj = $this->db->fetch_object($res))) {
				$o = new self($this->db);
				$o->fillFrom($obj);
				$rows[] = $o;
			}
		}
		return array('rows' => $rows, 'total' => $total);
	}

	/**
	 * Distinct module list (for the filter dropdown).
	 *
	 * @return string[]
	 */
	public function listModules()
	{
		$sql = "SELECT DISTINCT module FROM ".MAIN_DB_PREFIX.self::TABLE." WHERE module IS NOT NULL AND module <> '' ORDER BY module";
		$res = $this->db->query($sql);
		$out = array();
		if ($res) {
			while (($obj = $this->db->fetch_object($res))) {
				$out[] = $obj->module;
			}
		}
		return $out;
	}

	/**
	 * @return int Total count
	 */
	public function countAll()
	{
		$res = $this->db->query("SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX.self::TABLE);
		if (!$res) {
			return 0;
		}
		$obj = $this->db->fetch_object($res);
		return (int) $obj->n;
	}

	private function fillFrom($obj)
	{
		$this->rowid = (int) $obj->rowid;
		$this->name = $obj->name;
		$this->type = $obj->type;
		$this->default_value = $obj->default_value;
		$this->module = $obj->module;
		$this->purpose = $obj->purpose;
		$this->description = $obj->description;
		$this->impact = $obj->impact;
		$this->possible_values = $obj->possible_values;
		$this->hidden_setting = (int) $obj->hidden_setting;
		$this->hidden_setting_guess = (int) $obj->hidden_setting_guess;
		$this->admin_ui_files = $obj->admin_ui_files;
		$this->conf_php_wiring = $obj->conf_php_wiring;
		$this->doc_quality = (int) $obj->doc_quality;
		$this->confidence = $obj->confidence;
	}
}
