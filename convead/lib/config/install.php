<?php

/*
 *
 * Convead plugin for Webasyst framework.
 *
 * @name Convead
 * @author Vladimir Savelyev
 * @link http://convead.ru/
 * @copyright Copyright (c) 2015, Convead
 * @version    2.4, 2019-07-11
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{domains:[]}');