<?php

class shopConveadPluginSettingsAction extends waViewAction {

  public function execute() {
    $plugin_id = 'convead';
    $plugin = waSystem::getInstance()->getPlugin($plugin_id);
    $settings = $plugin->getSettings();

    wa('site');
    $domains = siteHelper::getDomains(true);

    $this->view->assign('domains', $domains);
    $this->view->assign('exist_curl', function_exists('curl_exec'));
    $this->view->assign('settings', $settings);
  }

}
