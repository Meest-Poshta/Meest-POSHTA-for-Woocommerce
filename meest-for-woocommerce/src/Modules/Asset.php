<?php

namespace MeestShipping\Modules;

defined( 'ABSPATH' ) || exit;

class Asset
{
    private static $_instance;
    private $options;
    public $enqueues;
    public $locale;

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
        $this->locale = substr(get_locale(), 0, 2);

        $this->enqueues = [
            'jquery' => [
                'js' => [
                    'src' => ABSPATH . 'wp-includes/js/jquery/jquery.min.js',
                ]
            ],
            'flatpickr' => [
                'css' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/flatpickr.min.css',
                ],
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/flatpickr.min.js',
                    'locale' => MEEST_PLUGIN_URL.'public/plugins/flatpickr/l10n/%s.min.js'
                ],
            ],
            'meest' => [
                'css' => [
                    'src' => MEEST_PLUGIN_URL.'public/css/style.min.css',
                    'deps' => [],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-address' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/address.min.js',
                    'deps' => ['jquery'],
                    'ver' => MEEST_PLUGIN_VERSION
                ]
            ],
            'meest-setting' =>  [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/setting.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-parcel' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/parcel.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-pickup' => [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/pickup.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
            'meest-checkout' =>  [
                'js' => [
                    'src' => MEEST_PLUGIN_URL.'public/js/checkout.min.js',
                    'deps' => ['meest-address'],
                    'ver' => MEEST_PLUGIN_VERSION
                ],
            ],
        ];
    }

    public static function instance(): Asset
    {
        return static::$_instance ?? static::$_instance = new static();
    }

    public static function load($keys): void
    {
        $self = self::instance();

        foreach ($keys as $key) {
            if (isset($self->enqueues[$key])) {
                $item = $self->enqueues[$key];

                if ($key === 'meest-address') {
                    self::loadSelectWoo();
                }

                if (!empty($item['css'])) {
                    wp_register_style($key, $item['css']['src'], $item['css']['deps'] ?? [], $item['css']['ver'] ?? false);
                    wp_enqueue_style($key);
                }
                if (!empty($item['js'])) {
                    wp_register_script($key, $item['js']['src'], $item['js']['deps'] ?? [], $item['js']['ver'] ?? false);
                    wp_enqueue_script($key);

                    if (!empty($item['js']['locale'])) {
                        wp_register_script("$key-locale", sprintf($item['js']['locale'], $self->locale), [$key]);
                        wp_enqueue_script("$key-locale");
                    }
                }
            } else {
                wp_enqueue_script($key);
            }
        }
    }

    public static function localize(string $handle)
    {
        $self = self::instance();

        wp_localize_script($handle, 'meest', [
            'id' => MEEST_PLUGIN_NAME,
            'ajaxUrl' => admin_url('admin-ajax.php', 'relative'),
            'actions' => [
                'get_country' => 'meest_address_country',
                'get_city' => 'meest_address_city',
                'get_street' => 'meest_address_street',
                'get_branch' => 'meest_address_branch',
                'get_poshtomat' => 'meest_address_poshtomat',
                'update_dictionary' => 'meest_update_dictionary',
            ],
            'delivery_types' => [
                'branch' => 'Branch delivery',
                'poshtomat' => 'Poshtomat delivery',
                'address' => 'Address delivery',
            ],
            'country_id' => $self->options['country_id']
        ]);
    }

    public static function loadSelectWoo()
    {
        // Загружаем стили Select2
        if (wp_style_is('select2', 'registered')) {
            wp_enqueue_style('select2');
        } else {
            $version = defined('WC_VERSION') ? WC_VERSION : null;
            $wcPluginFile = defined('WC_PLUGIN_FILE') ? WC_PLUGIN_FILE : WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
            $cssUrl = plugins_url('assets/css/select2.css', $wcPluginFile);
            wp_enqueue_style('select2', $cssUrl, [], $version);
        }

        // Загружаем скрипт SelectWoo
        if (wp_script_is('selectWoo', 'registered')) {
            wp_enqueue_script('selectWoo');
        } else {
            // Загружаем SelectWoo вручную, если он не зарегистрирован
            $version = defined('WC_VERSION') ? WC_VERSION : null;
            $wcPluginFile = defined('WC_PLUGIN_FILE') ? WC_PLUGIN_FILE : WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
            $jsUrl = plugins_url('assets/js/selectWoo/selectWoo.full.min.js', $wcPluginFile);
            
            // Проверяем, существует ли файл
            $wcPluginDir = defined('WC_PLUGIN_DIR') ? WC_PLUGIN_DIR : WP_PLUGIN_DIR . '/woocommerce/';
            if (file_exists($wcPluginDir . 'assets/js/selectWoo/selectWoo.full.min.js')) {
                wp_enqueue_script('selectWoo', $jsUrl, ['jquery'], $version);
            } else {
                // Если WooCommerce не предоставляет SelectWoo, показываем предупреждение админу
                if (current_user_can('manage_options')) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-warning is-dismissible"><p>';
                        echo esc_html__('Meest for WooCommerce: WooCommerce SelectWoo library not found. Please update WooCommerce to the latest version.', 'meest-for-woocommerce');
                        echo '</p></div>';
                    });
                }
            }
        }
        
        // Дополнительно загружаем jQuery UI, если нужно
        if (!wp_script_is('jquery-ui-core', 'enqueued')) {
            wp_enqueue_script('jquery-ui-core');
        }
    }

    private static function get_asset_url( $path ) {
        $wcPluginFile = defined('WC_PLUGIN_FILE') ? WC_PLUGIN_FILE : WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
        return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, $wcPluginFile ), $path );
    }
}
