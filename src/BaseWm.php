<?php

	namespace zjweimob\weimob\src;
	require_once "error.wm.php";

	use GuzzleHttp\Client;
	use yii\base\Component;
	use WmParameterError;

	class BaseWm extends Component
	{
		const BASE_URI           = 'https://dopen.weimob.com';
		const GET_CODE           = "/fuwu/b/oauth2/authorize";//授权code获取
		const GET_TOKEN          = "/fuwu/b/oauth2/token";//获取请求access_token
		/*业务api*/
		const GET_PRODUCT_LIST   = '/api/1_0/ec/goods/querySimpleGoodsListWithPage'; //获取商品列表(不限制商品数量)
		const GET_PRODUCT_DETAIL = '/api/1_0/ec/goods/queryGoodsDetail'; //获取商品详情
		const GET_ACTIVE_LIST    = '/api/1_0/ec/promotion/queryPromotionList'; //获取活动列表
		const GET_COUPON_LIST    = '/api/1_0/ec/coupon/getMerchantCouponList'; //优惠列表
		const GET_COUPON_DETAIL  = '/api/1_0/ec/coupon/getMerchantCouponDetail'; //优惠详情

		protected     $guzzleOptions = [];
		public        $repJson;
		public static $client;

		/**
		 * 第三方应用的token
		 *
		 * @var string
		 */
		public $access_token;

		/**
		 * 凭证的有效时间（秒）
		 *
		 * @var string
		 */
		public $access_token_expire;

		/**
		 * 凭证的有效时间（秒）
		 *
		 * @var string
		 */
		public $refresh_token;

		/**
		 * 凭证的有效时间
		 *
		 * @var string
		 */
		public $refresh_token_expire;

		public function httpClient ()
		{
			if (!self::$client) {
				self::$client = new self();
			}

			return self::$client;
		}

		protected function getHttpClient ()
		{
			if (!isset($this->guzzleOptions['base_uri'])) {
				$this->guzzleOptions['base_uri'] = self::BASE_URI;
			}

			return new Client($this->guzzleOptions);
		}

		/**
		 * 统一转换响应结果为 json 格式.
		 *
		 * @param $response
		 *
		 * @return mixed
		 */
		protected function unwrapResponse ($response)
		{
			$contentType = $response->getHeaderLine('Content-Type');
			$contents    = $response->getBody()->getContents();
			if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
				return json_decode($contents, true);
			} elseif (false !== stripos($contentType, 'xml')) {
				return json_decode(json_encode(simplexml_load_string($contents)), true);
			}

			return $contents;
		}

		public function request ($method, $endpoint, $options = [])
		{
			return $this->unwrapResponse($this->getHttpClient()->{$method}($endpoint, $options));
		}

		/**
		 * Title 发送请求
		 * User: ZJ
		 * Date: 2021/10/12 11:39
		 *
		 * @param       $url
		 * @param       $method
		 * @param       $params
		 * @param array $query
		 *
		 * @return mixed
		 * @throws \WmParameterError
		 */
		public function _HttpCall ($url, $method, $params, $query = [])
		{

			if (!empty($this->access_token)) {
				$query = array_merge([
					'accesstoken' => $this->access_token,
				], $query);
			}
			$options = [
				'headers' => [],
				'query'   => $query
			];
			if (!empty($params)) {
				$body            = json_encode($params);
				$options['body'] = $body;
			}
			$result = $this->httpClient()->request($method, $url, $options);
			if (isset($result['code']['errcode']) && $result['code']['errcode'] != 0) {
				if ($result['code']['errcode'] == '80001001000119') {//TOKEN失效
					//刷新token
					try {
						$newConfig = $this->GetAccessToken(true);
					} catch (\Exception $e) {
						throw new WmParameterError($e->getMessage());
					}
					if (!empty($newConfig)) {
						return $this->_HttpCall($url, $method, $params);
					} else {
						throw new WmParameterError('请求成功，但未请求到token');
					}
				} else {
					throw new WmParameterError($result['code']['errcode'] . ':' . $result['code']['errmsg']);
				}
			} else {
				$this->repJson = $result;
			}

		}

		public function getCache ($key)
		{
			if (\Yii::$app->cache->exists($key)) {
				return \Yii::$app->cache->get($key);
			}

			return false;
		}

		public function setCache ($key, $value, $expiresIn)
		{
			\Yii::$app->cache->set($key, $value, $expiresIn);
		}

	}