<?php

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
			return $serviceWork->getProductList();
		}

	}