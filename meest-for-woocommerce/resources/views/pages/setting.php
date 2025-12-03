<?php

use MeestShipping\Core\View;
use MeestShipping\Core\Error;

if (!empty($options['tokens'])) {
    $currentTab = $request->tab ?? 'general';
} else {
    $currentTab = 'api';
}

$tabs = [
    ['name' => 'general', 'title' =>  translate('General', MEEST_PLUGIN_DOMAIN)],
    ['name' => 'cost', 'title' => translate('Delivery cost', MEEST_PLUGIN_DOMAIN)],
    ['name' => 'api', 'title' =>  translate('API', MEEST_PLUGIN_DOMAIN)],
];
?>
<style>
    .nav-tab {
        margin-left: 0;
    }
    #meest_delivery_cost_table tr td {
        padding: 6px;
    }
</style>
<div class="container">
    <div class="row">
        <img class="page-container-logo" src="<?php echo MEEST_PLUGIN_URL.'public\img\icon_big.png' ?>" alt="">
        <h1><?php _e('Settings', MEEST_PLUGIN_DOMAIN) ?></h1>
        <?php Error::show(); ?>
        <hr class="wp-header-end">
        <div class="setting-grid">
            <div class="content">
                <nav class="nav-tab-wrapper">
                    <?php foreach ($tabs as $tab): ?>
                        <a href="<?= esc_url(add_query_arg(['page' => 'meest_setting', 'tab' => $tab['name']], admin_url('admin.php'))) ?>" class="nav-tab <?= $tab['name'] === $currentTab ? ' nav-tab-active' : '' ?>"><?= $tab['title'] ?></a>
                    <?php endforeach ?>
                </nav>
                <?php
                switch($currentTab) {
                    case 'general':
                        echo View::part('views/pages/setting_general');
                        break;
                    case 'cost':
                        echo View::part('views/pages/setting_cost');
                        break;
                    case 'api':
                        echo View::part('views/pages/setting_api');
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>
