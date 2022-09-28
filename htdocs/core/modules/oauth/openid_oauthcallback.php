<?php
/*
 * Copyright (C) 2022       Henry GALVEZ      <henry@alograg.me>
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
 *      \file       htdocs/core/modules/oauth/openid_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

$_GET['openid_mode'] = 'auth';
//$_GET['actionlogin'] = 'login';
require '../../../main.inc.php';

$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));

header('Location: ' . $urlwithouturlroot . '?mainmenu=home&leftmenu=home');
exit();
