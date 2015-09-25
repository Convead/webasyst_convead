<?php

class shopConveadPlugin extends shopPlugin
{

	# emulate cart_set_quantity and cart_add event
	public function routing($params)
	{
		$uri = waRequest::server('REQUEST_URI');
		if (
			(strpos($uri, 'add') !== false and !empty($_POST['product_id']))
			or 
			(strpos($uri, 'save') !== false and !empty($_POST['quantity']) and !empty($_POST['id']))
		) $this->update_cart();
	}

	public function update_cart($params)
	{
		if (!($convead = $this->_include_api()) or !class_exists(shopCart)) return false;
		
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
		foreach($items as $product)
		{
			$order_array[] = array('product_id' => $product['product_id'], 'qnt' => $product['quantity'], 'price' => $product['price']);
			$total_price = $total_price + ($product['price']*$product['quantity']);
		}
		$convead->eventOrder($params['order_id'], $total_price, $order_array);
	}

	public function view_product($params)
	{
		self::widget();
	}

  public static function widget()
  {
		$plugin = wa('shop')->getPlugin('convead');

		$settings = $plugin->getSettings();
		
		if (empty($settings['options']['api_key'])) return false;

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
			app_key: '{$settings['options']['api_key']}',
			onready: function() {{$js_ready}}

			/* For more information on widget configuration please see:
			   http://help.convead.ru/knowledge_base/item/25215
			*/
		};

		(function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = 'https://tracker.convead.io/widgets/'+ts+'/widget-{$settings['options']['api_key']}.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
		</script>
		<!-- /Convead Widget -->";
		
		return $ret;
  }
	
	private function _include_api()
	{
		$settings = $this->getSettings();
		if (empty($settings['options']['api_key'])) return false;

		include_once('vendors/ConveadTracker.php');
		
		$auth = new waAuth();

		$convead = new ConveadTracker($settings['options']['api_key'], waRequest::server('SERVER_NAME'), waRequest::cookie('convead_guest_uid'), (($auth_info = $auth->isAuth()) ? $auth_info['id'] : false), (isset($this->visitor_info) ? $this->visitor_info : false));
		
		return $convead;
	}

}
