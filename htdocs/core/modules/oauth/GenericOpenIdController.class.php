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
 *      \file       htdocs/core/modules/oauth/GenericOpenId.class.php
 *      \ingroup    oauth
 *      \brief      Class to bridge OpenId Server
 */

require_once (DOL_DOCUMENT_ROOT ?: '../../..') . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/OAuth/bootstrap.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/oauth/GenericOpenIdService.class.php';

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Exception\Exception as OAuthException;

class GenericOpenIdController
{

	/**
	 * Undocumented variable
	 *
	 * @var \TraceableDB
	 */
	protected $db;
	/**
	 * Undocumented variable
	 *
	 * @var \Conf
	 */
	protected $conf;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\Common\Http\Uri\UriFactory
	 */
	protected $uriFactory;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\Common\Http\Uri\UriInterface
	 */
	protected $currentUri;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\Common\Http\Client\CurlClient
	 */
	protected $httpClient;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\ServiceFactory
	 */
	protected $serviceFactory;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\Common\Storage\TokenStorageInterface
	 */
	protected $storage;
	/**
	 * Undocumented variable
	 *
	 * @var \OAuth\Common\Consumer\CredentialsInterface
	 */
	protected $credentials;
	/**
	 * Undocumented variable
	 *
	 * @var GenericOpenIdService
	 */
	protected $apiService;
	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db, $conf)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('Start'), LOG_DEBUG);
		// If defined (Ie: Apache with Linux)
		if (isset($_SERVER["SCRIPT_URI"])) {
			$dolibarr_main_url_root = $_SERVER["SCRIPT_URI"];
		} elseif (isset($_SERVER["SERVER_URL"]) && isset($_SERVER["DOCUMENT_URI"])) {
			// If defined (Ie: Apache with Caudium)
			$dolibarr_main_url_root = $_SERVER["SERVER_URL"] . $_SERVER["DOCUMENT_URI"];
		} else {
			// If SCRIPT_URI, SERVER_URL, DOCUMENT_URI not defined (Ie: Apache 2.0.44 for Windows)
			$proto = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https' : 'http';
			if (!empty($_SERVER["HTTP_HOST"])) {
				$serverport = $_SERVER["HTTP_HOST"];
			} elseif (!empty($_SERVER["SERVER_NAME"])) {
				$serverport = $_SERVER["SERVER_NAME"];
			} else {
				$serverport = 'localhost';
			}
			$dolibarr_main_url_root = $proto . "://" . $serverport . $_SERVER["SCRIPT_NAME"];
		}
		// Clean proposed URL
		// We assume /install to be under /htdocs, so we get the parent path of the current URL
		$dolibarr_main_url_root = dirname(dirname($dolibarr_main_url_root));
		$urlWithoutUrlRoot = preg_replace('/' . preg_quote(constant('DOL_MAIN_URL_ROOT'), '/') . '$/i', '', trim($dolibarr_main_url_root));
		$urlWithRoot = $urlWithoutUrlRoot . constant('DOL_URL_ROOT'); // This is to use external domain name found into config file
		$this->db = $db;
		$this->conf = $conf;
		$this->uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
		$this->currentUri = $this->uriFactory->createFromAbsolute($urlWithRoot . '/oauth/openid_oauthcallback.php');
		$this->httpClient = new \OAuth\Common\Http\Client\CurlClient();
		$this->httpClient->setTimeout(300);
		$this->storage = new DoliStorage($db, $conf);
		$this->credentials = new Credentials(
			$conf->global->OPENID_APP,
			$conf->global->OPENID_SECRET,
			$this->currentUri->getAbsoluteUri()
		);
		$this->apiService = new GenericOpenIdService(
			$this->credentials,
			$this->httpClient,
			$this->storage,
			explode(' ', $conf->global->OPENID_SCOPE)
		);
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' even = ' . json_encode('end'), LOG_DEBUG);
	}

	/**
	 * Process server callback
	 *
	 * @param string $code
	 */
	public function serverCallback($code = null)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('Start'), LOG_DEBUG);
		if (empty($code) || !is_string($code)) {
			dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('no code'), LOG_DEBUG);
			header('Location: ' . $this->apiService->getAuthorizationUri());
			exit();
		}
		try {
			$this->apiService->requestAccessToken($code);
			//Pedir la informacion del usuario
			$openIdUser = (array)$this->apiService->requestUserInfo();
			dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode($openIdUser), LOG_DEBUG);
			//buscar el usuario en Dolibar
			$doliUser = $this->searchUser([
				$openIdUser['login'],
				$openIdUser['email'],
			]);
			if (!$doliUser) {
				//Si no existe; agregarlo
				$doliUser = $this->insertUser($openIdUser);
			}
			if (!$doliUser)
				throw new OAuthException('User connection imposible');

			// loguear el usuario
			$this->startDolibarrSession($doliUser);
			dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('user logged'), LOG_DEBUG);

			return $doliUser;
		} catch (Exception $err) {
			dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' error = ' . json_encode($err->getMessage()), LOG_ERR);
			dol_print_error($err->getMessage());
		}
		return false;
	}

	private function searchUser($filter)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('called'), LOG_DEBUG);
		$sql = <<<SQL
SELECT *
FROM %suser
WHERE %s
ORDER BY rowid ASC
SQL;

		if (!is_array($filter))
			throw new OAuthException('Bad filter');
		$stripFilter = array_unique(array_filter($filter));
		$where = [
			'login in ("' . implode('", "', $stripFilter) . '")',
			'email in ("' . implode('", "', $stripFilter) . '")',
		];
		$resql = $this->db->query(sprintf($sql, MAIN_DB_PREFIX, implode(' OR ', $where)));
		if ($resql) {
			return $this->db->fetch_object($resql);
		} else {
			dol_print_error($this->db);
		}

		return false;
	}

	/**
	 * Insert a new User with default rights
	 */
	private function insertUser($data)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('called'), LOG_DEBUG);
		$date = new DateTime();
		$date->getTimestamp();
		$cdate = $date->format('Y-m-d H:i:s');
		$login = $data['login'];
		$pass = self::generatePass();
		$lastname = $data['family_name'] ?: $data['login'];
		$firstname = $data['first_name'] ?: $data['login'];
		$email = $data['email'];
		$openid = $data['login'];
		$pass_crypted = dol_hash($pass);
		$sql = <<<SQL
INSERT INTO %suser
		(datec, login, pass, pass_crypted, lastname, firstname, email, openid)
	VALUES
		("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s");
SQL;
		if (!$this->db->query(sprintf($sql, MAIN_DB_PREFIX, $cdate, $login, $pass, $pass_crypted, $lastname, $firstname, $email, $openid))) {
			dol_print_error($this->db);
			return false;
		}
		$userID = $this->db->last_insert_id(MAIN_DB_PREFIX . "user");
		$this->setDefautPrivileges($userID);

		return $this->searchUser([$login, $email]);
	}

	/**
	 * Build a ramdom string
	 */
	public static function generatePass()
	{
		$generated_password = '';
		$generated_password = getRandomPassword(false);
		return $generated_password;
	}

	/**
	 * Set default privileges for the new user
	 */
	private function setDefautPrivileges($id)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('called'), LOG_DEBUG);

		$sql = "SELECT id FROM " . MAIN_DB_PREFIX . "rights_def";
		$sql .= " WHERE bydefault = 1";
		$sql .= " AND entity = " . $this->conf->entity;

		$resql = $this->db->query($sql);
		$rd = [];
		if ($resql) {

			while ($row = $this->db->fetch_row($resql)) {
				$rd[] = $row[0];
			}

			$this->db->free($resql);
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "user_rights WHERE fk_user = $id";
			$this->db->query($sql);
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "user_rights (fk_user, fk_id) VALUES ";
			$sqlValues = [];
			for ($i = count($rd); $i <= 0; $i--) {
				$sqlValues[] = "$id, $rd[$i])";
			}
			$sql .= implode(', ', $sqlValues);
		}

		return count($rd);
	}

	private function startDolibarrSession($user)
	{
		dol_syslog('SSO - ' . __CLASS__ . "::" . __FUNCTION__ . ' line ' . __LINE__ . ' event = ' . json_encode('called'), LOG_DEBUG);
		$theuser = (array)$user;
		$_SESSION["dol_login"] = trim($theuser['login']);
		$_POST["username"] = $_SESSION["dol_login"];
		// //$_SESSION["dol_authmode"]='dolibarr';
		// $_SESSION["dol_tz"]='';
		// $_SESSION["dol_tz_string"]=trim(date_default_timezone_get());
		// $_SESSION["dol_dst"]='0';
		// $_SESSION["dol_dst_observed"]='0';
		// $_SESSION["dol_dst_first"]='';
		// $_SESSION["dol_dst_second"]='';
		// $_SESSION["dol_screenwidth"]='';
		// $_SESSION["dol_screenheight"]='';
		// $_SESSION["dol_company"]=$this->conf->global->MAIN_INFO_SOCIETE_NOM;
		// $_SESSION["dol_entity"]=$this->conf->entity;
	}
}
