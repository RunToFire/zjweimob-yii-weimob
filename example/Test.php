<?php

	use zjweimob\weimob\src\Weimob;

	class Test
	{
		public function index(){

			$authConfig = [];
			$serviceWork = \Yii::createObject([
				'class'         => Weimob::className(),
				'client_id'     => $authConfig['client_id'],
				'client_secret' => $authConfig['client_secret'],
				'redirect_uri'  => $authConfig['redirect_uri']
			]);
			//获取token
			$force=1;
			$serviceWork->GetAccessToken($force);

			$serviceWork->getProductList();

			return $serviceWork->repJson;
		}

	}