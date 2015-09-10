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
		$items = $order_items_model->getByField('order_id', $params['order_id'], true);
		$order_array = array();
		$total_price = 0;
		foreach($items as $product)
		{
			$order_array[] = array('product_id' => $product['product_id'], 'qnt' => $product['quantity'], 'price' => $product['price']);
			$total_price = $total_price + ($product['price']*$product['quantity']);
		}

		$convead->eventOrder($params['order_id'], $total_price, $order_array);
	}
	
	public function view_product($params)
	{
		new siteConveadPlugin();
	}
	
	private function _include_api()
	{
		$settings = $this->getSettings();
		if (empty($settings['options']['api_key'])) return false;

		include_once('vendors/ConveadTracker.php');
		
		$auth = new waAuth();
		
		if ($auth_info = $auth->isAuth()) $user_id = $auth_info['id'];

		$convead = new ConveadTracker($settings['options']['api_key'], waRequest::server('SERVER_NAME'), waRequest::cookie('convead_guest_uid'), (isset($user_id) ? $user_id : false), (isset($visitor_info) ? $visitor_info : false));
		
		return $convead;
	}

}
