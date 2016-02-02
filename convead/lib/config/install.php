<?php

/*
 *
 * Convead plugin for Webasyst framework.
 *
 * @name Convead
 * @author Vladimir Savelyev
 * @link http://convead.ru/
 * @copyright Copyright (c) 2015, EasyIT LLC
 * @version    1.5, 2016-02-02
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{"api_key":"","domains":""}');