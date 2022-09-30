<?php
/* Copyright (C) 2007-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 */

/**
 *      \file       htdocs/core/login/functions_openid.php
 *      \ingroup    core
 *      \brief      Authentication functions for OpenId mode
 */

include_once DOL_DOCUMENT_ROOT . '/core/class/openid.class.php';


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_openid($usertotest, $passwordtotest, $entitytotest)
{
	global $db, $conf, $langs;

	dol_syslog("functions_openid::check_user_password_openid usertotest=" . $usertotest);

	$login = false;

	dol_syslog("functions_openid::check_user_password_openid actionlogin=" . json_encode(GETPOST('actionlogin')), LOG_DEBUG);
	if (GETPOST('actionlogin') == 'login') return false;

	dol_syslog("functions_openid::check_user_password_openid OPENID_GENERIC=" . json_encode($conf->global->OPENID_GENERIC), LOG_DEBUG);
	if (!empty($conf->global->OPENID_GENERIC)) {
		// Connecting to generic server
		require_once DOL_DOCUMENT_ROOT . '/core/modules/oauth/GenericOpenIdController.class.php';
		$control = new GenericOpenIdController($db, $conf);
		$user = $control->serverCallback(GETPOST('code'));
		if (is_object($user))
			$login = $user->login;
		dol_syslog("functions_openid::check_user_password_openid user=" . json_encode($user), LOG_DEBUG);
	}

	return $login;
}
