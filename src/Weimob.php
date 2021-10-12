<?php

	namespace zjweimob\weimob\src;
	require_once "error.wm.php";

	use app\components\InvalidDataException;
	use ParameterError;
	use zjweimob\weimob\utils\Utils;
	use yii\base\InvalidParamException;

	class Weimob extends BaseWm
	{

		/**`
		 * 为应用的唯一身份标识
		 *
		 * @var string
		 */
		public $client_id;

		/**
		 * 为对应的调用身份密钥。
		 *
		 * @var string
		 */
		public $client_secret;

		/**
		 * 回调地址
		 *
		 * @var string
		 */
		public $redirect_uri;

		/**
		 * 数据缓存前缀
		 *
		 * @var string
		 */
		protected $cachePrefix = 'cache_wei_mob';

		/**
		 */
		public function init ()
		{
			Utils::checkNotEmptyStr($this->client_id, 'client_id');
			Utils::checkNotEmptyStr($this->client_secret, 'client_secret');
			Utils::checkNotEmptyStr($this->redirect_uri, 'redirect_uri');
		}

		/**
		 * 获取缓存键值
		 *
		 * @param $name
		 *
		 * @return string
		 */
		protected function getCacheKey ($name)
		{
			return $this->cachePrefix . '_' . $this->client_id . '_' . $name;
		}

		/**
		 * 获取 accesstoken 不用主动调用
		 *
		 * @param bool $force
		 *
		 * @return string|void
		 *
		 * @throws \ParameterError
		 * @throws \app\components\InvalidDataException
		 */
		public function GetAccessToken ($force = false)
		{
			$time = time();
			if (!Utils::notEmptyStr($this->access_token) || $this->access_token_expire < $time || $force) {
				$result = !Utils::notEmptyStr($this->access_token) && !$force ? $this->getCache($this->getCacheKey('access_token')) : false;
				if ($result === false) {
					$result = $this->RefreshAccessToken();
				} else {
					if ($result['expire'] < $time) {
						$result = $this->RefreshAccessToken();
					}
				}
				$this->SetAccessToken($result);
			}

			return $this->access_token ?? '';
		}

		/**
		 * 更新 accesstoken
		 *
		 * @throws \ParameterError|\app\components\InvalidDataException
		 */
		protected function RefreshAccessToken ()
		{
			if (!Utils::notEmptyStr($this->client_secret) || !Utils::notEmptyStr($this->client_id)) {
				throw new ParameterError("invalid client_secret or client_id");
			}

			$params = [
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refresh_token,
			];
			$this->_HttpCall(self::GET_TOKEN, 'POST',[],$params);
			
			if (isset($this->repJson['code']) && $this->repJson['code']['errcode'] == '80001001000119') {
				throw new InvalidDataException('授权已经过期，请重新授权！');
			}
			$time                            = time();
			$this->repJson['expire']         = $time + $this->repJson["expires_in"];
			$this->repJson['refresh_expire'] = $time + $this->repJson["refresh_token_expires_in"] * 3600 * 24;
			$cacheKey                        = $this->getCacheKey('access_token');
			$this->setCache($cacheKey, $this->repJson, $this->repJson['expires_in']);

			return $this->repJson;
		}

		/**
		 * 设置 accesstoken
		 *
		 * @param array $accessToken
		 *
		 * @throws InvalidParamException
		 */
		public function SetAccessToken (array $accessToken)
		{
			if (!isset($accessToken['access_token'])) {
				throw new InvalidParamException('The work access_token must be set.');
			} elseif (!isset($accessToken['expire'])) {
				throw new InvalidParamException('Work access_token expire time must be set.');
			}
			$this->access_token         = $accessToken['access_token'];
			$this->access_token_expire  = $accessToken['expire'];
			$this->refresh_token        = $accessToken['refresh_token'];
			$this->refresh_token_expire = $accessToken['refresh_expire'];
		}

		/**
		 * Title 获取授权跳转地址获取code
		 * User: ZJ
		 * Date: 2021/10/4 14:15
		 *
		 * @param $state
		 *
		 * @return string
		 */
		protected function GetOauth2Url ($state = '')
		{
			$params = [
				'enter'         => 'wm',
				'view'          => 'pc',
				'response_type' => 'code',
				'scope'         => 'default',
				'client_id'     => $this->client_id,
				'redirect_uri'  => $this->redirect_uri,
				'state'         => $state,
			];

			return self::BASE_URI . self::GET_CODE . '?' . http_build_query($params);
		}

		/**
		 * Title 用code换取accessToken
		 * User: ZJ
		 * Date: 2021/10/4 14:18
		 *
		 * @param $code
		 *
		 * @throws \ParameterError
		 */
		public function GetAccessTokenByCode ($code)
		{
			$params = [
				'code'          => $code,
				'grant_type'    => 'authorization_code',
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_uri'  => $this->redirect_uri
			];
			$this->_HttpCall(self::GET_TOKEN, 'POST', $params);
			$this->repJson['expire'] = time() + $this->repJson["expires_in"];
			$this->setCache($this->getCacheKey('access_token'), $this->repJson, $this->repJson['expires_in']);

			return $this->repJson;
		}

		public function getProductList($params = ['pageSize' => 20, 'startGoodsId' => 0]){
		
			$this->_HttpCall(self::GET_PRODUCT_LIST, 'POST', $params);
			return $this->repJson;
		}


	}