<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    admin/setup.php
 * \ingroup doliconstdoc
 * \brief   Module configuration.
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../class/doliconstdoc.class.php';

/** @var Conf $conf @var DoliDB $db @var Translate $langs @var User $user */

$langs->loadLangs(array("admin", "doliconstdoc@doliconstdoc"));

if (!$user->admin) {
	accessforbidden();
}

$dao = new DoliConstDoc($db);
$count = $dao->countAll();
$version = getDolGlobalString('DOLICONSTDOC_VERSION');

llxHeader('', $langs->trans('DoliConstDocSetup'), '');
print load_fiche_titre($langs->trans('DoliConstDocSetup'), '', 'technic');

print '<div class="fichecenter"><div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans('DoliConstDocVersion').'</td><td>'.($version !== '' ? dol_escape_htmltag($version) : '<span class="opacitymedium">'.$langs->trans('DoliConstDocVersionUnknown').'</span>').'</td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocConstantsCount').'</td><td>'.$count.'</td></tr>';
print '</table></div>';

print '<br><div class="tabsAction">';
print '<a class="butAction" href="'.dol_buildpath('/doliconstdoc/admin/update.php', 1).'">'.dol_escape_htmltag($langs->trans('DoliConstDocCheckForUpdate')).'</a>';
print '<a class="butAction" href="'.dol_buildpath('/doliconstdoc/list.php', 1).'">'.dol_escape_htmltag($langs->trans('DoliConstDocBrowserTitle')).'</a>';
print '</div>';

llxFooter();
$db->close();
