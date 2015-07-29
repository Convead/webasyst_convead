<?php

class shopConveadPluginSettingsAction extends waViewAction {

    public function execute() {
        $plugin_id = 'convead';
        $plugin = waSystem::getInstance()->getPlugin($plugin_id);
        $settings = $plugin->getSettings();
        $plugin_model = new shopPluginModel();
        $this->view->assign('settings', $settings);
    }

}
