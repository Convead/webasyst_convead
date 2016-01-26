<?php

class shopConveadPlugin extends shopPlugin
{

	# emulate cart_set_quantity and cart_add event
	public function routing($route = array())
	{
		$uri = waRequest::server('REQUEST_URI');
		if (strpos($uri, 'add') !== false and !empty($_POST['product_id'])) $this->update_cart( array('cart_add' => true, 'product_id' => intval($_POST['product_id'])) );
		if (strpos($uri, 'save') !== false and !empty($_POST['quantity']) and !empty($_POST['id'])) $this->update_cart( array('cart_set_quantity' => true, 'id' => intval($_POST['id']), 'quantity' => intval($_POST['quantity'])) );
	}

	public function update_cart($params = array())
	{
		if (!($convead = $this->_include_api()) or !class_exists('shopCart')) return false;
		
		$cart = new shopCart();
		$products_cart_res = $cart->items();

		// fix old cart value
		if (!empty($params['cart_add']))
		{
			$find_id = false;
			foreach($products_cart_res as $id=>$product)
			{
				if ((empty($_REQUEST['sku_id']) and $product['sku_id'] == $params['product_id']) or (!empty($_REQUEST['sku_id']) and $product['sku_id'] == $_REQUEST['sku_id']))
				{
					$find_id = $id;
					break;
				}
			}
			if (!$find_id) return false;
			else
			{
				$products_cart_res[$find_id]['quantity']++;
			}
		}
		if (!empty($params['cart_set_quantity']))
		{
			if (isset($products_cart_res[$params['id']])) $products_cart_res[$params['id']]['quantity'] = $params['quantity'];
		}
		// / fix old cart value

		$products_cart = array();
		foreach($products_cart_res as $product)
		{
			$product_id = $product['product']['id'];
			if ($product['sku_id'] != $product['product']['sku_id']) $product_id .= 's'.$product['sku_id'];

			$products_cart[] = array('product_id' => $product_id, 'qnt' => $product['quantity'], 'price' => $product['price']);
		}

		$convead->eventUpdateCart($products_cart);
	}
	
	public function purchase($params = array())
	{
		$order_model = new shopOrderModel();
		$order = $order_model->getById($params['order_id']);
		$customer = new waContact($order['contact_id']);
		$this->visitor_info = array(
				'first_name' => $customer->get('firstname'),
				'last_name' => $customer->get('lastname'),
				'phone' => (($phone = $customer->get('phone') and isset($phone[0])) ? $phone[0]['value'] : false),
				'email' => (($email = $customer->get('email') and isset($email[0])) ? $email[0]['value'] : false)
			);

		if (!($convead = $this->_include_api())) return false;

		$order_items_model = new shopOrderItemsModel();
		$items = $order_items_model->getByField('order_id', $params['order_id'], true);
		$order_array = array();
		$total_price = 0;
    $sku_model = new shopProductSkusModel();
		foreach($items as $product)
		{
	    $skus = $sku_model->getDataByProductId($product['product_id']);
	    $product['product'] = reset($skus);

			$product_id = $product['product_id'];
			if ($product['sku_id'] != $product['product']['id']) $product_id .= 's'.$product['sku_id'];

			$order_array[] = array('product_id' => $product_id, 'qnt' => $product['quantity'], 'price' => $product['price']);
			$total_price = $total_price + ($product['price']*$product['quantity']);
		}
		$convead->eventOrder($params['order_id'], $total_price, $order_array);
	}

	public function view_product($params = array())
	{
		self::widget();
	}

  public static function widget()
  {
		if (!($api_key = self::_get_api_key())) return false;

		$js_info_user = '';		
		if ($user_id = wa()->getUser()->getId())
		{
			$js_info_user = "
			visitor_uid: '{$user_id}',
			visitor_info: {
				first_name: '".wa()->getUser()->get('firstname','default')."',
				last_name: '".wa()->getUser()->get('lastname','default')."',
				phone: '".wa()->getUser()->get('phone','default')."',
				email: '".wa()->getUser()->get('email','default')."'
			},
			";
		}
		
		$product_model = new shopProductModel();
		$product = $product_model->getByField('url', waRequest::param('product_url'));
		$js_ready = '';
		if ($product)
		{
			$js_ready = "
				convead('event', 'view_product', {
					product_id: '{$product["id"]}',
					product_name: '{$product["name"]}',
					product_url: window.location.href
				  });
			";
		}
		
		$ret = "<!-- Convead Widget -->
		<script>
		window.ConveadSettings = {
			$js_info_user
			app_key: '{$api_key}',
			onready: function() {{$js_ready}}

			/* For more information on widget configuration please see:
			   http://help.convead.ru/knowledge_base/item/25215
			*/
		};

		(function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = 'https://tracker.convead.io/widgets/'+ts+'/widget-{$api_key}.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
		</script>
		<!-- /Convead Widget -->";
		
		return $ret;
  }
	
	private function _include_api()
	{
		if (!($api_key = self::_get_api_key())) return false;

		include_once('vendors/ConveadTracker.php');
		
		$auth = new waAuth();

		$convead = new ConveadTracker($api_key, waRequest::server('SERVER_NAME'), waRequest::cookie('convead_guest_uid'), (($auth_info = $auth->isAuth()) ? $auth_info['id'] : false), (isset($this->visitor_info) ? $this->visitor_info : false));
		
		return $convead;
	}

	public static function _get_api_key()
	{
		$plugin = wa('shop')->getPlugin('convead');
		$settings = $plugin->getSettings();
		
		wa('site');
		foreach (siteHelper::getDomains(true) as $domain_id=>$domain) if (waRequest::server('SERVER_NAME') == $domain['name']) break;

		if (!empty($settings['options']['domains'][$domain_id]['api_key'])) return $settings['options']['domains'][$domain_id]['api_key'];
		else if (!empty($settings['options']['api_key'])) return $settings['options']['api_key'];
		else return false;
	}

}
