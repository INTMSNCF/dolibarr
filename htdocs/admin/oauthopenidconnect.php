<?php
/* Copyright (C) 2022      Henry GALVEZ      <henry@alograg.me>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * \file        htdocs/admin/oauthopenidconnect.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access api to a generic OpenId server
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Security check
if (!$user->admin) {
	accessforbidden();
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/modOauth.class.php';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
$redirect_uri = $urlwithroot.'/core/modules/oauth/openid_oauthcallback.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'oauth'));

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */
if ($action == 'update') {
	$error = 0;
	foreach ($oauthConstants as $const) {
		$constvalue = GETPOST($const);
		$url = filter_var($constvalue, FILTER_SANITIZE_URL);
		$validUrl = filter_var($url, FILTER_VALIDATE_URL);
		if (!$validUrl) {
			setEventMessages($langs->trans("URL_ERROR").": ".$langs->trans($const), null, 'errors');
		}
		$currConst = modOauth::getConstantsDefinition($const);
		if (!dolibarr_set_const($db, $const, $constvalue, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
			$error++;
		} else if ($const == 'OPENID_AUTHENTICATION_URL') {
			$MAIN_AUTHENTICATION_OPENID_URL = $constvalue;
		}
	}

	$openIdApp = GETPOST('OPENID_APP');
	$openIdAppSecret = GETPOST('OPENID_SECRET');
<<<<<<< HEAD
	$openIdScope = GETPOST('OPENID_SCOPE');
=======
>>>>>>> 9daaf0eaf7931a8ca0f247a64a1e27eb64b2b88f

	$currConst = modOauth::getConstantsDefinition('OPENID_APP');
	if (!dolibarr_set_const($db, 'OPENID_APP', $openIdApp, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
		$error++;
	}

	$currConst = modOauth::getConstantsDefinition('OPENID_SECRET');
	if (!dolibarr_set_const($db, 'OPENID_SECRET', $openIdAppSecret, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
			$error++;
	}

	if (!empty($MAIN_AUTHENTICATION_OPENID_URL) && !empty($openIdApp) && !empty($openIdAppSecret)) {
		$params = array(
			"response_type"=>"code",
<<<<<<< HEAD
			"scope"=>$openIdScope,
=======
>>>>>>> 9daaf0eaf7931a8ca0f247a64a1e27eb64b2b88f
			"client_id"=>$openIdApp,
			"redirect_uri"=>$redirect_uri,
		);
		$MAIN_AUTHENTICATION_OPENID_URL.="?".http_build_query($params);
	}

	$currConst = modOauth::getConstantsDefinition('MAIN_AUTHENTICATION_OPENID_URL');
	if ($error || !dolibarr_set_const($db, 'MAIN_AUTHENTICATION_OPENID_URL', $MAIN_AUTHENTICATION_OPENID_URL, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
		$error++;
	}

<<<<<<< HEAD
	$currConst = modOauth::getConstantsDefinition('OPENID_SCOPE');
	if ($error || !dolibarr_set_const($db, 'OPENID_SCOPE', $openIdScope, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
		$error++;
	}

=======
>>>>>>> 9daaf0eaf7931a8ca0f247a64a1e27eb64b2b88f
	$currConst = modOauth::getConstantsDefinition('OPENID_GENERIC');
	if ($error || !dolibarr_set_const($db, 'OPENID_GENERIC', 1, $currConst[1], $currConst[4], $currConst[3], $conf->entity)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

 /*
 * View
 */

llxHeader();

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

$head = oauthadmin_prepare_head();

print dol_get_fiche_head($head, 'openidconfig', '', -1, 'technic');

print '<span class="opacitymedium">'.$langs->trans("OpenIdConnectDescription").'</span><br><br>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<colgroup>';
print '<col style="width: 20%">';
print '<col style="width: 80%">';
print '</colgroup>';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("CallbackURI").'</td>';
print '<td><input style="width: 90%" type"url" value="'.$redirect_uri.'" readonly>';
print '</td></tr>';

// Api Id
print '<tr class="oddeven value">';
print '<td><label for="OPENID_APP">'.$langs->trans('OPENID_APP').'</label></td>';
print '<td><input type="text" size="100" id="OPENID_APP" name="OPENID_APP" value="'.$conf->global->OPENID_APP.'">';
print '</td></tr>';

// Api Secret
print '<tr class="oddeven value">';
print '<td><label for="OPENID_SECRET">'.$langs->trans('OPENID_SECRET').'</label></td>';
print '<td><input type="password" size="100" id="OPENID_SECRET" name="OPENID_SECRET" value="'.$conf->global->OPENID_SECRET.'">';
print '</td></tr>';

<<<<<<< HEAD
// OPENID_SCOPE
print '<tr class="oddeven value">';
print '<td><label for="OPENID_SCOPE">'.$langs->trans('OPENID_SCOPE').'</label></td>';
print '<td><input type="test" size="100" id="OPENID_SCOPE" name="OPENID_SCOPE" value="'.$conf->global->OPENID_SCOPE.'">';
print '</td></tr>';

=======
>>>>>>> 9daaf0eaf7931a8ca0f247a64a1e27eb64b2b88f
// URLs
foreach ($oauthConstants as $const) {
	print '<tr class="oddeven value">';
	print '<td><label for="'.$const.'">'.$langs->trans($const).'</label></td>';
	print '<td><input style="width: 90%" type="url" id="'.$const.'" name="'.$const.'" value="'.$conf->global->{$const}.'">';
	print '</td></tr>';
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';

// End of page
llxFooter();
$db->close();
