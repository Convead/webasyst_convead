<?php

/*
 *
 * Storequickorder plugin for Webasyst framework.
 *
 * @name Storequickorder
 * @author EasyIT LLC
 * @link http://easy-it.ru/
 * @copyright Copyright (c) 2014, EasyIT LLC
 * @version    1.3, 2014-10-15
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{"api_key":""}');