<?php

namespace MeestShipping\Modules;

use MeestShipping\Contracts\Module;
use MeestShipping\Repositories\RegionRepository;
use MeestShipping\Repositories\CityRepository;
use MeestShipping\Repositories\BranchRepository;
use MeestShipping\Repositories\StreetRepository;

defined('ABSPATH') || exit;

/**
 * Новая реализация Checkout для стандартного WooCommerce checkout
 * Основана на лучших практиках из wc-ukr-shipping
 */
class Checkout implements Module
{
    private $options;
    private $shippingMethodId = 'meest';

    public function __construct()
    {
        $this->options = meest_init('Option')->all();
    }

    public function init()
    {
        // Инжекция полей в checkout форму
        add_action($this->getInjectActionName(), [$this, 'injectBillingFields']);
        add_action('woocommerce_after_checkout_shipping_form', [$this, 'injectShippingFields']);
        
        // Валидация полей
        add_action('woocommerce_checkout_process', [$this, 'validateFields']);
        add_filter('woocommerce_checkout_fields', [$this, 'removeDefaultFieldsFromValidation'], 99);
        
        // Сохранение данных в заказ
        add_action('woocommerce_checkout_create_order', [$this, 'saveOrderData'], 10, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'updateOrderAddress'], 20, 1);
        
        // Загрузка скриптов и стилей
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // AJAX обработчики
        add_action('wp_ajax_meest_get_regions', [$this, 'ajaxGetRegions']);
        add_action('wp_ajax_nopriv_meest_get_regions', [$this, 'ajaxGetRegions']);
        add_action('wp_ajax_meest_get_cities', [$this, 'ajaxGetCities']);
        add_action('wp_ajax_nopriv_meest_get_cities', [$this, 'ajaxGetCities']);
        add_action('wp_ajax_meest_get_branches', [$this, 'ajaxGetBranches']);
        add_action('wp_ajax_nopriv_meest_get_branches', [$this, 'ajaxGetBranches']);
        add_action('wp_ajax_meest_get_streets', [$this, 'ajaxGetStreets']);
        add_action('wp_ajax_nopriv_meest_get_streets', [$this, 'ajaxGetStreets']);
        
        // Сохранение данных заказа
        add_action('woocommerce_checkout_create_order', [$this, 'saveOrderData'], 10, 2);

        return $this;
    }

    /**
     * Инжектируем billing поля
     */
    public function injectBillingFields()
    {
        $this->renderFields('billing');
    }

    /**
     * Инжектируем shipping поля
     */
    public function injectShippingFields()
    {
        $this->renderFields('shipping');
    }

    /**
     * Рендерим контейнер для полей (будут заполнены через JavaScript)
     */
    private function renderFields($type)
    {
        if (!is_checkout()) {
            return;
        }
        ?>
        <div id="meest-<?php echo esc_attr($type); ?>-fields" class="meest-shipping-fields"></div>
        <?php
    }

    /**
     * Удаляем стандартные WooCommerce поля из валидации когда выбран Meest
     */
    public function removeDefaultFieldsFromValidation($fields)
    {
        if (!wp_doing_ajax() || empty($_POST)) {
            return $fields;
        }

        if ($this->isMeestShippingSelected()) {
            foreach (['billing', 'shipping'] as $type) {
                unset($fields[$type][$type . '_address_1']);
                unset($fields[$type][$type . '_address_2']);
                unset($fields[$type][$type . '_city']);
                unset($fields[$type][$type . '_state']);
                unset($fields[$type][$type . '_postcode']);
            }
        }

        return $fields;
    }

    /**
     * Валидируем поля Meest
     */
    public function validateFields()
    {
        if (!$this->isMeestShippingSelected()) {
            return;
        }

        $type = $this->getFieldType();
        $deliveryType = sanitize_text_field($_POST["meest_{$type}_delivery_type"] ?? '');

        // Проверка обязательных полей
        if (empty($_POST["meest_{$type}_city_id"]) && empty($_POST["meest_{$type}_city_text"])) {
            wc_add_notice(__('Будь ласка, виберіть місто', 'meest-for-woocommerce'), 'error');
        }

        if ($deliveryType === 'branch') {
            // Для доставки в отделение
            if (empty($_POST["meest_{$type}_branch_id"])) {
                wc_add_notice(__('Будь ласка, виберіть відділення', 'meest-for-woocommerce'), 'error');
            }
        } elseif ($deliveryType === 'poshtomat') {
            // Для доставки в поштомат
            if (empty($_POST["meest_{$type}_branch_id"])) {
                wc_add_notice(__('Будь ласка, виберіть поштомат', 'meest-for-woocommerce'), 'error');
            }
        } elseif ($deliveryType === 'address') {
            // Для доставки на адрес
            if (empty($_POST["meest_{$type}_street_text"])) {
                wc_add_notice(__('Будь ласка, введіть вулицю', 'meest-for-woocommerce'), 'error');
            }
            if (empty($_POST["meest_{$type}_building"])) {
                wc_add_notice(__('Будь ласка, введіть номер будинку', 'meest-for-woocommerce'), 'error');
            }
        }
    }

    /**
     * Сохраняем данные Meest в заказ
     */
    public function saveOrderData($order, $data)
    {
        if (!$this->isMeestShippingSelected()) {
            return;
        }

        $type = $this->getFieldType();
        
        // ВАЖНО: используем $_POST вместо $data, так как WooCommerce не передает кастомные поля в $data
        $deliveryType = sanitize_text_field($_POST["meest_{$type}_delivery_type"] ?? 'branch');
        $countryId = sanitize_text_field($_POST["meest_{$type}_country_id"] ?? '');
        $countryCode = sanitize_text_field($_POST["meest_{$type}_country"] ?? '');
        $countryText = sanitize_text_field($_POST["meest_{$type}_country_text"] ?? '');
        $cityId = sanitize_text_field($_POST["meest_{$type}_city_id"] ?? '');
        $cityText = sanitize_text_field($_POST["meest_{$type}_city_text"] ?? '');
        $regionId = sanitize_text_field($_POST["meest_{$type}_region_id"] ?? '');
        $regionText = sanitize_text_field($_POST["meest_{$type}_region_text"] ?? '');

        // Сохраняем базовые данные
        $order->update_meta_data('_meest_delivery_type', $deliveryType);
        $order->update_meta_data('_meest_country_id', $countryId);
        $order->update_meta_data('_meest_city_id', $cityId);
        $order->update_meta_data('_meest_city_text', $cityText);

        // Формируем данные адреса для сохранения в receiver
        $receiverData = [
            'delivery_type' => $deliveryType,
            'country' => [
                'id' => $countryId,
                'code' => $countryCode,
                'text' => $countryText
            ],
            'city' => [
                'id' => $cityId,
                'text' => $cityText
            ],
            'region' => [
                'id' => $regionId,
                'text' => $regionText
            ]
        ];

        // Сохраняем данные в зависимости от типа доставки
        if ($deliveryType === 'branch') {
            // Для отделения
            $branchId = sanitize_text_field($_POST["meest_{$type}_branch_id"] ?? '');
            $branchText = sanitize_text_field($_POST["meest_{$type}_branch_text"] ?? '');
            
            $order->update_meta_data('_meest_branch_id', $branchId);
            $order->update_meta_data('_meest_branch_text', $branchText);
            
            $receiverData['branch'] = [
                'id' => $branchId,
                'text' => $branchText
            ];
            
            // Устанавливаем адрес доставки как название отделения
            $this->setShippingAddress($order, $branchText);
        } elseif ($deliveryType === 'poshtomat') {
            // Для поштомата
            $poshtomatId = sanitize_text_field($_POST["meest_{$type}_branch_id"] ?? '');
            $poshtomatText = sanitize_text_field($_POST["meest_{$type}_branch_text"] ?? '');
            
            $order->update_meta_data('_meest_poshtomat_id', $poshtomatId);
            $order->update_meta_data('_meest_poshtomat_text', $poshtomatText);
            
            $receiverData['poshtomat'] = [
                'id' => $poshtomatId,
                'text' => $poshtomatText
            ];
            
            // Устанавливаем адрес доставки как название поштомата
            $this->setShippingAddress($order, $poshtomatText);
        } elseif ($deliveryType === 'address') {
            // Для курьерской доставки
            $streetId = sanitize_text_field($_POST["meest_{$type}_street_id"] ?? '');
            $streetText = sanitize_text_field($_POST["meest_{$type}_street_text"] ?? '');
            $building = sanitize_text_field($_POST["meest_{$type}_building"] ?? '');
            $flat = sanitize_text_field($_POST["meest_{$type}_flat"] ?? '');
            
            $order->update_meta_data('_meest_street_id', $streetId);
            $order->update_meta_data('_meest_street_text', $streetText);
            $order->update_meta_data('_meest_building', $building);
            $order->update_meta_data('_meest_flat', $flat);
            
            $receiverData['street'] = [
                'id' => $streetId,
                'text' => $streetText
            ];
            $receiverData['building'] = $building;
            $receiverData['flat'] = $flat;
            
            // Формируем полный адрес
            $fullAddress = $streetText . ', ' . $building;
            if (!empty($flat)) {
                $fullAddress .= ' кв. ' . $flat;
            }
            
            $this->setShippingAddress($order, $fullAddress);
        }

        // Устанавливаем страну
        $countryCode = sanitize_text_field($_POST["meest_{$type}_country"] ?? '');
        $this->setShippingCountry($order, $countryCode);

        // Устанавливаем город
        $cityName = sanitize_text_field($_POST["meest_{$type}_city_text"] ?? '');
        $this->setShippingCity($order, $cityName);

        // Сохраняем данные адреса в мета-поле receiver элемента доставки
        $shippingMethods = $order->get_shipping_methods();
        if (!empty($shippingMethods)) {
            $shippingItem = array_shift($shippingMethods);
            $shippingItem->update_meta_data('receiver', $receiverData);
            $shippingItem->save_meta_data();
        }

        $order->save();
    }

    /**
     * Оновлюємо адресу після того як WooCommerce встановив свої значення
     */
    public function updateOrderAddress($orderId)
    {
        if (!$this->isMeestShippingSelected()) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        $type = $this->getFieldType();
        $deliveryType = sanitize_text_field($_POST["meest_{$type}_delivery_type"] ?? 'branch');

        $address = '';
        
        // Отримуємо адресу залежно від типу доставки
        if ($deliveryType === 'branch') {
            $address = sanitize_text_field($_POST["meest_{$type}_branch_text"] ?? '');
        } elseif ($deliveryType === 'poshtomat') {
            $address = sanitize_text_field($_POST["meest_{$type}_branch_text"] ?? '');
        } elseif ($deliveryType === 'address') {
            $streetText = sanitize_text_field($_POST["meest_{$type}_street_text"] ?? '');
            $building = sanitize_text_field($_POST["meest_{$type}_building"] ?? '');
            $flat = sanitize_text_field($_POST["meest_{$type}_flat"] ?? '');
            
            $address = $streetText . ', ' . $building;
            if (!empty($flat)) {
                $address .= ' кв. ' . $flat;
            }
        }

        if (!empty($address)) {
            $order->set_billing_address_1($address);
            $order->set_shipping_address_1($address);
        }

        // Встановлюємо місто
        $cityName = sanitize_text_field($_POST["meest_{$type}_city_text"] ?? '');
        if (!empty($cityName)) {
            $order->set_billing_city($cityName);
            $order->set_shipping_city($cityName);
        }

        $order->save();
    }

    /**
     * Устанавливаем адрес доставки
     */
    private function setShippingAddress($order, $address)
    {
        $address = sanitize_text_field($address);
        
        // Встановлюємо адресу в обидва поля для надійності
        $order->set_billing_address_1($address);
        $order->set_shipping_address_1($address);
    }

    /**
     * Устанавливаем страну доставки
     */
    private function setShippingCountry($order, $country)
    {
        $country = sanitize_text_field($country);
        $shipToDestination = get_option('woocommerce_ship_to_destination');
        
        // Перевіряємо обидва варіанти: 'billing' та 'billing_only'
        if (in_array($shipToDestination, ['billing', 'billing_only'])) {
            $order->set_billing_country($country);
        } else {
            $order->set_shipping_country($country);
        }
    }

    /**
     * Устанавливаем город доставки
     */
    private function setShippingCity($order, $city)
    {
        $city = sanitize_text_field($city);
        
        // Встановлюємо місто в обидва поля для надійності
        $order->set_billing_city($city);
        $order->set_shipping_city($city);
    }

    /**
     * Загружаем скрипты и стили
     */
    public function enqueueAssets()
    {
        if (!is_checkout()) {
            return;
        }

        // Стили
        wp_enqueue_style(
            'meest-checkout',
            MEEST_PLUGIN_URL . 'public/css/checkout.min.css',
            [],
            MEEST_PLUGIN_VERSION
        );

        // Скрипт
        wp_enqueue_script(
            'meest-checkout',
            MEEST_PLUGIN_URL . 'public/js/checkout-new.min.js',
            ['jquery'],
            MEEST_PLUGIN_VERSION,
            true
        );

        // Передаем данные в JavaScript
        wp_localize_script('meest-checkout', 'meestCheckoutData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('meest-checkout'),
            'shippingMethodId' => $this->shippingMethodId,
            'countryId' => $this->options['country_id']['ua'], // Всегда Украина
            'enabledDeliveryTypes' => [
                'branch' => !empty($this->options['shipping']['delivery_type_branch']),
                'poshtomat' => !empty($this->options['shipping']['delivery_type_poshtomat']),
                'address' => !empty($this->options['shipping']['delivery_type_address']),
            ],
            'i18n' => [
                'formTitle' => __('Meest Пошта', 'meest-for-woocommerce'),
                'selectCity' => __('Виберіть місто', 'meest-for-woocommerce'),
                'selectBranch' => __('Виберіть відділення', 'meest-for-woocommerce'),
                'selectPoshtomat' => __('Виберіть поштомат', 'meest-for-woocommerce'),
                'enterStreet' => __('Введіть вулицю', 'meest-for-woocommerce'),
                'enterBuilding' => __('Введіть будинок', 'meest-for-woocommerce'),
                'enterFlat' => __('Введіть квартиру (необов\'язково)', 'meest-for-woocommerce'),
                'deliveryToBranch' => __('Доставка у відділення', 'meest-for-woocommerce'),
                'deliveryToPoshtomat' => __('Доставка у поштомат', 'meest-for-woocommerce'),
                'deliveryToAddress' => __('Доставка на адресу', 'meest-for-woocommerce'),
                'loading' => __('Завантаження...', 'meest-for-woocommerce'),
            ]
        ]);
    }


    /**
     * Определяем тип полей (billing или shipping)
     */
    private function getFieldType()
    {
        return (!empty($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1)
            ? 'shipping'
            : 'billing';
    }

    /**
     * Проверяем выбран ли метод доставки Meest
     */
    private function isMeestShippingSelected()
    {
        if (empty($_POST['shipping_method'])) {
            return false;
        }

        $shippingMethods = (array) $_POST['shipping_method'];
        foreach ($shippingMethods as $method) {
            if (strpos($method, $this->shippingMethodId) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Определяем действие для инжектирования billing полей
     */
    private function getInjectActionName()
    {
        // Можно сделать настройку в админке, пока используем после billing формы
        return 'woocommerce_after_checkout_billing_form';
    }

    /**
     * AJAX: Получить регионы
     */
    public function ajaxGetRegions()
    {
        check_ajax_referer('meest-checkout', 'nonce');

        $countryId = sanitize_text_field($_POST['country_id'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');

        if (empty($countryId)) {
            wp_send_json_error(['message' => 'Country ID is required']);
        }

        try {
            $regions = RegionRepository::instance()->search($countryId, $search);
            
            // Форматируем для select
            $formatted = array_map(function($region) {
                return [
                    'id' => $region['id'],
                    'name' => $region['text']
                ];
            }, $regions);
            
            wp_send_json_success(['regions' => $formatted]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Получить города
     */
    public function ajaxGetCities()
    {
        check_ajax_referer('meest-checkout', 'nonce');

        $countryId = sanitize_text_field($_POST['country_id'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');

        if (empty($countryId)) {
            wp_send_json_error(['message' => 'Country ID is required']);
        }

        try {
            $cities = CityRepository::instance()->search($countryId, $search);
            
            // Форматируем для select
            $formatted = array_map(function($city) {
                return [
                    'id' => $city['id'] ?? $city['uuid'],
                    'name' => $city['text'] ?? $city['descr']
                ];
            }, $cities);
            
            wp_send_json_success(['cities' => $formatted]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Получить отделения
     */
    public function ajaxGetBranches()
    {
        check_ajax_referer('meest-checkout', 'nonce');

        $cityId = sanitize_text_field($_POST['city_id'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $deliveryType = sanitize_text_field($_POST['delivery_type'] ?? 'branch');

        if (empty($cityId)) {
            wp_send_json_error(['message' => 'City ID is required']);
        }

        try {
            // Используем разные методы в зависимости от типа доставки
            if ($deliveryType === 'poshtomat') {
                $branches = BranchRepository::instance()->searchPoshtomat($cityId, $search);
            } else {
                $branches = BranchRepository::instance()->searchBranchOnly($cityId, $search);
            }
            
            // Форматируем для select и сбрасываем ключи массива
            $formatted = array_values(array_map(function($branch) {
                return [
                    'id' => $branch['id'],
                    'name' => $branch['text']
                ];
            }, $branches));
            
            wp_send_json_success(['branches' => $formatted]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Получить улицы
     */
    public function ajaxGetStreets()
    {
        check_ajax_referer('meest-checkout', 'nonce');

        $cityId = sanitize_text_field($_POST['city_id'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');

        if (empty($cityId)) {
            wp_send_json_error(['message' => 'City ID is required']);
        }

        try {
            $streets = StreetRepository::instance()->search($cityId, $search);
            
            // Форматируем для select
            $formatted = array_map(function($street) {
                return [
                    'id' => $street['id'] ?? $street['uuid'],
                    'name' => $street['text'] ?? $street['descr']
                ];
            }, $streets);
            
            wp_send_json_success(['streets' => $formatted]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

}

