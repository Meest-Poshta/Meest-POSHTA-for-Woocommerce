<?php
namespace MeestShipping\Controllers;

use MeestShipping\Core\Error;
use MeestShipping\Core\Http;
use MeestShipping\Core\Controller;
use MeestShipping\Core\Request;
use MeestShipping\Core\View;
use MeestShipping\Models\User;
use MeestShipping\Models\Parcel;
use MeestShipping\Repositories\PackTypesRepository;
use MeestShipping\Resources\ParcelResource;
use MeestShipping\Resources\ParcelApiResource;
use MeestShipping\Modules\Asset;
use MeestShipping\Tables\ParcelTable;
use MeestShipping\Traits\Email;
use MeestShipping\Traits\Helper;

class ParcelController extends Controller
{
    use Helper, Email;

    public function index()
    {
        $parcelTable = new ParcelTable();
        $totalPickup = count($_SESSION['meest_pickup_parcels'] ?? []);

        if ($parcelTable->current_action() === false) {
            $parcelTable->prepare_items();

            Asset::load(['meest']);

            return View::render('views/pages/parcel_list', [
                'parcelTable' => $parcelTable,
                'totalPickup' => $totalPickup,
            ]);
        }
    }

    public function create()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!empty($_GET['post'])) {
            $orderId = sanitize_text_field($_GET['post']);
            $parcel = Parcel::find($orderId, 'order_id');
            if ($parcel !== null) {
                return wp_redirect('admin.php?page=meest_parcel&action=edit&id='.$parcel->id);
            }

            $order = wc_get_order($orderId);

            if ($order === false) {
                return wp_redirect('admin.php?page=meest_parcel&action=create');
            }
        }

      

        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }
           
            try {
                $request = new Request($_POST);
                $parcelApiData = ParcelApiResource::make($request->all());
                $response = meest_init('Api')->parcelCreate($parcelApiData);

                if (!empty($response)) {
                    $parcelData = ParcelResource::make($request->all());
                    var_dump($parcelData);
                    $parcel = new Parcel([
                        'order_id' => $orderId ?? null,
                        'parcel_id' => $response['parcelID'],
                        'pack_type_id' => $parcelData['pack_type'],
                        'sender' => $parcelData['sender'],
                        'receiver' => $parcelData['receiver'],
                        'pay_type' => $parcelData['pay_type'],
                        'receiver_pay' => $parcelData['receiver_pay'],
                        'cod' => $parcelData['cod'],
                        'insurance' => $parcelData['insurance'],
                        'weight' => $parcelData['weight'],
                        'lwh' => $parcelData['lwh'],
                        'notation' => $parcelData['notation'],
                        'barcode' => $response['barCode'],
                        'cost_services' => $response['costServices'],
                        'delivery_date' => date("Y-m-d", strtotime($response['estimatedDeliveryDate'])),
                    ]);

                    if ($parcel->save()) {
                        do_action('meest_parcel_created', $parcel, $order ?? null);

                        Error::add('parcel-create', __('Parcel was created.', MEEST_PLUGIN_DOMAIN), 'success');

                        return wp_redirect('admin.php?page=meest_parcel');
                    }
                }
            } catch (\Exception $e) {
                Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
            }
        }

        $packTypes = PackTypesRepository::instance()->get();
        $senderData = array_merge($this->options['contact'] ?? [], $this->options['address'] ?? []);
        $sender = new User($senderData);

        if (!empty($order)) {
            $orderData = $order->get_data();
            $shippingMethods = $order->get_shipping_methods();
            $orderShipping = array_shift($shippingMethods);
            
            // Получаем адрес Meest из мета данных заказа
            $meestAddress = $order->get_meta('_meest_address');
            $meestAddress = !empty($meestAddress) ? (is_string($meestAddress) ? maybe_unserialize($meestAddress) : $meestAddress) : [];
            
            // Получаем дополнительные данные Meest из отдельных мета-полей
            $meestDeliveryType = $order->get_meta('_meest_delivery_type');
            $meestCityId = $order->get_meta('_meest_city_id');
            $meestCityText = $order->get_meta('_meest_city_text');
            $meestBranchId = $order->get_meta('_meest_branch_id');
            $meestBranchText = $order->get_meta('_meest_branch_text');
            $meestPoshtomatId = $order->get_meta('_meest_poshtomat_id');
            $meestPoshtomatText = $order->get_meta('_meest_poshtomat_text');
            $meestStreetId = $order->get_meta('_meest_street_id');
            $meestStreetText = $order->get_meta('_meest_street_text');
            
            $meestBuilding = $order->get_meta('_meest_building');
            $meestFlat = $order->get_meta('_meest_flat');
            
            // Если нет в отдельных полях, пробуем получить из _meest_address
            if (!$meestDeliveryType && isset($meestAddress['delivery_type'])) {
                $meestDeliveryType = $meestAddress['delivery_type'];
            }
            
            if (!$meestBranchId && isset($meestAddress['branch']['id'])) {
                $meestBranchId = $meestAddress['branch']['id'];
                $meestBranchText = $meestAddress['branch']['text'] ?? '';
            }
            
            if (!$meestPoshtomatId && isset($meestAddress['poshtomat']['id'])) {
                $meestPoshtomatId = $meestAddress['poshtomat']['id'];
                $meestPoshtomatText = $meestAddress['poshtomat']['text'] ?? '';
            }
            
            // FALLBACK: Если delivery_type = poshtomat, но данные в branch полях (старые заказы)
            if ($meestDeliveryType === 'poshtomat' && !$meestPoshtomatId && $meestBranchId) {
                $meestPoshtomatId = $meestBranchId;
                $meestPoshtomatText = $meestBranchText;
                // Очищаем branch данные чтобы не было дублирования
                $meestBranchId = '';
                $meestBranchText = '';
            }
            
            if (!$meestStreetId && isset($meestAddress['street']['id'])) {
                $meestStreetId = $meestAddress['street']['id'];
                $meestStreetText = $meestAddress['street']['text'] ?? '';
            }
            
            if (!$meestBuilding && isset($meestAddress['building'])) {
                $meestBuilding = $meestAddress['building'];
            }
            
            if (!$meestFlat && isset($meestAddress['flat'])) {
                $meestFlat = $meestAddress['flat'];
            }
            
            // Формируем данные для country
            $countryData = $meestAddress['country'] ?? [];
            if (is_string($countryData)) {
                $countryData = ['id' => $countryData, 'code' => $countryData, 'text' => ''];
            } elseif (is_array($countryData)) {
                // Если есть code но нет id, копируем code в id
                if (empty($countryData['id']) && !empty($countryData['code'])) {
                    $countryData['id'] = $countryData['code'];
                }
                // Если есть id но нет code, копируем id в code
                if (empty($countryData['code']) && !empty($countryData['id'])) {
                    $countryData['code'] = $countryData['id'];
                }
                // Если text пустой, пытаемся получить название страны из WooCommerce
                if (empty($countryData['text']) && !empty($countryData['code'])) {
                    $countries = WC()->countries->get_countries();
                    $countryData['text'] = $countries[$countryData['code']] ?? $countryData['code'];
                }
            }
            
            // Если countryData пустой, берем из данных заказа
            if (empty($countryData) || empty($countryData['code'])) {
                $shippingCountry = $orderData['shipping']['country'] ?? '';
                if ($shippingCountry) {
                    $countries = WC()->countries->get_countries();
                    $countryData = [
                        'id' => $shippingCountry,
                        'code' => $shippingCountry,
                        'text' => $countries[$shippingCountry] ?? $shippingCountry
                    ];
                }
            }
            
            // Для Украины заменяем код на UUID для корректной работы Select2
            if (isset($countryData['code']) && $countryData['code'] === 'UA') {
                $countryData['id'] = 'c35b6195-4ea3-11de-8591-001d600938f8';
            }
            
            // Формируем данные для region
            $regionData = $meestAddress['region'] ?? [];
            if (is_string($regionData)) {
                $regionData = ['id' => $regionData, 'text' => ''];
            } elseif (is_array($regionData) && empty($regionData['text']) && !empty($regionData['id'])) {
                // Если text пустой, используем id как text для отображения
                $regionData['text'] = $regionData['id'];
            }
            
            // Формируем данные для city - приоритет у отдельных полей
            if ($meestCityId || $meestCityText) {
                $cityData = [
                    'id' => $meestCityId ?: '',
                    'text' => $meestCityText ?: ''
                ];
            } else {
                $cityData = $meestAddress['city'] ?? [];
                if (is_string($cityData)) {
                    $cityData = ['id' => '', 'text' => $cityData];
                }
            }
            
            // Формируем данные получателя с приоритетом на Meest данные
            $receiverData = array_merge(
                $orderData['shipping'],
                [
                    'phone' => $orderData['billing']['phone'],
                    'country' => $countryData ?: ['id' => $orderData['shipping']['country'] ?? '', 'code' => $orderData['shipping']['country'] ?? '', 'text' => ''],
                    'region' => $regionData ?: ['id' => $orderData['shipping']['state'] ?? '', 'text' => ''],
                    'city' => $cityData ?: ['id' => '', 'text' => $orderData['shipping']['city'] ?? ''],
                    'street' => null,
                    'building' => null,
                    'flat' => null,
                ],
                $orderShipping->get_meta('receiver') ?: []
            );
            
            // Добавляем Meest специфичные данные если есть
            if ($meestDeliveryType) {
                $receiverData['delivery_type'] = $meestDeliveryType;
            }
            
            if ($meestDeliveryType === 'branch' && $meestBranchId) {
                $receiverData['branch'] = [
                    'id' => $meestBranchId,
                    'text' => $meestBranchText
                ];
            } elseif ($meestDeliveryType === 'poshtomat' && $meestPoshtomatId) {
                $receiverData['poshtomat'] = [
                    'id' => $meestPoshtomatId,
                    'text' => $meestPoshtomatText
                ];
            } elseif ($meestDeliveryType === 'address') {
                if ($meestStreetId || $meestStreetText) {
                    $receiverData['street'] = [
                        'id' => $meestStreetId ?: '',
                        'text' => $meestStreetText ?: ''
                    ];
                }
                if ($meestBuilding) {
                    $receiverData['building'] = $meestBuilding;
                }
                if ($meestFlat) {
                    $receiverData['flat'] = $meestFlat;
                }
            }
            
            $receiver = new User($receiverData);

            $package = [
                'quantity' => 0,
                'insurance' => 0,
                'weight' => 0,
                'lwh' => [0, 0, 0],
                'description' => []
            ];

            foreach ($order->get_items() as $item) {
                $itemData = $item->get_data();
                $product = wc_get_product($itemData['product_id']);
                $productData = $product->get_data();
                $package['quantity']++;
                $package['insurance'] += $itemData['total'];
                $package['weight'] += $productData['weight'] ?: 0;
                array_push($package['description'], $productData['name']);
                self::implodePack($package['lwh'], [$productData['length'], $productData['width'], $productData['height']]);
            }

            $package['order_id'] = $orderData['id'];
            $package['cod'] = $orderData['payment_method'] === 'cod' ? $orderData['total'] : 0;
            $package['notation'] = implode(', ', $package['description']).'. '.$orderData['customer_note'];
        } else {
            $receiver = new User($this->options['empty_user']);
            $package = [
                'quantity' => 1,
                'insurance' => $this->options['parcel']['insurance'],
                'weight' => $this->options['parcel']['weight'],
                'lwh' => $this->options['parcel']['lwh'],
            ];
        }

        if (isset($receiver->country['id']) && $receiver->country['id'] !== $this->options['country_id']['ua']) {
            $this->options['parcel']['receiver_pay'] = 0;
        }

        $parcel = new Parcel(array_merge($package, [
            'pay_type' => $this->options['parcel']['pay_type'],
            'receiver_pay' => $this->options['parcel']['receiver_pay']
        ]));

        Asset::load(['meest-address', 'meest-parcel', 'meest']);
        Asset::localize('meest-parcel');
     
        return View::render('views/pages/parcel_form', [
            'options' => $this->options,
            'sender' => $sender,
            'receiver' => $receiver,
            'parcel' => $parcel,
            'packTypes' => $packTypes,
        ]);
    }

    public function update()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $parcel = Parcel::find(sanitize_text_field($_GET['id']));
        $order = !empty($parcel->order_id) ? wc_get_order($parcel->order_id) : false;
        $request = new Request($_POST);

        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }

            try {
                $parcelApiData = ParcelApiResource::make($request->all());
                $response = meest_init('Api')->parcelUpdate($parcel->parcel_id, $parcelApiData);
            } catch (\Exception $e) {
                Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
            }

            if (!empty($response)) {
                $parcelData = ParcelResource::make($request->all());
                $data = [
                    'parcel_id' => $response['parcelID'],
                    'pack_type_id' => $parcelData['pack_type'],
                    'sender' => $parcelData['sender'],
                    'receiver' => $parcelData['receiver'],
                    'pay_type' => $parcelData['pay_type'],
                    'receiver_pay' => $parcelData['receiver_pay'],
                    'cod' => $parcelData['cod'],
                    'insurance' => $parcelData['insurance'],
                    'weight' => $parcelData['weight'],
                    'lwh' => $parcelData['lwh'],
                    'notation' => $parcelData['notation'],
                    'barcode' => $response['barCode'],
                    'cost_services' => $response['costServices'],
                    'delivery_date' => date("Y-m-d", strtotime($response['estimatedDeliveryDate'])),
                ];

                if ($parcel->update($data)) {
                    do_action('meest_parcel_updated', $parcel, $order ?? null);

                    Error::add('parcel-update', __('Parcel was updated.', MEEST_PLUGIN_DOMAIN), 'success');

                    return wp_redirect('admin.php?page=meest_parcel');
                }
            }
        }

        $packTypes = PackTypesRepository::instance()->get();
        $sender = new User($parcel->sender);
        $receiver = new User($parcel->receiver);

        Asset::load(['meest-address', 'meest-parcel', 'meest']);
        Asset::localize('meest-parcel');
     
        return View::render('views/pages/parcel_form', [
            'options' => $this->options,
            'sender' => $sender,
            'receiver' => $receiver,
            'parcel' => $parcel,
            'packTypes' => $packTypes,
        ]);
    }

    public function delete()
    {
        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        try {
            $response = meest_init('Api')->parcelDelete($parcel->parcel_id);

            if ($response === []) {
                $parcel->delete();
            }
        } catch (\Exception $e) {
            Http::addSettingsError('Exception', $e->getCode(), $e->getMessage());
        }

        do_action('meest_parcel_deleted', $parcel, $order ?? null);

        Error::add('parcel-delete', __('Parcel was deleted.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }

    public function email()
    {
        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        if (!empty($parcel->order_id)) {
            $order = wc_get_order($parcel->order_id);

            if ($this->sendMailByOrder($order, $parcel)) {
                $parcel->update(['is_email' => 1]);

                Error::add('email-sent', __('Email was sent.', MEEST_PLUGIN_DOMAIN), 'success');
            } else {
                Error::add('email-sent', __('Email wasn\'t sent.', MEEST_PLUGIN_DOMAIN), 'error');
            }

            return wp_safe_redirect('admin.php?page=meest_parcel');
        }
    }

    public function tracking()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (empty($_GET['id'])) {
            return wp_safe_redirect('admin.php?page=meest_parcel');
        }

        $parcel = Parcel::find(sanitize_text_field($_GET['id']));

        $tracking = meest_init('Api')->tracking($parcel->barcode);

        Asset::load(['meest']);

        return View::render('views/pages/parcel_tracking', [
            'parcel' => $parcel,
            'tracking' => $tracking,
        ]);
    }

    public function pickup()
    {
        if (!empty($_GET['id'])) {
            $parcel = Parcel::find(sanitize_text_field($_GET['id']));
            if ($parcel !== null) {
                if (!isset($_SESSION['meest_pickup_parcels'])) {
                    $_SESSION['meest_pickup_parcels'] = [];
                }
                if (in_array($parcel->id, $_SESSION['meest_pickup_parcels']) === false) {
                    array_push($_SESSION['meest_pickup_parcels'], $parcel->id);
                }
            }
        }

        Error::add('parcel-pickup', __('Parcel was pickuped.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }

    public function unPickup()
    {
        if (!empty($_SESSION['meest_pickup_parcels']) && !empty($_GET['id'])) {
            array_splice($_SESSION['meest_pickup_parcels'], array_search(sanitize_text_field($_GET['id']), $_SESSION['meest_pickup_parcels']), 1);
        }

        Error::add('parcel-unpickup', __('Parcel was unpickuped.', MEEST_PLUGIN_DOMAIN), 'success');

        return wp_redirect('admin.php?page=meest_parcel');
    }
}
