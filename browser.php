<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    browser.php
 * \ingroup doliconstdoc
 * \brief   Constants browser (list + filters).
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/class/doliconstdoc.class.php';
require_once __DIR__.'/lib/doliconstdoc.lib.php';

/** @var Conf $conf @var DoliDB $db @var Translate $langs @var User $user */

$langs->loadLangs(array("admin", "doliconstdoc@doliconstdoc"));

if (!$user->hasRight('doliconstdoc', 'read') && !$user->admin) {
	accessforbidden();
}

$search   = trim((string) GETPOST('search', 'alphanohtml'));
$module   = (string) GETPOST('module', 'alphanohtml');
$type     = (string) GETPOST('type', 'alphanohtml');
$stateFlt = (string) GETPOST('statefilter', 'alphanohtml'); // all|active|inactive|hidden|standard
$page     = max(0, (int) GETPOST('page', 'int'));
$limit    = (int) (GETPOST('limit', 'int') ?: $conf->liste_limit);
$offset   = $limit * $page;
$sortfield = GETPOST('sortfield', 'alphanohtml') ?: 'name';
$sortorder = GETPOST('sortorder', 'alphanohtml') ?: 'asc';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search = ''; $module = ''; $type = ''; $stateFlt = '';
}

$dao = new DoliConstDoc($db);

$filters = array(
	'search' => $search,
	'module' => $module,
	'type'   => $type,
	'hidden_only'   => ($stateFlt === 'hidden'),
	'standard_only' => ($stateFlt === 'standard'),
);

$res = $dao->fetchList($filters, $sortfield, $sortorder, $limit, $offset);
$rows = $res['rows'];
$total = $res['total'];

llxHeader('', $langs->trans('DoliConstDocBrowserTitle'), '');

$paramUrl = '';
if ($search !== '')   $paramUrl .= '&search='.urlencode($search);
if ($module !== '')   $paramUrl .= '&module='.urlencode($module);
if ($type !== '')     $paramUrl .= '&type='.urlencode($type);
if ($stateFlt !== '') $paramUrl .= '&statefilter='.urlencode($stateFlt);

print load_fiche_titre($langs->trans('DoliConstDocBrowserTitle'), '', 'technic');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" name="filterform">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';

print_barre_liste(
	$langs->trans('DoliConstDocConstants'),
	$page,
	$_SERVER['PHP_SELF'],
	$paramUrl,
	$sortfield,
	$sortorder,
	'',
	count($rows),
	$total,
	'technic',
	0,
	'',
	'',
	$limit
);

// Filters row
print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield"><input type="text" class="flat maxwidth200" name="search" value="'.dol_escape_htmltag($search).'" placeholder="'.dol_escape_htmltag($langs->trans('Search')).'"></div>';

print '<div class="divsearchfield"><select name="module" class="flat minwidth150">';
print '<option value="">'.dol_escape_htmltag($langs->trans('Module')).'</option>';
foreach ($dao->listModules() as $m) {
	$sel = $m === $module ? ' selected' : '';
	print '<option value="'.dol_escape_htmltag($m).'"'.$sel.'>'.dol_escape_htmltag($m).'</option>';
}
print '</select></div>';

print '<div class="divsearchfield"><select name="type" class="flat minwidth100">';
$types = array('' => $langs->trans('Type'), 'bool' => 'bool', 'int' => 'int', 'string' => 'string', 'enum' => 'enum');
foreach ($types as $k => $v) {
	$sel = ($k === $type ? ' selected' : '');
	print '<option value="'.dol_escape_htmltag($k).'"'.$sel.'>'.dol_escape_htmltag($v).'</option>';
}
print '</select></div>';

print '<div class="divsearchfield"><select name="statefilter" class="flat minwidth150">';
$states = array(
	''         => $langs->trans('DoliConstDocFilterAll'),
	'hidden'   => $langs->trans('DoliConstDocFilterHidden'),
	'standard' => $langs->trans('DoliConstDocFilterStandard'),
);
foreach ($states as $k => $v) {
	$sel = ($k === $stateFlt ? ' selected' : '');
	print '<option value="'.dol_escape_htmltag($k).'"'.$sel.'>'.dol_escape_htmltag($v).'</option>';
}
print '</select></div>';

print '<div class="divsearchfield"><button type="submit" class="button button-search" name="button_search" value="x"><span class="fa fa-search"></span></button>';
print ' <button type="submit" class="button button-removefilter" name="button_removefilter" value="x"><span class="fa fa-times-circle"></span></button></div>';
print '</div>';

print '<div class="div-table-responsive"><table class="tagtable nobottomiftotal liste centpercent">';
print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('Name'),       0, $_SERVER['PHP_SELF'], 'name',         '', $paramUrl, '', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('Module'),     0, $_SERVER['PHP_SELF'], 'module',       '', $paramUrl, '', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('Type'),       0, $_SERVER['PHP_SELF'], 'type',         '', $paramUrl, '', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('Status'),     0, $_SERVER['PHP_SELF'], '',             '', $paramUrl);
print getTitleFieldOfList($langs->trans('Value'),      0, $_SERVER['PHP_SELF'], '',             '', $paramUrl);
print getTitleFieldOfList($langs->trans('Action'),     0, $_SERVER['PHP_SELF'], '',             '', $paramUrl, 'class="right"');
print '</tr>';

if (!$rows) {
	print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.dol_escape_htmltag($langs->trans('DoliConstDocNoResult')).'</td></tr>';
}

foreach ($rows as $c) {
	$state = doliconstdoc_state($c, doliconstdoc_read_const($db, $c->name, $conf->entity));
	$action = doliconstdoc_action_url($c);

	// Apply state filter post-fetch when active/inactive selected
	if ($stateFlt === 'active' && $state['state'] === DOLICONSTDOC_STATE_INACTIVE) continue;
	if ($stateFlt === 'inactive' && $state['state'] !== DOLICONSTDOC_STATE_INACTIVE) continue;

	$detailHref = dol_buildpath('/doliconstdoc/const_view.php', 1).'?name='.urlencode($c->name);
	$hiddenBadge = ((int) $c->hidden_setting === 1) ? ' <span class="badge badge-status0" title="hidden">H</span>' : '';

	print '<tr class="oddeven">';
	print '<td><a href="'.$detailHref.'"><code>'.dol_escape_htmltag($c->name).'</code></a>'.$hiddenBadge.'</td>';
	print '<td>'.dol_escape_htmltag((string) $c->module).'</td>';
	print '<td>'.dol_escape_htmltag((string) $c->type).'</td>';
	print '<td><span class="badge '.dol_escape_htmltag($state['css']).'">'.dol_escape_htmltag($langs->trans('DoliConstDocState_'.$state['state'])).'</span></td>';
	print '<td>'.($state['value'] !== null ? '<code>'.dol_escape_htmltag(dol_trunc((string) $state['value'], 60)).'</code>' : '').'</td>';
	$btnLabel = ((int) $c->hidden_setting === 1)
		? ($state['state'] === DOLICONSTDOC_STATE_INACTIVE ? $langs->trans('DoliConstDocActivate') : $langs->trans('DoliConstDocEdit'))
		: $langs->trans('DoliConstDocOpenNative');
	$cls = $action['local'] ? 'button-add' : 'button';
	print '<td class="right"><a class="butAction '.$cls.'" href="'.$action['href'].'">'.dol_escape_htmltag($btnLabel).'</a></td>';
	print '</tr>';
}

print '</table></div>';
print '</form>';

llxFooter();
$db->close();
