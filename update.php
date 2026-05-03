<?php
/* Copyright (C) 2026 DoliConstDoc contributors
 *
 * GPL v3.
 */

/**
 * \file    update.php
 * \ingroup doliconstdoc
 * \brief   Check + install latest doc release from GitHub.
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/** @var Conf $conf @var DoliDB $db @var Translate $langs @var User $user */

$langs->loadLangs(array("admin", "doliconstdoc@doliconstdoc"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$installedVersion = getDolGlobalString('DOLICONSTDOC_VERSION');
$latest = null;
$err = null;

/**
 * Query GitHub API for latest release of nikube/doliconstdoc.
 *
 * @param  string|null $err Out error
 * @return array{tag:string, asset_url:?string, asset_name:?string}|null
 */
function doliconstdoc_fetch_latest_release(&$err)
{
	$url = 'https://api.github.com/repos/nikube/doliconstdoc/releases/latest';
	$resp = getURLContent($url, 'GET', '', 1, array('User-Agent: doliconstdoc-module'), array('http', 'https'), 2, 15);
	if ($resp['curl_error_no'] || (int) $resp['http_code'] !== 200) {
		$err = 'GitHub API HTTP '.((int) $resp['http_code']);
		return null;
	}
	$data = json_decode($resp['content'], true);
	if (!is_array($data) || empty($data['tag_name'])) {
		$err = 'invalid release payload';
		return null;
	}
	$assetUrl = null; $assetName = null;
	if (!empty($data['assets']) && is_array($data['assets'])) {
		foreach ($data['assets'] as $a) {
			if (!empty($a['name']) && substr($a['name'], -4) === '.sql') {
				$assetUrl = $a['browser_download_url'] ?? null;
				$assetName = $a['name'];
				break;
			}
		}
	}
	return array('tag' => $data['tag_name'], 'asset_url' => $assetUrl, 'asset_name' => $assetName);
}

if ($action === 'install') {
	$tok = GETPOST('token', 'alpha');
	if ($tok === '' || $tok !== (isset($_SESSION['newtoken']) ? $_SESSION['newtoken'] : '')) {
		setEventMessages($langs->trans('ErrorBadToken'), null, 'errors');
	} else {
		$latest = doliconstdoc_fetch_latest_release($err);
		if (!$latest || !$latest['asset_url']) {
			setEventMessages($err ?: $langs->trans('DoliConstDocUpdateNoAsset'), null, 'errors');
		} else {
			$tmpDir = $conf->doliconstdoc->dir_temp ?: ($conf->admin->dir_temp ?? sys_get_temp_dir());
			if (!is_dir($tmpDir)) dol_mkdir($tmpDir);
			$tmpFile = rtrim($tmpDir, '/').'/doliconstdoc-'.dol_sanitizeFileName($latest['tag']).'.sql';
			$dl = getURLContent($latest['asset_url'], 'GET', '', 1, array('User-Agent: doliconstdoc-module'), array('http', 'https'), 2, 60);
			if ($dl['curl_error_no'] || (int) $dl['http_code'] !== 200 || $dl['content'] === '') {
				setEventMessages($langs->trans('DoliConstDocUpdateDownloadFailed').' (HTTP '.(int) $dl['http_code'].')', null, 'errors');
			} else {
				file_put_contents($tmpFile, $dl['content']);
				$lineCount = substr_count($dl['content'], "\n");
				if ($lineCount < 100) {
					setEventMessages($langs->trans('DoliConstDocUpdateCorrupt'), null, 'errors');
				} else {
					$db->query("TRUNCATE TABLE ".MAIN_DB_PREFIX."doliconstdoc_constants");
					run_sql($tmpFile, 1, 0, 1, '', 'default', 0, 0);
					dolibarr_set_const($db, 'DOLICONSTDOC_VERSION', $latest['tag'], 'chaine', 0, '', 0);
					setEventMessages($langs->trans('DoliConstDocUpdateInstalled', $latest['tag']), null, 'mesgs');
					@unlink($tmpFile);
					header('Location: '.$_SERVER['PHP_SELF']);
					exit;
				}
			}
		}
	}
}

if ($latest === null) {
	$latest = doliconstdoc_fetch_latest_release($err);
}

llxHeader('', $langs->trans('DoliConstDocUpdate'), '');
print load_fiche_titre($langs->trans('DoliConstDocUpdate'), '', 'technic');

print '<div class="fichecenter"><table class="border centpercent">';
print '<tr><td class="titlefield">'.$langs->trans('DoliConstDocVersionInstalled').'</td><td>'.($installedVersion !== '' ? dol_escape_htmltag($installedVersion) : '—').'</td></tr>';
print '<tr><td>'.$langs->trans('DoliConstDocVersionLatest').'</td><td>';
if ($err) {
	print '<span class="error">'.dol_escape_htmltag($err).'</span>';
} elseif ($latest) {
	print dol_escape_htmltag($latest['tag']).($latest['asset_name'] ? ' <em class="opacitymedium">('.dol_escape_htmltag($latest['asset_name']).')</em>' : '');
} else {
	print '—';
}
print '</td></tr>';
print '</table></div>';

print '<br><div class="tabsAction">';
if ($latest && $latest['asset_url'] && $latest['tag'] !== $installedVersion) {
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display:inline">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="install">';
	print '<input type="submit" class="butAction" value="'.dol_escape_htmltag($langs->trans('DoliConstDocInstallUpdate', $latest['tag'])).'">';
	print '</form>';
} elseif ($latest) {
	print '<span class="opacitymedium">'.dol_escape_htmltag($langs->trans('DoliConstDocUpToDate')).'</span> ';
}
print '<a class="butAction" href="'.dol_buildpath('/doliconstdoc/admin/setup.php', 1).'">'.dol_escape_htmltag($langs->trans('Back')).'</a>';
print '</div>';

llxFooter();
$db->close();
