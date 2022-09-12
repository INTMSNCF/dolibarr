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
 *      \file       htdocs/core/modules/oauth/GenericOpenIdService.class.php
 *      \ingroup    oauth
 *      \brief      Class to bridge OpenId Server
 */


require_once (DOL_DOCUMENT_ROOT ?: '../../..') . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;

class GenericOpenIdService extends AbstractService
{
	const SCOPE_OPEN_ID = 'openid';
	const SCOPE_PROFILE = 'profile';
	const SCOPE_EMAIL = 'email';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
		global $conf;
        return new Uri($conf->global->OPENID_AUTHENTICATION_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
		global $conf;
        return new Uri($conf->global->OPENID_TOKEN_URL);
    }

    /**
     * Get user info endpoint URL
	 *
	 * @return Uri
     */
    public function getUserInfoEndpoint()
    {
		global $conf;
        return new Uri($conf->global->OPENID_USER_INFO_URL);
    }

    /**
     * Return any additional headers always needed for this service implementation's OAuth calls.
     *
     * @return array
     */
    protected function getExtraOAuthHeaders()
    {
        return array(
			'Content-Type'=>'application/x-www-form-urlencoded',
			'Authorization'=>'Basic ' . base64_encode($this->credentials->getConsumerId() . ':'.$this->credentials->getConsumerSecret()),
		);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAccessToken($code, $state = null)
    {
        if (null !== $state) {
            $this->validateAuthorizationState($state);
        }

        $bodyParams = array(
            'code'          => $code,
            'redirect_uri'  => $this->credentials->getCallbackUrl(),
            'grant_type'    => 'authorization_code',
        );

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $bodyParams,
            $this->getExtraOAuthHeaders()
        );

        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }

	/**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }

	/**
	 * Get user info
	 *
	 * @param TokenInterface $token
	 */
	public function requestUserInfo($token = null)
	{
		if(empty($token))
		{
			$token = $this->storage->retrieveAccessToken($this->service());
		}
		$headers = array(
			'Authorization' => 'Bearer ' . $token->getAccessToken(),
		);
		try {
			$responseBody = $this->httpClient->retrieveResponse(
				$this->getUserInfoEndpoint(),
				[],
				$headers
			);
		} catch (Exception $err) {
			throw new Exception('OpenId Server Error: ' . $err->getMessage());

		}

		return json_decode($responseBody);
	}
}
