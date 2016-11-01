<?php

/*
  PHP API lib for Convead
  More information on https://convead.io/api-doc
*/

class ConveadApi {
  public $version = '1.0.0';

  public $debug = false;
  public $host = "tracker.convead.io";
  public $protocol = "https";
  public $timeout = 1;
  public $connect_timeout = 1;

  private $access_token;
  private $api_key;

  /**
   * 
   * @param string $access_token
   * @param string $api_key
   */
  public function __construct($access_token, $api_key) {
    $this->access_token = (string) $access_token;
    $this->api_key = (string) $api_key;
  }

  /**
   * 
   * @param string $order_id
   * @param string $state
   * @param integer $revenue
   * @param array $items
   * @param array $visitor
   */
  public function orderPurchase($order_id, $state = null, $revenue = null, $items = null, $visitor = null) {
    $url = $this->getUrl("orders/{$order_id}");
    $post = array();
   
    if ($revenue !== null) $post['revenue'] = $revenue;
    if ($items !== null) $post['items'] = $items;
    if ($state !== null) $post['state'] = $state;
    if ($visitor !== null) $post['visitor'] = $visitor;
    
    return $this->send($url, 'POST', $post);
  }

  /**
   * 
   * @param string $order_id
   * @param string $state
   * @param integer $revenue
   * @param array $items
   * @param array $visitor
   */
  public function orderUpdate($order_id, $state = null, $revenue = null, $items = null, $visitor = null) {
    $url = $this->getUrl("orders/{$order_id}");
    $post = array();

    if ($revenue !== null) $post['revenue'] = $revenue;
    if ($items !== null) $post['items'] = $items;
    if ($state !== null) $post['state'] = $state;
    if ($visitor !== null) $post['visitor'] = $visitor;

    return $this->send($url, 'PUT', $post);
  }

  /**
   * 
   * @param string $order_id
   */
  public function orderDelete($order_id) {
    $url = $this->getUrl("orders/{$order_id}");
    
    return $this->send($url, 'DELETE');
  }

  /**
   * 
   * @param string $path
   */
  private function getUrl($path) {
    $token = urlencode($this->access_token);
    return "{$this->protocol}://{$this->host}/api/v1/accounts/{$this->api_key}/{$path}?access_token={$token}";
  }

  /**
   * 
   * @param string $url
   * @param string $method
   * @param array $post
   */
  private function send($url, $method = 'GET', $post = array()) {
    $this->putLog($url, $method, $post);

    if (!function_exists('curl_exec')) return array('error' => 'No support for CURL');

    if (isset($_COOKIE['convead_track_disable'])) return array('error' => 'Convead tracking disabled');

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    if ($method != 'POST') curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($post) {
      if ($method == 'POST') curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $this->buildHttpQuery($post));

      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    } else {
      curl_setopt($curl, CURLOPT_POST, false);
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded; charset=utf-8", "Accept:application/json, text/javascript, */*; q=0.01"));

    $response = json_decode(curl_exec($curl));

    if (!is_array($response)) $response = array();

    $error = curl_error($curl);

    if ($error) return array('error' => $error);
    else $response['error'] = false;

    curl_close($curl);

    return $response;
  }

  /**
   * 
   * @param array $query
   */
  private function buildHttpQuery($query) {
    $query_array = array();
    foreach( $query as $key => $key_value ){
        $query_array[] = urlencode($key) . '=' . urlencode($key_value);
    }
    
    return implode('&', $query_array);
  }

  /**
   * 
   * @param string $url
   * @param string $method
   * @param array $post
   */
  private function putLog($url, $method, $post) {
    if (!$this->debug) return;

    ob_start();
    print_r($post);
    $string = ob_get_clean();

    $date = date('Y.m.d H:i:s');
    $row = "{$date}\n{$method} {$url}\n{$string}\n\n";
    $filename = dirname(__FILE__) . "/api_debug.log";
    return file_put_contents($filename, $row, FILE_APPEND);
  }

}
