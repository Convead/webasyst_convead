<?php

return array(
    'name' => 'Convead',
    'img'=>'img/logo.png',
    'icons'=>array(
        16 => 'img/logo.png'
    ),
	'shop_settings' => true,
	'version'=>'0.1',
	'vendor'=>1027096,
	'frontend'     => true,
	'handlers' => array(
		'convead_update_cart' => 'update_cart',
		'convead_purchase' => 'purchase',
		'convead_view_product' => 'view_product'
	),
);
