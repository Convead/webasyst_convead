<?php

return array(
    'name' => 'Convead',
    'img'=>'img/logo.png',
    'icons'=>array(
        16 => 'img/logo.png'
    ),
    'shop_settings' => true,
    'version' => '1.4',
    'vendor' => 1027096,
    'frontend' => true,
    'handlers' => array(
        
        // emulate cart_set_quantity and cart_add event
        'routing' => 'routing',
        'cart_add' => 'update_cart',
        //'cart_set_quantity' => 'update_cart',

        'cart_delete' => 'update_cart',
        'order_action.create' => 'purchase',
        'frontend_product' => 'view_product'
    ),
);
