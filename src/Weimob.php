<?php

	namespace zjweimob\weimob\src;
	require_once "error.wm.php";

	use app\components\InvalidDataException;
	use WmParameterError;
	use zjweimob\weimob\utils\Utils;

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
		 * @throws \WmParameterError
		 */
		public function GetAccessToken ($force = false)
		{

			$result = $this->getCache($this->getCacheKey('access_token'));
			if (empty($result) || $force) {

				$result = $this->RefreshAccessToken();

			}
			$this->SetAccessToken($result);

			return isset($this->access_token) ? $this->access_token : '';
		}

		/**
		 * 更新 accesstoken
		 *
		 * @throws \WmParameterError
		 */
		protected function RefreshAccessToken ()
		{
			if (!Utils::notEmptyStr($this->client_secret) || !Utils::notEmptyStr($this->client_id)) {
				throw new WmParameterError("invalid client_secret or client_id");
			}

			$params = [
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refresh_token,
			];
			$this->_HttpCall(self::GET_TOKEN, 'POST', [], $params);

			if (isset($this->repJson['code']) && $this->repJson['code']['errcode'] == '80001001000119') {
				throw new WmParameterError('授权已经过期，请重新授权！');
			}
			$time                    = time();
			$this->repJson['expire'] = $time + $this->repJson["expires_in"];
			$cacheKey                = $this->getCacheKey('access_token');
			$this->setCache($cacheKey, $this->repJson, $this->repJson['expires_in']);

			return $this->repJson;
		}

		/**
		 * 设置 accesstoken
		 *
		 * @param array $accessToken
		 *
		 * @throws \WmParameterError
		 */
		public function SetAccessToken (array $accessToken)
		{
			if (!isset($accessToken['access_token'])) {
				throw new WmParameterError('The work access_token must be set.');
			} elseif (!isset($accessToken['expire'])) {
				throw new WmParameterError('Work access_token expire time must be set.');
			}
			$this->access_token        = $accessToken['access_token'];
			$this->access_token_expire = $accessToken['expire'];
			$this->refresh_token       = $accessToken['refresh_token'];

		}

		/**
		 * Title 获取授权地址
		 * User: ZJ
		 * Date: 2021/10/12 20:40
		 *
		 * @param $state
		 *
		 * @return string
		 */
		public function getAuthUrl ($state)
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

			return parent::BASE_URI . '/fuwu/b/oauth2/authorize?' . http_build_query($params);
		}

		/**
		 * Title 用code换取accessToken
		 * User: ZJ
		 * Date: 2021/10/4 14:18
		 *
		 * @param $code
		 *
		 * @throws \WmParameterError
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
			$this->_HttpCall(self::GET_TOKEN, 'POST', [], $params);
			$this->repJson['expire'] = time() + $this->repJson["expires_in"];

			$this->setCache($this->getCacheKey('access_token'), $this->repJson, $this->repJson['expires_in']);

			return $this->repJson;
		}

		/**
		 * Title 获取业务接口地址
		 * User: ZJ
		 * Date: 2021/10/12 20:38
		 *
		 * @param $name
		 *
		 * @return string
		 */
		private function getUrl ($name)
		{
			$url = '';
			switch ($name) {
				case 'GET_PRODUCT_LIST':
					$url = self::GET_PRODUCT_LIST;
					break;
				case 'GET_GUIDE_URL':
					$url = self::GET_GUIDE_URL;
					break;
				case 'GET_PRODUCT_DETAIL':
					$url = self::GET_PRODUCT_DETAIL;
					break;
				case 'GET_ACTIVE_LIST':
					$url = self::GET_ACTIVE_LIST;
					break;
				case 'QUERY_FULL_DISCOUNT_DETAIL':
					$url = self::QUERY_FULL_DISCOUNT_DETAIL;
					break;
				case 'QUERY_DISCOUNT_DETAIL':
					$url = self::QUERY_DISCOUNT_DETAIL;
					break;
				case 'QUERY_NYNJ_DETAIL':
					$url = self::QUERY_NYNJ_DETAIL;
					break;
				case 'QUERY_COMBINATION_DETAIL':
					$url = self::QUERY_COMBINATION_DETAIL;
					break;
				case 'QUERY_REDEMPTION_DETAIL':
					$url = self::QUERY_REDEMPTION_DETAIL;
					break;
				case 'QUERY_GIFTMARKETING_DETAIL':
					$url = self::QUERY_GIFTMARKETING_DETAIL;
					break;
				case 'QUERY_XJXZ_DETAIL':
					$url = self::QUERY_XJXZ_DETAIL;
					break;
				case 'GET_COUPON_LIST':
					$url = self::GET_COUPON_LIST;
					break;
				case 'GET_COUPON_DETAIL':
					$url = self::GET_COUPON_DETAIL;
					break;
				case 'QUERY_ORDER_LIST':
					$url = self::QUERY_ORDER_LIST;
					break;
				case 'QUERY_ORDER_DETAIL':
					$url = self::QUERY_ORDER_DETAIL;
					break;
				case 'FIND_GUIDER_LIST':
					$url = self::FIND_GUIDER_LIST;
					break;
				case 'GET_MEMBER_DETAIL':
					$url = self::GET_MEMBER_DETAIL;
					break;
				case 'GET_USER_INFO':
					$url = self::GET_USER_INFO;
					break;
				case 'GET_SUPER_WID_BY_SOURCE':
					$url = self::GET_SUPER_WID_BY_SOURCE;
					break;
				case 'PAGE_TAG_ATT_LIST':
					$url = self::PAGE_TAG_ATT_LIST;
					break;
				case 'SEND_USER_COUPON':
					$url = self::SEND_USER_COUPON;
					break;
				case 'STORE_LIST':
					$url = self::STORE_LIST;
					break;
				case 'STORE_INFO':
					$url = self::STORE_INFO;
					break;
				default:
					break;
			}

			return $url;
		}

		/**
		 * Title 业务接口
		 * User: ZJ
		 * Date: 2021/10/12 15:13
		 *
		 * @param string $name
		 * @param array  $params
		 *
		 * @return mixed
		 * @throws \app\components\InvalidDataException
		 */
		public function getWeiMobData (string $name, array $params)
		{

			try {
				$this->_HttpCall($this->getUrl($name), 'POST', $params);
			} catch (WmParameterError $e) {
				throw new InvalidDataException($e->getMessage());
			}

			return $this->repJson;
		}

	}