<?php

/**
 * Класс для работы с сервисом convead.io
 */
class ConveadTracker {
    public $version = '1.1.3';

    private $browser;
    private $api_key;
    private $guest_uid;
    private $visitor_info = false;
    private $visitor_uid = false;
    private $referrer = false;
    private $api_page = "http://tracker.convead.io/watch/event";
    private $url = false;
    private $domain = false;
    private $charset = 'utf-8';
    private $debug = false;

    /**
     * 
     * @param type $api_key
     * @param type $domain
     * @param type $guest_uid
     * @param type $visitor_uid
     * @param type $visitor_info структура с параметрами текущего визитора (все параметры опциональные) следующего вида:
      [
      first_name: 'Name',
      last_name: 'Surname',
      email: 'email',
      phone: '1-111-11-11-11',
      date_of_birth: '1984-06-16',
      gender: 'male',
      language: 'ru',
      custom_field_1: 'custom value 1',
      custom_field_2: 'custom value 2',
      ...
      ]
     * @param type $referrer
     * @param type $url
     */
    public function __construct($api_key, $domain, $guest_uid, $visitor_uid = false, $visitor_info = false, $referrer = false, $url = false) {
        if (!class_exists('Browser')) {
            require 'Browser.php';
        }

        $this->browser = new Browser();
        $this->api_key = (string) $api_key;
        
        $domain_encoding = mb_detect_encoding($domain, array('UTF-8', 'windows-1251'));
        $this->domain = (string) mb_strtolower( (($domain_encoding == 'UTF-8') ? $domain : iconv($domain_encoding, 'UTF-8', $domain)) , 'UTF-8');
        
        $this->guest_uid = (string) $guest_uid;
        $this->visitor_info = $visitor_info;
        $this->visitor_uid = (string) $visitor_uid;
        $this->referrer = (string) $referrer;
        $this->url = (string) $url;
    }

    private function getDefaultPost() {
        $post = array();
        $post["app_key"] = $this->api_key;
        $post["domain"] = $this->domain;

        if ($this->guest_uid)
            $post["guest_uid"] = $this->guest_uid;
        else
            $post["guest_uid"] = "";

        if ($this->visitor_uid)
            $post["visitor_uid"] = $this->visitor_uid;
        else
            $post["visitor_uid"] = "";

        if ($this->referrer) $post["referrer"] = $this->referrer;
        if (is_array($this->visitor_info) and count($this->visitor_info) > 0) $post["visitor_info"] = $this->visitor_info;
        if ($this->url) {
            $post["url"] = "http://" . $this->url;
            $post["host"] = $this->url;
        }
        return $post;
    }

    /**
     * 
     * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $product_name наименование товара
     * @param type $product_url постоянный URL товара
     */
    public function eventProductView($product_id, $product_name = false, $product_url = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "view_product";
        $post["properties"]["product_id"] = (string) $product_id;
        if ($product_name) $post["properties"]["product_name"] = (string) $product_name;
        if ($product_url) $post["properties"]["product_url"] = (string) $product_url;
        $post = $this->post_encode($post);
        $this->putLog($post);
        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param type $product_id - ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $qnt количество ед. добавляемого товара
     * @param type $price стоимость 1 ед. добавляемого товара
     * @param type $product_name наименование товара
     * @param type $product_url постоянный URL товара
     * @return boolean
     */
    public function eventAddToCart($product_id, $qnt, $price, $product_name = false, $product_url = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "add_to_cart";
        $post["properties"]["product_id"] = (string) $product_id;
        $post["properties"]["qnt"] = $qnt;
        $post["properties"]["price"] = $price;
        if ($product_name) $post["properties"]["product_name"] = (string) $product_name;
        if ($product_url) $post["properties"]["product_url"] = (string) $product_url;

        $post = $this->post_encode($post);
        $this->putLog($post);
        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $qnt количество ед. добавляемого товара
     * @param type $product_name наименование товара
     * @param type $product_url постоянный URL товара
     * @return boolean
     */
    public function eventRemoveFromCart($product_id, $qnt, $product_name = false, $product_url = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "remove_from_cart";
        $post["properties"]["product_id"] = (string) $product_id;
        $post["properties"]["qnt"] = $qnt;
        if ($product_name) $post["properties"]["product_name"] = (string) $product_name;
        if ($product_url) $post["properties"]["product_url"] = (string) $product_url;

        $post = $this->post_encode($post);
        $this->putLog($post);
        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param type $order_id - ID заказа в интернет-магазине
     * @param type $revenue - общая сумма заказа
     * @param type $order_array массив вида:
      [
          [id: <product_id>, qnt: <product_count>, price: <product_price>],
          [...]
      ]
     * @return boolean
     */
    public function eventOrder($order_id, $revenue = false, $order_array = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "purchase";
        $properties = array();
        $properties["order_id"] = (string) $order_id;

        if ($revenue) $properties["revenue"] = $revenue;
        if (is_array($order_array)) $properties["items"] = $order_array;

        $post["properties"] = $properties;
        unset($post["url"]);
        unset($post["host"]);
        unset($post["path"]);
        $post = $this->post_encode($post);
        $this->putLog($post);

        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param array $order_array JSON-структура вида:
      [
          [id: <product_id>, qnt: <product_count>, price: <product_price>],
          [...]
      ]
     * @return boolean
     */
    public function eventUpdateCart($order_array) {
        $post = $this->getDefaultPost();
        $post["type"] = "update_cart";
        $properties = array();

        $properties["items"] = $order_array;

        $post["properties"] = $properties;

        $post = $this->post_encode($post);
        $this->putLog($post);

        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param string $key - имя кастомного ключа
     * @param array $properties - передаваемые свойства
     * @return boolean
     */
    public function eventCustom($key, $properties = array()) {
        $post = $this->getDefaultPost();
        $post["type"] = "custom";
        $properties["key"] = (string) $key;
        $post["properties"] = $properties;

        $post = $this->post_encode($post);
        $this->putLog($post);

        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    /**
     * 
     * @param string $url - url адрес страницы
     * @param string $title - заголовок страницы
     * @return boolean
     */
    public function view($url, $title) {
        $url = (string) $url;
        $post = $this->getDefaultPost();
        $post["type"] = "link";
        $post["title"] = (string) $title;
        $post["url"] = "http://" . $this->url . $url;
        $post["path"] = $url;

        $post = $this->post_encode($post);

        $this->putLog($post);

        if ($this->browser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->browser->error;
    }

    private function putLog($message) {
        if (!$this->debug) return true;
        $message = date("Y.m.d H:i:s") . " - " . $message . "\n";
        $filename = dirname(__FILE__) . "/debug.log";
        file_put_contents($filename, $message, FILE_APPEND);
    }

    private function post_encode($post) {
        $ret_post = array(
            'app_key' => $post['app_key'],
            'visitor_uid' => $post['visitor_uid'],
            'guest_uid' => $post['guest_uid'],
            'data' => $this->json_encode($post)
          );
        return $this->build_http_query($ret_post);
    }
  
    private function build_http_query($query) {
        $query_array = array();
        foreach( $query as $key => $key_value ){
            $query_array[] = urlencode( $key ) . '=' . urlencode( $key_value );
        }
        return implode('&', $query_array);
    }

    private function json_encode($text) {
        if ($this->charset == "windows-1251") {
            return json_encode($this->json_fix($text));
        } else {
            return json_encode($text);
        }
    }

    private function json_fix($data) {
        # Process arrays
        if (is_array($data)) {
            $new = array();
            foreach ($data as $k => $v) {
                $new[$this->json_fix($k)] = $this->json_fix($v);
            }
            $data = $new;
        }
        # Process objects
        else if (is_object($data)) {
            $datas = get_object_vars($data);
            foreach ($datas as $m => $v) {
                $data->$m = $this->json_fix($v);
            }
        }
        # Process strings
        else if (is_string($data)) {
            $data = iconv('cp1251', 'utf-8', $data);
        }
        return $data;
    }

}
