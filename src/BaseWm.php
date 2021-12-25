<?php

	namespace zjweimob\weimob\src;
	require_once "error.wm.php";

	use GuzzleHttp\Client;
	use yii\base\Component;
	use WmParameterError;

	class BaseWm extends Component
	{
		const BASE_URI  = 'https://dopen.weimob.com';
		const GET_CODE  = "/fuwu/b/oauth2/authorize";//授权code获取
		const GET_TOKEN = "/fuwu/b/oauth2/token";//获取请求access_token
		/*业务api*/
		const   GET_PRODUCT_LIST           = '/api/1_0/ec/goods/querySimpleGoodsListWithPage'; //获取商品列表(不限制商品数量)
		const   GET_GUIDE_URL              = '/api/1_0/ec/navigation/pageUrlWithExtendParam'; //获取导购链接
		const   GET_PRODUCT_DETAIL         = '/api/1_0/ec/goods/queryGoodsDetail'; //获取商品详情
		const   GET_ACTIVE_LIST            = '/api/1_0/ec/promotion/queryPromotionList'; //获取活动列表
		const   QUERY_FULL_DISCOUNT_DETAIL = '/api/1_0/ec/promotion/queryFullDiscountDetail'; //获取活动详情 满减
		const   QUERY_DISCOUNT_DETAIL      = '/api/1_0/ec/promotion/queryDiscountDetail'; //获取活动详情 折扣
		const   QUERY_NYNJ_DETAIL          = '/api/1_0/ec/promotion/queryNynjDetail'; //获取活动详情
		const   QUERY_COMBINATION_DETAIL   = '/api/1_0/ec/promotion/queryCombinationDetail';
		const   QUERY_REDEMPTION_DETAIL    = '/api/1_0/ec/promotion/queryRedemptionDetail';
		const   QUERY_GIFTMARKETING_DETAIL = '/api/1_0/ec/promotion/queryGiftMarketingDetail';
		const   QUERY_XJXZ_DETAIL          = '/api/1_0/ec/promotion/queryXjxzDetail';
		const   GET_COUPON_LIST            = '/api/1_0/ec/coupon/getMerchantCouponList'; //优惠券列表
		const   GET_COUPON_DETAIL          = '/api/1_0/ec/coupon/getMerchantCouponDetail'; //优惠券详情
		const   QUERY_ORDER_LIST           = '/api/1_0/ec/order/queryOrderList'; //订单列表
		const   QUERY_ORDER_DETAIL         = '/api/1_0/ec/order/queryOrderDetail'; //订单详情
		const   FIND_GUIDER_LIST           = '/api/1_0/ec/guide/findGuiderList'; //获取导购列表
		const   GET_MEMBER_DETAIL          = '/api/1_0/mc/member/getMemberDetail'; //会员详情
		const   GET_USER_INFO              = '/api/1_0/uc/user/getUserInfo'; //获取用户信息详情
		const   GET_SUPER_WID_BY_SOURCE    = '/api/1_0/uc/user/getSuperWidBySource'; //根据unionid获取微盟主wid
		const   PAGE_TAG_ATT_LIST          = '/api/2_0/ec/mbp/pageTagAttListV2'; //获取标签库信息
		const   SEND_USER_COUPON           = '/api/1_0/ec/coupon/sendUserCoupon'; //发送优惠券
		const   STORE_LIST                 = '/api/1_0/ec/merchant/queryStoreList'; //门店列表
		const   STORE_INFO                 = '/api/1_0/ec/merchant/getStoreInfo'; //门店详情
		const   STORE_GOODS                = '/api/1_0/ec/retailGoods/findStoreByGoodsId'; //商品可用门店信息
		const   STORE_AREA                 = '/api/1_0/ec/merchantdepartment/queryDepartmentListByBizIdList'; //根据门店storeId回溯上级区域列表

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
			try {
				$result = $this->httpClient()->request($method, $url, $options);
			} catch (\Exception $e) {
				preg_match_all("/\{.*?\}/is", $e->getMessage(), $matches);
				if (!empty($matches[0][0])) {
					$result = json_decode($matches[0][0], true);
				}
				if (!empty($result['error_description'])) {
					throw new WmParameterError($result['error_description']);
				} else if (!empty($result['error'])) {
					throw new WmParameterError($result['error']);
				} else {
					throw new WmParameterError($e->getMessage());
				}
			}
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
					$globalTicket = isset($result['globalTicket']) ? $result['globalTicket'] : '';
					$err_code     = isset($result['code']['errcode']) ? $result['code']['errcode'] : '';
					$err_msg      = isset($result['code']['errmsg']) ? $result['code']['errmsg'] : '';
					$msg          = !empty($err_msg) ? 'globalTicket:' . $globalTicket . ';' . 'err_code:' . $err_code . ';' . 'err_msg:' . $err_msg . ';' : json_encode(['error' => $result]);
					throw new WmParameterError($msg);
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