<?php

return array(
  'name'          => 'Convead',
  'img'           => 'img/logo.png',
  'icons'         => array(
    16 => 'img/logo.png'
  ),
  'shop_settings' => true,
  'version'       => '2.2',
  'vendor'        => 1027096,
  'frontend'      => true,
  'handlers'      => array(
    
    // Эмуляция эвентов cart_set_quantity и cart_add
    'routing'                 => 'routing',
    'cart_add'                => 'update_cart',
    //'cart_set_quantity'     => 'update_cart',

    'cart_delete'             => 'update_cart',
    'frontend_product'        => 'view_product',
    'order_action.create'     => 'purchase',
    'order_action.delete'     => 'order_delete', // Удален 
    'order_action.complete'   => 'order_state',  // Выполнен "completed"
    'order_action.callback'   => 'order_state',  // Callback от платежной системы
    'order_action.pay'        => 'order_state',  // Оплачен "paid"
    'order_action.process'    => 'order_state',  // В обработке "processing"
    'order_action.restore'    => 'order_state',  // Восстановлен
    'order_action.ship'       => 'order_state',  // Отправлен "shipped"
    'order_action.refund'     => 'order_state'   // Возврат "refunded"
  
  ),
);
