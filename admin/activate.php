<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    admin/activate.php
 * \ingroup doliconstdoc
 * \brief   Activate / edit / delete a hidden setting. Admin only — writes
 *          to llx_const.
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../class/doliconstdoc.class.php';
require_once __DIR__.'/../lib/doliconstdoc.lib.php';

/** @var Conf $conf @var DoliDB $db @var Translate $langs @var User $user */

$langs->loadLangs(array("admin", "doliconstdoc@doliconstdoc"));

if (!$user->admin) {
	accessforbidden();
}

$name = trim((string) GETPOST('name', 'alphanohtml'));
$action = (string) GETPOST('action', 'aZ09');

if ($name === '') {
	header('Location: '.dol_buildpath('/doliconstdoc/list.php', 1));
	exit;
}

$c = new DoliConstDoc($db);
if ($c->fetchByName($name) <= 0) {
	header('Location: '.dol_buildpath('/doliconstdoc/list.php', 1));
	exit;
}
if ((int) $c->hidden_setting !== 1) {
	// Only hidden settings are editable here.
	$action = doliconstdoc_action_url($c);
	header('Location: '.$action['href']);
	exit;
}

$row = doliconstdoc_read_const($db, $c->name, $conf->entity);
$state = doliconstdoc_state($c, $row);
$formType = doliconstdoc_form_type($c->type, $c->possible_values);

if ($action === 'save' || $action === 'delete') {
	$tok = GETPOST('token', 'alpha');
	if ($tok === '' || $tok !== (isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '')) {
		setEventMessages($langs->trans('ErrorBadToken'), null, 'errors');
	} elseif ($action === 'delete') {
		$ok = dolibarr_del_const($db, $c->name, $conf->entity);
		if ($ok > 0) {
			setEventMessages($langs->trans('DoliConstDocDeactivated', $c->name), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('DoliConstDocSaveFailed'), null, 'errors');
		}
		header('Location: '.dol_buildpath('/doliconstdoc/card.php', 1).'?name='.urlencode($c->name));
		exit;
	} else {
		// save
		if ($formType === 'dynamic') {
			setEventMessages($langs->trans('DoliConstDocCannotEditDynamic'), null, 'errors');
		} else {
			$value = '';
			if ($formType === 'bool') {
				$value = GETPOST('value', 'int') ? '1' : '0';
			} elseif ($formType === 'int') {
				$value = (string) ((int) GETPOST('value', 'int'));
			} elseif ($formType === 'enum') {
				$value = (string) GETPOST('value', 'alphanohtml');
			} else {
				$value = (string) GETPOST('value', 'restricthtml');
			}
			$dolType = ($formType === 'int' ? 'int' : 'chaine');
			$note = 'Activated via DoliConstDoc';
			$ok = dolibarr_set_const($db, $c->name, $value, $dolType, 1, $note, $conf->entity);
			if ($ok > 0) {
				setEventMessages($langs->trans('DoliConstDocSaved', $c->name), null, 'mesgs');
				header('Location: '.dol_buildpath('/doliconstdoc/card.php', 1).'?name='.urlencode($c->name));
				exit;
			} else {
				setEventMessages($langs->trans('DoliConstDocSaveFailed'), null, 'errors');
			}
		}
	}
}

// Render form
$current = $row['exists'] ? (string) $row['value'] : (string) ($c->default_value ?? '');

llxHeader('', $langs->trans('DoliConstDocActivateTitle', $c->name), '');
print load_fiche_titre($langs->trans('DoliConstDocActivateTitle', $c->name), '', 'technic');

if ($c->purpose)     print '<p>'.dol_escape_htmltag((string) $c->purpose).'</p>';
if ($c->description) print '<p class="opacitymedium">'.dol_escape_htmltag((string) $c->description).'</p>';

if ($formType === 'dynamic') {
	print '<div class="warning">'.dol_escape_htmltag($langs->trans('DoliConstDocDynamicWarning')).'</div>';
}

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="name" value="'.dol_escape_htmltag($c->name).'">';
print '<input type="hidden" name="action" value="save">';

print '<div class="fichecenter"><table class="border centpercent">';
print '<tr><td class="titlefield">'.$langs->trans('Name').'</td><td><code>'.dol_escape_htmltag($c->name).'</code></td></tr>';
print '<tr><td>'.$langs->trans('Type').'</td><td><code>'.dol_escape_htmltag((string) $c->type).'</code></td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocDefault').'</td><td>'.($c->default_value !== null && $c->default_value !== '' ? '<code>'.dol_escape_htmltag((string) $c->default_value).'</code>' : '<span class="opacitymedium">—</span>').'</td></tr>';
print '<tr><td>'.$langs->trans('Value').'</td><td>';
if ($formType === 'bool') {
	$checked = ($current === '1' || strtolower($current) === 'true') ? ' checked' : '';
	print '<input type="hidden" name="value" value="0">';
	print '<input type="checkbox" name="value" value="1"'.$checked.'>';
} elseif ($formType === 'int') {
	print '<input type="number" class="flat" name="value" value="'.dol_escape_htmltag($current).'">';
} elseif ($formType === 'enum') {
	$choices = doliconstdoc_parse_enum($c->possible_values) ?: array();
	print '<select class="flat" name="value">';
	foreach ($choices as $opt) {
		$sel = ($opt === $current ? ' selected' : '');
		print '<option value="'.dol_escape_htmltag($opt).'"'.$sel.'>'.dol_escape_htmltag($opt).'</option>';
	}
	print '</select>';
} elseif ($formType === 'dynamic') {
	print '<input type="text" class="flat minwidth300" name="value" value="'.dol_escape_htmltag($current).'" disabled>';
} else {
	print '<input type="text" class="flat minwidth300" name="value" value="'.dol_escape_htmltag($current).'">';
}
print '</td></tr>';
print '</table></div>';

print '<br><div class="center">';
if ($formType !== 'dynamic') {
	print '<input type="submit" class="button button-save" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
}
print ' <a class="button button-cancel" href="'.dol_buildpath('/doliconstdoc/card.php', 1).'?name='.urlencode($c->name).'">'.dol_escape_htmltag($langs->trans('Cancel')).'</a>';
print '</div>';
print '</form>';

if ($row['exists']) {
	print '<br><form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return confirm(\''.dol_escape_js($langs->trans('DoliConstDocConfirmDelete', $c->name)).'\');">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="name" value="'.dol_escape_htmltag($c->name).'">';
	print '<input type="hidden" name="action" value="delete">';
	print '<div class="center"><input type="submit" class="button button-cancel" value="'.dol_escape_htmltag($langs->trans('DoliConstDocDeactivate')).'"></div>';
	print '</form>';
}

llxFooter();
$db->close();
