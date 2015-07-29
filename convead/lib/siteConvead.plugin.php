<?php

class siteConveadPlugin extends shopPlugin
{
	
	public static $product = false;

    public static function widget()
    {
		$plugin = wa('shop')->getPlugin('convead');

		$settings = $plugin->getSettings();
		
		if (empty($settings['options']['api_key'])) return false;

		$user_id = wa()->getUser()->getId();
		
		$js_info_user = '';
		$js_ready = '';
		
		if ($user_id)
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
		
		if (self::$product)
		{
			$product = self::$product;
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

		(function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = '//tracker.convead.io/widgets/'+ts+'/widget-{$settings['options']['api_key']}.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
		</script>
		<!-- /Convead Widget -->";
		
		return $ret;
    }

}
