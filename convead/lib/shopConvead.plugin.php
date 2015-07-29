<?php

class shopConveadPlugin extends shopPlugin
{

	public function update_cart($params)
	{
		if (!($convead = $this->_include_api())) return false;
		
		$cart = new shopCart();
		$products_cart_res = $cart->items();
		$products_cart = array();
		foreach($products_cart_res as $product)
		{
			$products_cart[] = array('product_id' => $product['product']['id'], 'qnt' => $product['quantity'], 'price' => $product['price']);
		}

		$convead->eventUpdateCart($products_cart);
	}
	
	public function purchase($params)
	{
		if (!($convead = $this->_include_api())) return false;
	
		$order_items_model = new shopOrderItemsModel();
		$items = $order_items_model->getByField('order_id', $params['id'], true);
		$order_array = array();
		foreach($items as $product)
		{
			$order_array[] = array('product_id' => $product['product_id'], 'qnt' => $product['quantity'], 'price' => $product['price']);
		}

		$convead->eventOrder($params['id'], $params['total'], $order_array);
	}
	
	public function view_product($params)
	{
		$product_model = new shopProductModel();
		$siteConvead = new siteConveadPlugin();
		$siteConvead::$product = $product_model->getByField('url', waRequest::param('product_url'));
	}
	
	private function _include_api()
	{
		$settings = $this->getSettings();
		if (empty($settings['options']['api_key'])) return false;

		include_once('api/ConveadTracker.php');
		
		$auth = new waAuth();
		
		if ($auth_info = $auth->isAuth()) $user_id = $auth_info['id'];
		else if (empty($_SERVER['SERVER_NAME'])) return false;

		$convead = new ConveadTracker($settings['options']['api_key'], $_SERVER['SERVER_NAME'], $_COOKIE['convead_guest_uid'], (isset($user_id) ? $user_id : false), (isset($visitor_info) ? $visitor_info : false));
		
		return $convead;
	}

}
