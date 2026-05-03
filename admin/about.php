<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/** @var Translate $langs @var User $user */
$langs->loadLangs(array("admin", "doliconstdoc@doliconstdoc"));
if (!$user->admin) accessforbidden();

llxHeader('', $langs->trans('DoliConstDocAbout'), '');
print load_fiche_titre($langs->trans('DoliConstDocAbout'), '', 'technic');
print '<p>'.dol_escape_htmltag($langs->trans('DoliConstDocAboutText')).'</p>';
print '<p><a href="https://github.com/nikube/doliconstdoc-module" target="_blank">github.com/nikube/doliconstdoc-module</a></p>';
llxFooter();
$db->close();
