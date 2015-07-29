<?php

/**
 * Класс для работы с post запросами
 */
class Browser {
    public $version = '1.1.1';

    protected $config = array();
    public $error = false;

    public function __initialize() {
        $this->resetConfig();
    }

    public function setopt($const, $val) {
        $this->settings[$const] = $val;
    }

    public function resetConfig() {
        $this->referer = false;
        $this->useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0";
        $this->cookie = false;
        $this->userpwd = false;

        $this->timeout = 5;

        $this->proxy = false;
        $this->proxyuserpwd = false;

        $this->followlocation = false;
        $this->maxsize = 0;
        $this->maxredirs = 5;

        $this->encode = false;

        $this->settings = array();
    }

    public function postToString($post) {
        $result = "";
        $i = 0;
        foreach ($post as $varname => $varval) {
            $result .= ($i > 0 ? "&" : "") . urlencode($varname) . "=" . urlencode($varval);
            $i++;
        }

        return $result;
    }

    public function postEncode($post) {
        $result = array();
        foreach ($post as $varname => $varval) {
            $result[urlencode($varname)] = urlencode($varval);
        }

        return $result;
    }

    public function isUAAbandoned($user_agent){
        if(!$user_agent)
            return true;
        $re = "/bot|crawl(er|ing)|google|yandex|rambler|yahoo|bingpreview|alexa|facebookexternalhit|bitrix/i"; 
        
        $matches = array(); 
        preg_match($re, $user_agent, $matches);

        if(count($matches) > 0)
            return true;
        else
            return false;
    }

    public function get($url, $post = false) {
        if($this->isUAAbandoned($_SERVER['HTTP_USER_AGENT']))
            return true;

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded; charset=utf-8", "Accept:application/json, text/javascript, */*; q=0.01"));

        curl_exec($curl);

        $this->error = curl_error($curl);

        if ($this->error) {

            return $this->error;
        }

        curl_close($curl);

        return true;
    }

}
