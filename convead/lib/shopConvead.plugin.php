<?php

class shopConveadPlugin extends shopPlugin
{

	public function order_state($params)
	{
		if (!$params['order_id']) return false;
		if (!($tracker = $this->_include_tracker_anonym())) return false;
		
		$order_id = $params['order_id'];
		if (!($order_data = $this->_getOrderData($order_id))) return false;

		$revenue = $order_data ? $order_data->revenue : null;
		$items = $order_data ? $order_data->items : null;
		$state = $this->_switchState($params['after_state_id']);

		$tracker->webHookOrderUpdate($order_id, $state, $revenue, $items);
	}

	public function order_delete($params)
	{
		if (!$params['order_id']) return false;
		if (!($tracker = $this->_include_tracker_anonym())) return false;

		$tracker->webHookOrderUpdate($params['order_id'], 'cancelled');
	}

	// emulate cart_set_quantity and cart_add event
	public function routing($route = array())
	{
		$uri = waRequest::server('REQUEST_URI');
		if (strpos($uri, 'add') !== false and !empty($_POST['product_id'])) $this->update_cart( array('cart_add' => true, 'product_id' => intval($_POST['product_id'])) );
		if (strpos($uri, 'save') !== false and !empty($_POST['quantity']) and !empty($_POST['id'])) $this->update_cart( array('cart_set_quantity' => true, 'id' => intval($_POST['id']), 'quantity' => intval($_POST['quantity'])) );
	}

	public function update_cart($params = array())
	{
		if (!($tracker = $this->_include_tracker(false)) or !class_exists('shopCart')) return false;
		
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
			if ($find_id)
			{
				$products_cart_res[$find_id]['quantity']++;
			}
		}
		if (!empty($params['cart_set_quantity']))
		{
			if (isset($products_cart_res[$params['id']])) $products_cart_res[$params['id']]['quantity'] = $params['quantity'];
		}
		// / fix old cart value
		
		$sku_model = new shopProductSkusModel();

		$products_cart = array();
		foreach($products_cart_res as $product)
		{
			$product_id = $product['product']['id'];
			if ($product['sku_id'] != $product['product']['sku_id']) $product_id .= 's'.$product['sku_id'];
			
			$sku = $sku_model->getSku($product['sku_id']);

			if ($sku) $products_cart[] = array(
				'product_id' => $product_id, 
				'qnt' => $product['quantity'], 
				'price' => $sku['primary_price'],
				'product_name' => $product['product']['name']
			);
		}

		$tracker->eventUpdateCart($products_cart);
	}
	
	public function purchase($params = array())
	{
		if (!($order_data = $this->_getOrderData($params['order_id']))) return false;

		$customer = new waContact($order_data->order['contact_id']);

		if (isset($_REQUEST['customer_id']))
		{
			// create purchase from admin panel without customer
			$fields = array(
					'first_name' => (!empty($_REQUEST['customer']['firstname']) ? $_REQUEST['customer']['firstname'] : false),
					'last_name' => (!empty($_REQUEST['customer']['lastname']) ? $_REQUEST['customer']['lastname'] : false),
					'phone' => (!empty($_REQUEST['customer']['phone']) ? $_REQUEST['customer']['phone'] : false),
					'email' => (!empty($_REQUEST['customer']['email']) ? $_REQUEST['customer']['email'] : false)
				);
		}
		else
		{
			$fields = array(
					'first_name' => $customer->get('firstname'),
					'last_name' => $customer->get('lastname'),
					'phone' => (($phone = $customer->get('phone') and isset($phone[0])) ? $phone[0]['value'] : false),
					'email' => (($email = $customer->get('email') and isset($email[0])) ? $email[0]['value'] : false)
				);
		}
		$this->visitor_info = array();
		foreach($fields as $key=>$value)
		{
			if ($value) $this->visitor_info[$key] = $value;
		}

		if (!($tracker = $this->_include_tracker())) return false;
		
		return $tracker->eventOrder($order_data->order_id, $order_data->revenue, $order_data->items, $order_data->state);
	}

	public function view_product($params = array())
	{
		self::widget();
	}

  public static function widget()
  {
		if (!($api_key = self::_get_app_key())) return false;

		$js_info_user = '';		
		if ($user_id = wa()->getUser()->getId())
		{
			$fields = array(
					'first_name' => wa()->getUser()->get('firstname', 'default'),
					'last_name' => wa()->getUser()->get('lastname', 'default'),
					'phone' => wa()->getUser()->get('phone', 'default'),
					'email' => wa()->getUser()->get('email', 'default')
				);
			$js_visitor_info = array();
			foreach($fields as $key=>$value)
			{
				if ($value) $js_visitor_info[] = $key.": '".str_replace("'", "\'", $value)."'";
			}

			$js_info_user = "
			visitor_uid: '{$user_id}',
			visitor_info: {".implode(",\n", $js_visitor_info)."},
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
			app_key: '{$api_key}'

			/* For more information on widget configuration please see:
			   http://help.convead.ru/knowledge_base/item/25215
			*/
		};

		(function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = 'https://tracker.convead.io/widgets/'+ts+'/widget-{$api_key}.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');

{{$js_ready}}
		</script>
		<!-- /Convead Widget -->";
		
		return $ret;
  }

  private function _getOrderData($order_id) {
  	$order_model = new shopOrderModel();
  	$order = $order_model->getById($order_id);

  	if (!$order) return false;

 		$order_items_model = new shopOrderItemsModel();
		$items_res = $order_items_model->getByField('order_id', $order_id, true);
		$items = array();
		$sku_model = new shopProductSkusModel();
		$ret = new stdClass();
		foreach($items_res as $product)
		{
	    	$skus = $sku_model->getDataByProductId($product['product_id']);
	    	$product['product'] = reset($skus);
			$product_id = $product['product_id'];
			if ($product['sku_id'] != $product['product']['id']) $product_id .= 's'.$product['sku_id'];

			$items[] = array(
				'product_id' => $product_id,
				'qnt' => $product['quantity'],
				'price' => $product['price'],
				'product_name' => $product['name']
			);
		}
		$ret->order_id = $order_id;
		$ret->items = $items;
		$ret->revenue = $order['total'];
		$ret->state = $this->_switchState($order['state_id']);
		$ret->order = $order;
		return $ret;
  }

  private function _switchState($state) {
    switch ($state) {
      case 'processing':
        $state = 'new';
        break;
      case 'paid':
        $state = 'paid';
        break;
      case 'shipped':
        $state = 'shipped';
        break;
    }
    return $state;
  }
	
	private function _include_api()
	{
		if (!($api_token = self::_get_api_token())) return false;
		if (!($app_key = $this->_get_app_key())) return false;

		$api = new ConveadApi($api_token, $app_key);
		
		return $api;
	}

	private function _include_tracker_anonym()
	{
    if (!($api_key = self::_get_app_key())) return false;

    include_once('vendors/ConveadTracker.php');

    $tracker = new ConveadTracker($api_key);

    return $tracker;
	}

	private function _include_tracker($allow_uid_generate = true)
	{
		if (!($api_key = self::_get_app_key())) return false;

		include_once('vendors/ConveadTracker.php');
		
		$auth = new waAuth();

		if (isset($_REQUEST['customer_id']))
		{
			if (!$allow_uid_generate && $_REQUEST['customer_id'] == 0) return false;
			// create event from admin panel without customer
			$guest_uid = ($_REQUEST['customer_id'] == 0 ? uniqid() : false);
			$uid = ($_REQUEST['customer_id'] == 0 ? false : $_REQUEST['customer_id']);
		}
		else
		{
			$guest_uid = waRequest::cookie('convead_guest_uid');
			$uid = (($auth_info = $auth->isAuth()) ? $auth_info['id'] : false);
		}

		if (!$guest_uid && !$uid) return false;

		$routing = wa()->getRouting();
		$domain = $routing->getDomain();

		$tracker = new ConveadTracker($api_key, $domain, $guest_uid, $uid, (isset($this->visitor_info) ? $this->visitor_info : false));
		
		return $tracker;
	}

	public static function _get_app_key()
	{
		$plugin = wa('shop')->getPlugin('convead');
		$settings = $plugin->getSettings();
		
		wa('site');
		foreach (siteHelper::getDomains(true) as $domain_id=>$domain) if (waRequest::server('SERVER_NAME') == $domain['name']) break;
		if (isset($settings['options']) and isset($settings['options']['domains']) and !empty($settings['options']['domains'][$domain_id]) and !empty($settings['options']['domains'][$domain_id]['api_key'])) return $settings['options']['domains'][$domain_id]['api_key'];
		else if (!empty($settings['options']['api_key'])) return $settings['options']['api_key'];
		else return false;
	}

}
