<?php

use MeestShipping\Helpers\Html;

?>
<form method="post" id="meest-setting-general">
    <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
    <input type="hidden" name="action" value="update_general">
    <?php if (!empty($options['tokens'])) : ?>
    <div class="table-container">
        <h3 class="table-container-title"><?php _e('Agent', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('First name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_contact_first_name"
                                type="text"
                                name="option[contact][first_name]"
                                value="<?php echo esc_attr($options['contact']['first_name'] ?? null) ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Last name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_contact_last_name"
                                type="text"
                                name="option[contact][last_name]"
                                value="<?php echo esc_attr($options['contact']['last_name'] ?? null) ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Middle name', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_contact_middle_name"
                                type="text"
                                name="option[contact][middle_name]"
                                value="<?php echo esc_attr($options['contact']['middle_name'] ?? null) ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Mobile phone', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_contact_phone"
                                type="text"
                                name="option[contact][phone]"
                                value="<?php echo esc_attr($options['contact']['phone'] ?? null) ?>"
                                placeholder="+XXXXXXXXXXX"
                        >
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container">
        <h3 class="table-container-title"><?php _e('Address', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('Country', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <select
                                id="meest_address_country_id"
                                name="option[address][country][id]"
                                value="<?php echo esc_attr($options['address']['country']['id']) ?>"
                                data-placeholder="<?php _e('Select a country', MEEST_PLUGIN_DOMAIN) ?>"
                        >
                            <option value="<?php echo esc_attr($options['address']['country']['id']) ?>"><?php echo esc_attr($options['address']['country']['text']) ?></option>
                        </select>
                        <input
                                id="meest_address_country_text"
                                type="hidden"
                                name="option[address][country][text]"
                                value="<?php echo esc_attr($options['address']['country']['text']) ?>"
                        >
                        <input
                                id="meest_address_country_code"
                                type="hidden"
                                name="option[address][country][code]"
                                value="<?php echo esc_attr($options['address']['country']['code']) ?>"
                        >
                        <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr style="display: none;">
                    <th scope="row">
                        <label><?php _e('Region', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_address_region_text"
                                type="hidden"
                                name="option[address][region][text]"
                                value="<?php echo esc_attr($options['address']['region']['text'] ?? '') ?>"
                        >
                        <select
                                id="meest_address_region_id"
                                name="option[address][region][id]"
                                value="<?php echo esc_attr($options['address']['region']['id'] ?? '') ?>"
                                data-placeholder="<?php _e('Select a region', MEEST_PLUGIN_DOMAIN) ?>"
                        >
                            <option value="<?php echo esc_attr($options['address']['region']['id'] ?? '') ?>"><?php echo esc_attr($options['address']['region']['text'] ?? '') ?></option>
                        </select>
                        <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('City', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_address_city_text"
                                type="text"
                                name="option[address][city][text]"
                                value="<?php echo esc_attr($options['address']['city']['text']) ?>"
                                style="display: none"
                        >
                        <select
                                id="meest_address_city_id"
                                name="option[address][city][id]"
                                value="<?php echo esc_attr($options['address']['city']['id']) ?>"
                                data-placeholder="<?php _e('Select a city', MEEST_PLUGIN_DOMAIN) ?>"
                        >
                            <option value="<?php echo esc_attr($options['address']['city']['id']) ?>"><?php echo esc_attr($options['address']['city']['text']) ?></option>
                        </select>
                        <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Delivery type', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td class="table-radio">
                        <?php echo Html::radioInput(
                            'meest_address_branch_delivery',
                            'option[address][delivery_type]',
                            __('Branch delivery', MEEST_PLUGIN_DOMAIN),
                            'branch',
                            $options['address']['delivery_type'] === 'branch' ? 'checked' : null
                        ) ?>
                        <?php echo Html::radioInput(
                            'meest_address_address_delivery',
                            'option[address][delivery_type]',
                            __('Address delivery', MEEST_PLUGIN_DOMAIN),
                            'address',
                            $options['address']['delivery_type'] === 'address' ? 'checked' : null
                        ) ?>
                    </td>
                </tr>
                <tr <?php echo $options['address']['delivery_type'] === 'branch' ? 'hidden' : null ?>>
                    <th scope="row">
                        <label><?php _e('Address', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td style="grid-template-columns: 8fr 2fr 2fr 2fr; display: grid; grid-gap: 6px;">
                        <div>
                            <input
                                    id="meest_address_street_text"
                                    type="text"
                                    name="option[address][street][text]"
                                    value="<?php echo esc_attr($options['address']['street']['text']) ?>"
                                    style="display: none"
                            >
                            <select
                                    id="meest_address_street_id"
                                    name="option[address][street][id]"
                                    value="<?php echo esc_attr($options['address']['street']['id']) ?>"
                                    data-placeholder="<?php _e('Select a street', MEEST_PLUGIN_DOMAIN) ?>"
                            >
                                <option value="<?php echo esc_attr($options['address']['street']['id']) ?>"><?php echo esc_attr($options['address']['street']['text']) ?></option>
                            </select>
                            <p class="hint"><?php //_e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                        </div>
                        <div>
                            <input
                                    id="meest_address_building"
                                    type="text"
                                    name="option[address][building]"
                                    value="<?php echo esc_attr($options['address']['building']) ?>"
                                    placeholder="<?php _e('Building', MEEST_PLUGIN_DOMAIN) ?>"
                            >
                        </div>
                        <div>
                            <input
                                    id="meest_address_flat"
                                    type="text"
                                    name="option[address][flat]"
                                    value="<?php echo esc_attr($options['address']['flat']) ?>"
                                    placeholder="<?php _e('Flat', MEEST_PLUGIN_DOMAIN) ?>"
                            >
                        </div>
                        <div>
                            <input
                                    id="meest_address_postcode"
                                    type="text"
                                    name="option[address][postcode]"
                                    value="<?php echo esc_attr($options['address']['postcode']) ?>"
                                    placeholder="<?php _e('Post code', MEEST_PLUGIN_DOMAIN) ?>"
                            >
                        </div>
                    </td>
                </tr>
                <tr <?php echo $options['address']['delivery_type'] === 'address' ? 'hidden' : null ?>>
                    <th scope="row">
                        <label><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                id="meest_address_branch_text"
                                type="hidden"
                                name="option[address][branch][text]"
                                value="<?php echo esc_attr($options['address']['branch']['text']) ?>"
                        >
                        <select
                                id="meest_address_branch_id"
                                name="option[address][branch][id]"
                                value="<?php echo esc_attr($options['address']['branch']['id']) ?>"
                                data-placeholder="<?php _e('Select a branch', MEEST_PLUGIN_DOMAIN) ?>"
                        >
                            <option value="<?php echo esc_attr($options['address']['branch']['id']) ?>"><?php echo esc_attr($options['address']['branch']['text']) ?></option>
                        </select>
                        <p class="hint"><?php _e('Select from the list', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container">
        <h3 class="table-container-title"><?php _e('Shipping', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('Delivery types', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <label style="display: block; margin-bottom: 10px;">
                            <input 
                                type="checkbox" 
                                name="option[shipping][delivery_type_branch]" 
                                value="1"
                                <?php checked(!empty($options['shipping']['delivery_type_branch'])); ?>
                            />
                            <?php _e('Branch (Warehouse)', MEEST_PLUGIN_DOMAIN) ?>
                        </label>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input 
                                type="checkbox" 
                                name="option[shipping][delivery_type_poshtomat]" 
                                value="1"
                                <?php checked(!empty($options['shipping']['delivery_type_poshtomat'])); ?>
                            />
                            <?php _e('Poshtomat', MEEST_PLUGIN_DOMAIN) ?>
                        </label>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input 
                                type="checkbox" 
                                name="option[shipping][delivery_type_address]" 
                                value="1"
                                <?php checked(!empty($options['shipping']['delivery_type_address'])); ?>
                            />
                            <?php _e('Address (Courier)', MEEST_PLUGIN_DOMAIN) ?>
                        </label>
                        
                        <p class="hint"><?php _e('Select which delivery types should be available for customers', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Show limits for branches', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_shipping_branch_limits',
                            'option[shipping][branch_limits]',
                            $options['shipping']['branch_limits']
                        ) ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Send email after change order status ”Shipped”', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_shipping_send_email',
                            'option[shipping][send_email]',
                            $options['shipping']['send_email']
                        ) ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container">
        <h3 class="table-container-title"><?php _e('Default parcel parameters', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('Weight', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td style="grid-template-columns:2fr 6fr ; display: grid; grid-gap: 6px;">
                        <div>
                            <input
                                    id="meest_parcel_weight"
                                    type="text"
                                    name="option[parcel][weight]"
                                    value="<?php echo esc_attr($options['parcel']['weight'] ?? null) ?>"
                            >
                            <span style="margin-left:-22px;">кг</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('LWH', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td style="grid-template-columns:2fr 2fr 2fr 2fr; display: grid; grid-gap: 6px;">
                        <div>
                            <input
                                id="meest_parcel_length"
                                type="text"
                                name="option[parcel][lwh][0]"
                                value="<?php echo esc_attr($options['parcel']['lwh'][0]) ?>"
                            >
                            <span style="margin-left:-26px;">см</span>
                        </div>
                        <div>
                            <input
                                id="meest_parcel_width"
                                type="text"
                                name="option[parcel][lwh][1]"
                                value="<?php echo esc_attr($options['parcel']['lwh'][1]) ?>"
                            >
                            <span style="margin-left:-26px;">см</span>
                        </div>
                        <div>
                            <input
                                id="meest_parcel_high"
                                type="text"
                                name="option[parcel][lwh][2]"
                                value="<?php echo esc_attr($options['parcel']['lwh'][2]) ?>"
                            >
                            <span style="margin-left:-26px;">см</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Insurance', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_parcel_is_insurance',
                            'option[parcel][is_insurance]',
                            $options['parcel']['is_insurance']
                        ) ?>
                        <p class="hint"><?php _e('If not checked, insurance will be standard', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                    <th scope="row">
                        <label><?php _e('Fixed insurance', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <input
                                id="meest_parcel_insurance"
                                type="text"
                                name="option[parcel][insurance]"
                                value="<?php echo esc_attr($options['parcel']['insurance'] ?? null) ?>"
                        >
                        <p class="hint"><?php _e('If empty, then the insurance is equal to the order amount', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
    <p class="submit">
        <button type="submit" name="submit" class="button button-primary button-large" value="Save changes"><?php _e('Save', MEEST_PLUGIN_DOMAIN) ?></button>
    </p>
</form>
