<?php

/*
 *
 * Convead plugin for Webasyst framework.
 *
 * @name Convead
 * @author Vladimir Savelyev
 * @link http://convead.ru/
 * @copyright Copyright (c) 2025, Convead
 * @version    2.6.0, 2025-08-09
 *
 */

$app_settings_model = new waAppSettingsModel();
$app_settings_model->set(array('shop', 'convead'), 'options', '{domains:[]}');