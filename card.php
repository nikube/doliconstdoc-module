<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    card.php
 * \ingroup doliconstdoc
 * \brief   Detail page for one constant.
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

$name = trim((string) GETPOST('name', 'alphanohtml'));
if ($name === '') {
	header('Location: '.dol_buildpath('/doliconstdoc/list.php', 1));
	exit;
}

$c = new DoliConstDoc($db);
if ($c->fetchByName($name) <= 0) {
	llxHeader('', $langs->trans('DoliConstDocNotFound'), '');
	print load_fiche_titre($langs->trans('DoliConstDocNotFound'), '', 'technic');
	print '<div class="warning">'.dol_escape_htmltag($langs->trans('DoliConstDocConstantUnknown', $name)).'</div>';
	print '<a class="butAction" href="'.dol_buildpath('/doliconstdoc/list.php', 1).'">'.dol_escape_htmltag($langs->trans('Back')).'</a>';
	llxFooter();
	$db->close();
	return;
}

$row = doliconstdoc_read_const($db, $c->name, $conf->entity);
$state = doliconstdoc_state($c, $row);
$action = doliconstdoc_action_url($c);

llxHeader('', $c->name, '');
print load_fiche_titre($c->name.' <span class="badge '.dol_escape_htmltag($state['css']).'">'.dol_escape_htmltag($langs->trans('DoliConstDocState_'.$state['state'])).'</span>', '', 'technic');

print '<div class="fichecenter"><div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans('Module').'</td><td>'.dol_escape_htmltag((string) $c->module).'</td></tr>';
print '<tr><td>'.$langs->trans('Type').'</td><td><code>'.dol_escape_htmltag((string) $c->type).'</code></td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocHidden').'</td><td>'.((int) $c->hidden_setting === 1 ? $langs->trans('Yes') : $langs->trans('No')).((int) $c->hidden_setting_guess === 1 ? ' <em class="opacitymedium">('.$langs->trans('DoliConstDocGuessed').')</em>' : '').'</td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocConfidence').'</td><td>'.dol_escape_htmltag((string) $c->confidence).' / '.$langs->trans('DoliConstDocDocQuality').' '.(int) $c->doc_quality.'</td></tr>';
print '</table></div>';

print '<br>';
print '<div class="info">';
if ($c->purpose)     print '<p><strong>'.$langs->trans('DoliConstDocPurpose').' :</strong> '.dol_escape_htmltag((string) $c->purpose).'</p>';
if ($c->description) print '<p><strong>'.$langs->trans('DoliConstDocDescription').' :</strong> '.dol_escape_htmltag((string) $c->description).'</p>';
if ($c->impact)      print '<p><strong>'.$langs->trans('DoliConstDocImpact').' :</strong> '.dol_escape_htmltag((string) $c->impact).'</p>';
print '</div>';

print '<div class="fichecenter"><div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans('DoliConstDocCurrentValue').'</td><td>'.($state['value'] !== null ? '<code>'.dol_escape_htmltag((string) $state['value']).'</code>' : '<span class="opacitymedium">'.$langs->trans('DoliConstDocNotSet').'</span>').'</td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocDefault').'</td><td>'.($c->default_value !== null && $c->default_value !== '' ? '<code>'.dol_escape_htmltag((string) $c->default_value).'</code>' : '<span class="opacitymedium">—</span>').'</td></tr>';
if ($c->possible_values) {
	print '<tr><td>'.$langs->trans('DoliConstDocPossibleValues').'</td><td>'.dol_escape_htmltag((string) $c->possible_values).'</td></tr>';
}
if ($c->conf_php_wiring) {
	print '<tr><td>'.$langs->trans('DoliConstDocConfPhpWiring').'</td><td><code>'.dol_escape_htmltag((string) $c->conf_php_wiring).'</code></td></tr>';
}
if ($c->admin_ui_files) {
	print '<tr><td>'.$langs->trans('DoliConstDocAdminUIFiles').'</td><td><code>'.dol_escape_htmltag((string) $c->admin_ui_files).'</code></td></tr>';
}
print '</table></div>';

print '<br><div class="tabsAction">';
$btnLabel = ((int) $c->hidden_setting === 1)
	? ($state['state'] === DOLICONSTDOC_STATE_INACTIVE ? $langs->trans('DoliConstDocActivate') : $langs->trans('DoliConstDocEdit'))
	: $langs->trans('DoliConstDocOpenNative');
print '<a class="butAction" href="'.$action['href'].'">'.dol_escape_htmltag($btnLabel).'</a>';
print '<a class="butAction" href="'.dol_buildpath('/doliconstdoc/list.php', 1).'">'.dol_escape_htmltag($langs->trans('Back')).'</a>';
print '</div>';

llxFooter();
$db->close();
