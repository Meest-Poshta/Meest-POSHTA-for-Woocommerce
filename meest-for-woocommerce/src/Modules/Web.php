<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;

class Web implements Module
{
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        return $this;
    }

    public function enqueueScripts()
    {
        if (is_checkout()) {
            // Принудительно загружаем SelectWoo
            Asset::loadSelectWoo();
            
            // Временно загружаем только основные скрипты без проблемных
            Asset::load(['meest']);
            // Asset::load(['meest-address', 'meest-checkout']); // Временно отключено
            // Asset::localize('meest-checkout'); // Временно отключено
        }
    }
}
