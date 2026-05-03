<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    lib/doliconstdoc.lib.php
 * \ingroup doliconstdoc
 * \brief   State + action helpers.
 */

const DOLICONSTDOC_STATE_INACTIVE = 'inactive';
const DOLICONSTDOC_STATE_DEFAULT  = 'default';
const DOLICONSTDOC_STATE_CUSTOM   = 'custom';
const DOLICONSTDOC_STATE_DISABLED = 'disabled';

/**
 * Read raw value from llx_const for the current entity.
 *
 * @param  DoliDB $db     Database
 * @param  string $name   Constant name
 * @param  int    $entity Entity id
 * @return array{exists:bool, value:?string}
 */
function doliconstdoc_read_const($db, $name, $entity)
{
	$sql = "SELECT value FROM ".MAIN_DB_PREFIX."const";
	$sql .= " WHERE name = '".$db->escape($name)."' AND entity = ".((int) $entity);
	$res = $db->query($sql);
	if (!$res) {
		return array('exists' => false, 'value' => null);
	}
	if ($db->num_rows($res) === 0) {
		return array('exists' => false, 'value' => null);
	}
	$obj = $db->fetch_object($res);
	$raw = $obj->value;
	// Try to decrypt if necessary (Dolibarr stores some const base64-ish).
	if (function_exists('dolDecrypt') && $raw !== null && $raw !== '') {
		$dec = dolDecrypt($raw);
		if ($dec !== false && $dec !== '') {
			$raw = $dec;
		}
	}
	return array('exists' => true, 'value' => $raw);
}

/**
 * Compute display state for a constant.
 *
 * @param  DoliConstDoc $c   Constant doc row
 * @param  array        $row Result of doliconstdoc_read_const
 * @return array{state:string, label:string, css:string, value:?string}
 */
function doliconstdoc_state($c, $row)
{
	if (!$row['exists']) {
		return array(
			'state' => DOLICONSTDOC_STATE_INACTIVE,
			'label' => 'Not set',
			'css'   => 'badge-status0',
			'value' => null,
		);
	}
	$value = (string) $row['value'];
	$type = strtolower((string) $c->type);

	$isDisabled = false;
	if ($type === 'bool' || $type === 'boolean') {
		$isDisabled = ($value === '0' || $value === '' || strtolower($value) === 'false');
	} elseif ($value === '') {
		$isDisabled = true;
	}
	if ($isDisabled) {
		return array(
			'state' => DOLICONSTDOC_STATE_DISABLED,
			'label' => 'Disabled',
			'css'   => 'badge-status7',
			'value' => $value,
		);
	}
	$default = (string) ($c->default_value ?? '');
	if ($default !== '' && $value === $default) {
		return array(
			'state' => DOLICONSTDOC_STATE_DEFAULT,
			'label' => 'Active (default)',
			'css'   => 'badge-status1',
			'value' => $value,
		);
	}
	return array(
		'state' => DOLICONSTDOC_STATE_CUSTOM,
		'label' => 'Active',
		'css'   => 'badge-status4',
		'value' => $value,
	);
}

/**
 * Build URL the user should be sent to when they click "Configure".
 *
 * Hidden settings → activation form. Standard with admin_ui_files →
 * first listed file. Otherwise admin/const.php.
 *
 * @param  DoliConstDoc $c Constant
 * @return array{href:string, local:bool}
 */
function doliconstdoc_action_url($c)
{
	if ((int) $c->hidden_setting === 1) {
		return array(
			'href'  => dol_buildpath('/doliconstdoc/admin/activate.php', 1).'?name='.urlencode($c->name),
			'local' => true,
		);
	}
	$files = trim((string) $c->admin_ui_files);
	if ($files !== '') {
		$first = trim(explode('|', $files)[0]);
		if ($first !== '' && strpos($first, '..') === false && strpos($first, '://') === false) {
			$first = ltrim($first, '/');
			return array('href' => DOL_URL_ROOT.'/'.$first, 'local' => false);
		}
	}
	return array('href' => DOL_URL_ROOT.'/admin/const.php', 'local' => false);
}

/**
 * Decode possible_values payload. The doc field is free text; we accept
 * a leading "enum:" hint or comma-separated list.
 *
 * @param  string|null $raw Raw possible_values text
 * @return string[]|null    null if not enumerable
 */
function doliconstdoc_parse_enum($raw)
{
	if ($raw === null) {
		return null;
	}
	$s = trim((string) $raw);
	if ($s === '') {
		return null;
	}
	if (stripos($s, 'enum:') === 0) {
		$s = trim(substr($s, 5));
		$parts = array_filter(array_map('trim', explode(',', $s)), 'strlen');
		return $parts ? array_values($parts) : null;
	}
	return null;
}

/**
 * @param  string $type Documented type
 * @return string       'bool'|'int'|'enum'|'string'|'dynamic'
 */
function doliconstdoc_form_type($type, $rawPossible)
{
	$t = strtolower(trim((string) $type));
	if ($t === 'bool' || $t === 'boolean') {
		return 'bool';
	}
	if ($t === 'int' || $t === 'integer') {
		return 'int';
	}
	if ($t === 'dynamic') {
		return 'dynamic';
	}
	if (doliconstdoc_parse_enum($rawPossible) !== null) {
		return 'enum';
	}
	return 'string';
}
