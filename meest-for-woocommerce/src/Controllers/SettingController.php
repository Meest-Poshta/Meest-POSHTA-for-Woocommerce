<?php
namespace MeestShipping\Controllers;

use MeestShipping\Core\Controller;
use MeestShipping\Core\Error;
use MeestShipping\Core\Request;
use MeestShipping\Core\View;
use MeestShipping\Modules\Asset;
use MeestShipping\Core\Migration;
use MeestShipping\Resources\SettingResource;

class SettingController extends Controller
{
    public function edit()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        Asset::load(['meest-address', 'meest-setting', 'meest']);
        Asset::localize('meest-setting');

        $request = new Request();

        return View::render('views/pages/setting', [
            'request' => $request
        ]);
    }

    public function update()
    {
        if (Request::isPost()) {
            if (!Request::isWpnonce()) {
                return false;
            }

            $request = new Request($_POST);

            $options = [];

            if ($request->action === 'update_api') {
                $options = SettingResource::make($request->option, 'delivery_api');

                if ($this->options['dictionary']['is_db'] !== $options['dictionary']['is_db']) {
                    $migration = new Migration();
                    $options['dictionary']['is_db']
                        ? $migration->create(['regions', 'districts', 'cities', 'streets', 'branches'])
                        : $migration->delete(['regions', 'districts', 'cities', 'streets', 'branches']);
                }

                if ($this->options['dictionary']['auto_update'] !== $options['dictionary']['auto_update']) {
                    $cron = meest_init('Cron');
                    $options['dictionary']['auto_update']
                        ? $cron->add()
                        : $cron->delete();
                }

                $this->options = array_replace_recursive($this->options, $options);

                if (!empty($this->options['credential']['token'])) {
                    // Зберігаємо contract_id перед очищенням username/password
                    $contractId = $options['credential']['contract_id'] ?? null;
                    $options['credential']['username'] = null;
                    $options['credential']['password'] = null;
                    // Повертаємо contract_id назад
                    if ($contractId !== null) {
                        $options['credential']['contract_id'] = $contractId;
                    }
                    $tokens = [
                        'token' => $this->options['credential']['token'],
                        'refreshToken' => null,
                        'expiresIn' => null,
                    ];
                } else {
                    $tokens = meest_init('Option')->getTokens($this->options['credential']);
                }

                if (!empty($tokens)) {
                    $tokens = meest_sanitize_text_field($tokens);
                    meest_init('Option')->saveTokens($tokens);
                }
            } elseif ($request->action === 'update_general') {
                $options = SettingResource::make($request->option, 'general');
            } elseif ($request->action === 'update_cost') {
                $options = SettingResource::make($request->option, 'delivery_cost');
            }

            if (Error::has()) {
                Error::add('setting-save', __('Setting not saved!', MEEST_PLUGIN_DOMAIN), 'error');
            } else {
                meest_init('Option')->saveOptions($options);

                Error::add('setting-save', __('Setting saved!', MEEST_PLUGIN_DOMAIN), 'success');
            }
        }
    }
}
