<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    list.php
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

$searchName    = trim((string) GETPOST('search_name', 'alphanohtml'));
$searchPurpose = trim((string) GETPOST('search_purpose', 'alphanohtml'));
$module    = (string) GETPOST('search_module', 'alphanohtml');
$hiddenFlt = (string) GETPOST('search_hidden', 'alphanohtml'); // ''|hidden|standard
$stateFlt  = (string) GETPOST('search_state', 'alphanohtml');  // ''|active|inactive|disabled
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = GETPOSTINT('limit') ? GETPOSTINT('limit') : 50;
$offset    = $limit * $page;
$sortfield = GETPOST('sortfield', 'alphanohtml') ?: 'name';
$sortorder = GETPOST('sortorder', 'alphanohtml') ?: 'asc';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$searchName = ''; $searchPurpose = ''; $module = ''; $hiddenFlt = ''; $stateFlt = '';
}

$dao = new DoliConstDoc($db);

$filters = array(
	'search_name'    => $searchName,
	'search_purpose' => $searchPurpose,
	'module' => $module,
	'hidden_only'   => ($hiddenFlt === 'hidden'),
	'standard_only' => ($hiddenFlt === 'standard'),
	'state'         => $stateFlt,
	'entity'        => $conf->entity,
);

$res = $dao->fetchList($filters, $sortfield, $sortorder, $limit, $offset);
$rows = $res['rows'];
$total = $res['total'];

llxHeader('', $langs->trans('DoliConstDocBrowserTitle'), '');

$paramUrl = '';
if ($searchName !== '')    $paramUrl .= '&search_name='.urlencode($searchName);
if ($searchPurpose !== '') $paramUrl .= '&search_purpose='.urlencode($searchPurpose);
if ($module !== '')        $paramUrl .= '&search_module='.urlencode($module);
if ($hiddenFlt !== '')     $paramUrl .= '&search_hidden='.urlencode($hiddenFlt);
if ($stateFlt !== '')      $paramUrl .= '&search_state='.urlencode($stateFlt);

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

print '<div class="div-table-responsive"><table class="tagtable nobottomiftotal liste centpercent">';

// Per-column search row (Dolibarr standard liste_titre_filter)
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth150" name="search_name" value="'.dol_escape_htmltag($searchName).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth200" name="search_purpose" value="'.dol_escape_htmltag($searchPurpose).'"></td>';
print '<td class="liste_titre"><select name="search_module" class="flat maxwidth150">';
print '<option value=""></option>';
foreach ($dao->listModules() as $m) {
	$sel = $m === $module ? ' selected' : '';
	print '<option value="'.dol_escape_htmltag($m).'"'.$sel.'>'.dol_escape_htmltag($m).'</option>';
}
print '</select></td>';
print '<td class="liste_titre center"><select name="search_hidden" class="flat">';
$hiddens = array(
	''         => '',
	'hidden'   => $langs->trans('Yes'),
	'standard' => $langs->trans('No'),
);
foreach ($hiddens as $k => $v) {
	$sel = ($k === $hiddenFlt ? ' selected' : '');
	print '<option value="'.dol_escape_htmltag($k).'"'.$sel.'>'.dol_escape_htmltag($v).'</option>';
}
print '</select></td>';
print '<td class="liste_titre"><select name="search_state" class="flat">';
$stateOpts = array(
	''         => '',
	'active'   => $langs->trans('DoliConstDocState_custom'),
	'inactive' => $langs->trans('DoliConstDocState_inactive'),
	'disabled' => $langs->trans('DoliConstDocState_disabled'),
);
foreach ($stateOpts as $k => $v) {
	$sel = ($k === $stateFlt ? ' selected' : '');
	print '<option value="'.dol_escape_htmltag($k).'"'.$sel.'>'.dol_escape_htmltag($v).'</option>';
}
print '</select></td>';
print '<td class="liste_titre"></td>'; // Value
print '<td class="liste_titre right">';
$searchpicto = function_exists('searchpicto') ? searchpicto('', '') : '';
print '<button type="submit" class="liste_titre button_search reposition" name="button_search" value="x" title="'.dol_escape_htmltag($langs->trans('Search')).'"><span class="fa fa-search"></span></button>';
print '<button type="submit" class="liste_titre button_removefilter reposition" name="button_removefilter" value="x" title="'.dol_escape_htmltag($langs->trans('RemoveFilter')).'"><span class="fa fa-times-circle"></span></button>';
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('Name'),       0, $_SERVER['PHP_SELF'], 'name',         '', $paramUrl, '', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('DoliConstDocPurpose'), 0, $_SERVER['PHP_SELF'], '',     '', $paramUrl);
print getTitleFieldOfList($langs->trans('Module'),     0, $_SERVER['PHP_SELF'], 'module',       '', $paramUrl, '', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('Hidden'),     0, $_SERVER['PHP_SELF'], 'hidden_setting', '', $paramUrl, 'class="center"', $sortfield, $sortorder);
print getTitleFieldOfList($langs->trans('Status'),     0, $_SERVER['PHP_SELF'], '',             '', $paramUrl);
print getTitleFieldOfList($langs->trans('Value'),      0, $_SERVER['PHP_SELF'], '',             '', $paramUrl);
print getTitleFieldOfList($langs->trans('Action'),     0, $_SERVER['PHP_SELF'], '',             '', $paramUrl, 'class="right"');
print '</tr>';

if (!$rows) {
	print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.dol_escape_htmltag($langs->trans('DoliConstDocNoResult')).'</td></tr>';
}

foreach ($rows as $c) {
	$state = doliconstdoc_state($c, doliconstdoc_read_const($db, $c->name, $conf->entity));
	$action = doliconstdoc_action_url($c);

	$detailHref = dol_buildpath('/doliconstdoc/card.php', 1).'?name='.urlencode($c->name);

	print '<tr class="oddeven">';
	print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($c->name).'"><a href="'.$detailHref.'"><code>'.dol_escape_htmltag($c->name).'</code></a></td>';
	$purpose = (string) $c->purpose;
	print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($purpose).'">'.dol_escape_htmltag(dol_trunc($purpose, 100)).'</td>';
	print '<td>'.dol_escape_htmltag((string) $c->module).'</td>';
	$hiddenCell = ((int) $c->hidden_setting === 1)
		? '<span class="badge badge-status0">'.dol_escape_htmltag($langs->trans('Yes')).'</span>'
		: '<span class="opacitymedium">—</span>';
	print '<td class="center">'.$hiddenCell.'</td>';
	print '<td><span class="badge '.dol_escape_htmltag($state['css']).'">'.dol_escape_htmltag($langs->trans('DoliConstDocState_'.$state['state'])).'</span></td>';
	print '<td>'.($state['value'] !== null ? '<code>'.dol_escape_htmltag(dol_trunc((string) $state['value'], 60)).'</code>' : '').'</td>';
	$btnLabel = ((int) $c->hidden_setting === 1)
		? ($state['state'] === DOLICONSTDOC_STATE_INACTIVE ? $langs->trans('DoliConstDocBtnOn') : $langs->trans('DoliConstDocBtnEdit'))
		: $langs->trans('DoliConstDocBtnOpen');
	$cls = $action['local'] ? 'button-add' : 'button';
	print '<td class="right nowraponall"><a class="butAction '.$cls.'" style="display:inline-block;min-width:70px;text-align:center;" href="'.$action['href'].'">'.dol_escape_htmltag($btnLabel).'</a></td>';
	print '</tr>';
}

print '</table></div>';
print '</form>';

llxFooter();
$db->close();
