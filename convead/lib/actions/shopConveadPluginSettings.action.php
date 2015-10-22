<?php

class shopConveadPluginSettingsAction extends waViewAction {

    public function execute() {
        $plugin_id = 'convead';
        $plugin = waSystem::getInstance()->getPlugin($plugin_id);
        $settings = $plugin->getSettings();

        $model = new siteDomainModel();
        $domains = $model->query('SELECT id, name FROM '.$model->getTableName())->fetchAll();

        $this->view->assign('domains', $domains);
        $this->view->assign('exist_curl', function_exists('curl_exec'));
        $this->view->assign('settings', $settings);
    }

}
