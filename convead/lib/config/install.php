<?php

/*
 *
 * Convead plugin for Webasyst framework.
 *
 * @name Convead
 * @author Vladimir Savelyev
 * @link http://convead.ru/
 * @copyright Copyright (c) 2015, Convead
 * @version    1.7, 2016-03-03
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{domains:[],api_token:""}');