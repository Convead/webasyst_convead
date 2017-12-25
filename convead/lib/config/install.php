<?php

/*
 *
 * Convead plugin for Webasyst framework.
 *
 * @name Convead
 * @author Vladimir Savelyev
 * @link http://convead.ru/
 * @copyright Copyright (c) 2015, Convead
 * @version    2.2, 2017-12-25
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{domains:[]}');