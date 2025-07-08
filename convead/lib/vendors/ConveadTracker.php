<?php

/**
 * Асинхронная версия класса для работы с сервисом convead.io
 */
class ConveadTracker {
  public $version = "1.2.5";
  public $debug = false;
  public $charset = "utf-8";
  public $timeout = 1;
  public $connect_timeout = 1;
  public $error = false;
  public $generated_uid = false;
  public $async_mode = true; // Новый флаг для асинхронного режима

  private $api_key;
  private $guest_uid = false;
  private $visitor_uid = false;
  private $visitor_info = false;
  private $referrer = false;
  private $host = "tracker.convead.io";
  private $protocol = "https";
  private $url = false;
  private $domain = false;
  private $queue_file = null; // Файл для очереди при недоступности сервиса

  /**
   * 
   * @param type $api_key
   * @param type $domain
   * @param type $guest_uid
   * @param type $visitor_uid
   * @param type $visitor_info структура с параметрами текущего визитора (все параметры опциональные) следующего вида:
    {
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
    }
   * @param type $referrer
   * @param type $url
   */
  public function __construct($api_key, $domain = null, $guest_uid = null, $visitor_uid = null, $visitor_info = null, $referrer = null, $url = null) {
    $this->api_key = (string) $api_key;

    $domain = ($domain == null) ? $_SERVER["HTTP_HOST"] : $domain;

    $domain_encoding = mb_detect_encoding($domain, array("UTF-8", "windows-1251"));
    $this->domain = (string) mb_strtolower( (($domain_encoding == "UTF-8") ? $domain : iconv($domain_encoding, "UTF-8", $domain)) , "UTF-8");

    $this->guest_uid = (string) $guest_uid;
    $this->visitor_info = $visitor_info;
    $this->visitor_uid = (string) $visitor_uid;
    $this->referrer = (string) $referrer;
    $this->url = (string) $url;

    // Инициализация файла очереди
    $this->queue_file = sys_get_temp_dir() . '/convead_queue_' . md5($this->api_key) . '.json';

    if (!$this->guest_uid and !$this->visitor_uid) {
      $this->guest_uid = uniqid();
      $this->generated_uid = true;
    }
  }

  // Включить/выключить асинхронный режим
  public function setAsyncMode($async = true) {
    $this->async_mode = $async;
  }

  /**
   * 
   * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
   * @param type $product_name наименование товара
   * @param type $product_url постоянный URL товара
   */
  public function eventProductView($product_id, $product_name = null, $product_url = null) {
    $post = $this->getDefaultPost();
    $post["type"] = "view_product";
    $post["properties"]["product_id"] = (string) $product_id;
    if ($product_name !== null) $post["properties"]["product_name"] = (string) $product_name;
    if ($product_url !== null) $post["properties"]["product_url"] = (string) $product_url;

    return $this->sendEvent($post);
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
  public function eventAddToCart($product_id, $qnt, $price, $product_name = null, $product_url = null) {
    $post = $this->getDefaultPost();
    $post["type"] = "add_to_cart";
    $post["properties"]["product_id"] = (string) $product_id;
    $post["properties"]["qnt"] = $qnt;
    $post["properties"]["price"] = $price;
    if ($product_name !== null) $post["properties"]["product_name"] = (string) $product_name;
    if ($product_url !== null) $post["properties"]["product_url"] = (string) $product_url;

    return $this->sendEvent($post);
  }

  /**
   * 
   * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
   * @param type $qnt количество ед. добавляемого товара
   * @param type $product_name наименование товара
   * @param type $product_url постоянный URL товара
   * @return boolean
   */
  public function eventRemoveFromCart($product_id, $qnt, $product_name = null, $product_url = null) {
    $post = $this->getDefaultPost();
    $post["type"] = "remove_from_cart";
    $post["properties"]["product_id"] = (string) $product_id;
    $post["properties"]["qnt"] = $qnt;
    if ($product_name) $post["properties"]["product_name"] = (string) $product_name;
    if ($product_url) $post["properties"]["product_url"] = (string) $product_url;

    return $this->sendEvent($post);
  }

  /**
   * 
   * @param type $order_id - ID заказа в интернет-магазине
   * @param type $state - статус заказа
   * @param type $revenue - общая сумма заказа
   * @param type $order_array массив вида:
    [
        {product_id: <product_id>, qnt: <product_count>, price: <product_price>},
        {...}
    ]
   * @return boolean
   */
  public function eventOrderUpdate($order_id, $state, $revenue = null, $order_array = null) {
    $post = $this->getDefaultPost();
    $post["type"] = "order_update";
    $properties = array();
    $properties["order_id"] = (string) $order_id;
    $properties["state"] = (string) $state;

    if ($revenue !== null) $properties["revenue"] = $revenue;

    if (is_array($order_array)) $properties["items"] = $order_array;

    $post["properties"] = $properties;
    unset($post["url"]);
    unset($post["host"]);
    unset($post["path"]);

    return $this->sendEvent($post);
  }

  /**
   * 
   * @param type $order_id - ID заказа в интернет-магазине
   * @param type $revenue - общая сумма заказа
   * @param type $order_array массив вида:
    [
        {product_id: <product_id>, qnt: <product_count>, price: <product_price>},
        {...}
    ]
   * @param type $state - статус заказа
   * @return boolean
   */
  public function eventOrder($order_id, $revenue, $order_array, $state = null) {
    $post = $this->getDefaultPost();
    $post["type"] = "purchase";
    $properties = array();
    $properties["order_id"] = (string) $order_id;

    if ($revenue !== null) $properties["revenue"] = $revenue;

    if (is_array($order_array)) $properties["items"] = $order_array;

    if ($state !== null) $properties["state"] = $state;

    $post["properties"] = $properties;
    unset($post["url"]);
    unset($post["host"]);
    unset($post["path"]);

    return $this->sendEvent($post);
  }

  /**
   * 
   * @param array $order_array JSON-структура вида:
    [
        {product_id: <product_id>, qnt: <product_count>, price: <product_price>},
        {...}
    ]
   * @return boolean
   */
  public function eventUpdateCart($order_array) {
    $post = $this->getDefaultPost();
    $post["type"] = "update_cart";
    $properties = array();
    $properties["items"] = $order_array;
    $post["properties"] = $properties;
    return $this->sendEvent($post);
  }

  // Асинхронная отправка данных
  private function sendAsync($url, $post = null, $custom_headers = array(), $method = "POST") {
    if (isset($_COOKIE["convead_track_disable"])) {
      return "Convead tracking disabled";
    }

    // Проверяем доступность сервиса быстрой проверкой
    if (!$this->isServiceAvailable()) {
      $this->addToQueue($url, $post, $custom_headers, $method);
      return true; // Возвращаем успех, чтобы не блокировать основной процесс
    }

    // Формируем команду для фонового выполнения
    $temp_file = tempnam(sys_get_temp_dir(), 'convead_');
    $request_data = json_encode(array(
      'url' => $url,
      'post' => $post,
      'headers' => $custom_headers,
      'method' => $method,
      'timeout' => $this->timeout,
      'connect_timeout' => $this->connect_timeout
    ));

    file_put_contents($temp_file, $request_data);

    // Запускаем фоновый процесс
    $script_path = dirname(__FILE__) . '/convead_async_sender.php';
    $command = "php {$script_path} {$temp_file}";

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      // Windows
      pclose(popen("start /B " . $command, "r"));
    } else {
      // Unix/Linux
      exec($command . " > /dev/null 2>&1 &");
    }

    return true;
  }

  // Быстрая проверка доступности сервиса
  private function isServiceAvailable() {
    $host = $this->host;
    $port = ($this->protocol === 'https') ? 443 : 80;

    $connection = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connection) {
      fclose($connection);
      return true;
    }
    return false;
  }

  // Добавление в очередь при недоступности сервиса
  private function addToQueue($url, $post, $headers, $method) {
    $queue_item = array(
      'url' => $url,
      'post' => $post,
      'headers' => $headers,
      'method' => $method,
      'timestamp' => time()
    );

    $queue = array();
    if (file_exists($this->queue_file)) {
      $queue = json_decode(file_get_contents($this->queue_file), true) ?: array();
    }

    $queue[] = $queue_item;

    // Ограничиваем размер очереди
    if (count($queue) > 100) {
      $queue = array_slice($queue, -100);
    }

    file_put_contents($this->queue_file, json_encode($queue));
  }

  // Обработка очереди
  public function processQueue() {
    if (!file_exists($this->queue_file)) {
      return true;
    }

    $queue = json_decode(file_get_contents($this->queue_file), true) ?: array();
    if (empty($queue)) {
      return true;
    }

    $processed = array();
    foreach ($queue as $item) {
      // Пропускаем старые элементы (старше 24 часов)
      if (time() - $item['timestamp'] > 86400) {
        continue;
      }

      if ($this->sendSync($item['url'], $item['post'], $item['headers'], $item['method'])) {
        // Успешно отправлено
        continue;
      } else {
        // Не удалось отправить, оставляем в очереди
        $processed[] = $item;
      }
    }

    // Обновляем файл очереди
    if (empty($processed)) {
      unlink($this->queue_file);
    } else {
      file_put_contents($this->queue_file, json_encode($processed));
    }

    return true;
  }

  // Синхронная отправка (для обработки очереди)
  private function sendSync($url, $post = null, $custom_headers = array(), $method = "POST") {
    $headers = array("Accept:application/json, text/javascript, */*; q=0.01");
    $headers = array_unique(array_merge($headers, $custom_headers));

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if ($post) {
      if ($method == "POST") curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    return !$error;
  }

  // Основной метод отправки событий
  private function sendEvent($post) {
    $headers = array("Content-Type: application/x-www-form-urlencoded; charset=utf-8");
    $post = $this->post_encode($post);

    if ($this->async_mode) {
      return $this->sendAsync($this->getUrl(), $post, $headers);
    } else {
      return $this->sendSync($this->getUrl(), $post, $headers);
    }
  }

  // Основной метод отправки webhook
  private function sendWebHook($post, $topic) {
    $headers = array(
      "Content-Type: application/json; charset=utf-8",
      "X-Webhook-Topic: {$topic}",
      "X-App-Key: {$this->api_key}"
    );

    if ($this->async_mode) {
      return $this->sendAsync($this->getWebHookUrl(), $this->json_encode($post), $headers, "POST");
    } else {
      return $this->sendSync($this->getWebHookUrl(), $this->json_encode($post), $headers, "POST");
    }
  }

  // Остальные методы остаются без изменений
  private function getDefaultPost() {
    $post = array();
    $post["app_key"] = $this->api_key;
    if ($this->guest_uid) $post["guest_uid"] = $this->guest_uid;
    if ($this->visitor_uid) $post["visitor_uid"] = $this->visitor_uid;
    $post["domain"] = $this->domain;
    if ($this->referrer) $post["referrer"] = $this->referrer;
    if ($this->url) {
      $post["url"] = "http://" . $this->url;
      $post["host"] = $this->url;
    }
    if (is_array($this->visitor_info) and count($this->visitor_info) > 0) {
      $post["visitor_info"] = $this->visitor_info;
    }
    return $post;
  }

  private function getUrl() {
    return "{$this->protocol}://{$this->host}/watch/event";
  }

  private function getWebHookUrl() {
    return "{$this->protocol}://{$this->host}/integration/common/webhook";
  }

  private function post_encode($post) {
    $ret = array("app_key" => $post["app_key"]);
    if (!empty($post["visitor_uid"])) $ret["visitor_uid"] = $post["visitor_uid"];
    if (!empty($post["guest_uid"])) $ret["guest_uid"] = $post["guest_uid"];
    $ret["data"] = $this->json_encode($post);
    return $this->build_http_query($ret);
  }

  private function json_encode($text) {
    if ($this->charset == "windows-1251") {
      return json_encode($this->json_fix($text));
    } else {
      return json_encode($text);
    }
  }

  private function json_fix($data) {
    if (is_array($data)) {
      $new = array();
      foreach ($data as $k => $v) {
        $new[$this->json_fix($k)] = $this->json_fix($v);
      }
      $data = $new;
    } else if (is_object($data)) {
      $datas = get_object_vars($data);
      foreach ($datas as $m => $v) {
        $data->$m = $this->json_fix($v);
      }
    } else if (is_string($data)) {
      $data = iconv("cp1251", "utf-8", $data);
    }
    return $data;
  }

  private function build_http_query($query) {
    return http_build_query($query);
  }
}
