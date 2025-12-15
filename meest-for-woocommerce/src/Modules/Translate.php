<?php

namespace MeestShipping\Modules;

class Translate
{
    public function init()
    {
        add_action('plugins_loaded', [$this, 'pluginsLoaded'], 1);
    }

    public function pluginsLoaded()
    {
        load_plugin_textdomain(MEEST_PLUGIN_DOMAIN, false, MEEST_PLUGIN_SLUG.DS.'languages');
        load_plugin_textdomain(MEEST_PLUGIN_DOMAIN, false, MEEST_PLUGIN_SLUG.DS.'resources'.DS.'langs');

        $localeCandidates = [];
        if (function_exists('determine_locale')) {
            $localeCandidates[] = determine_locale();
        }
        $localeCandidates[] = get_locale();
        $localeCandidates = array_values(array_unique(array_filter($localeCandidates)));

        foreach ($localeCandidates as $locale) {
            $lang = substr((string) $locale, 0, 2);
            $candidateFiles = [
                MEEST_PLUGIN_PATH . 'resources' . DS . 'langs' . DS . 'meest_for_woocommerce-' . $locale . '.mo',
                MEEST_PLUGIN_PATH . 'resources' . DS . 'langs' . DS . 'meest_for_woocommerce-' . $lang . '.mo',
                MEEST_PLUGIN_PATH . 'resources' . DS . 'langs' . DS . MEEST_PLUGIN_DOMAIN . '-' . $locale . '.mo',
                MEEST_PLUGIN_PATH . 'resources' . DS . 'langs' . DS . MEEST_PLUGIN_DOMAIN . '-' . $lang . '.mo',
                MEEST_PLUGIN_PATH . 'languages' . DS . MEEST_PLUGIN_DOMAIN . '-' . $locale . '.mo',
                MEEST_PLUGIN_PATH . 'languages' . DS . MEEST_PLUGIN_DOMAIN . '-' . $lang . '.mo',
            ];

            foreach ($candidateFiles as $mofile) {
                if (is_string($mofile) && file_exists($mofile)) {
                    if (function_exists('unload_textdomain')) {
                        unload_textdomain(MEEST_PLUGIN_DOMAIN);
                    }
                    load_textdomain(MEEST_PLUGIN_DOMAIN, $mofile);
                    return;
                }
            }
        }
    }
}
