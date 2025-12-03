<?php

use MeestShipping\Helpers\Html;

$deliveryCosts = $options['shipping']['delivery_cost'] ?: [[null, null, null]];
$deliveryCostType = $options['shipping']['delivery_cost_type'];
?>
<form method="post" id="meest-setting-cost">
    <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
    <input type="hidden" name="action" value="update_cost">
    <div class="table-container">
        <h3 class="table-container-title"><?php _e('Shipping', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('СOD', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_shipping_auto_cod',
                            'option[shipping][auto_cod]',
                            $options['shipping']['auto_cod']
                        ) ?>
                        <p class="hint"><?php _e('If checked, СOD will be set by price of items', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Calculate shipping cost', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_shipping_calc_cost',
                            'option[shipping][calc_cost]',
                            $options['shipping']['calc_cost']
                        ) ?>
                        <p class="hint"><?php _e('If checked, the delivery cost will be displayed', MEEST_PLUGIN_DOMAIN) ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Delivery cost type', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <select
                                id="meest_shipping_delivery_cost_type"
                                name="option[shipping][delivery_cost_type]"
                                value="<?php echo esc_attr($options['shipping']['delivery_cost_type']) ?>"
                        >
                            <option value="api" <?php echo $deliveryCostType === 'api' ? 'selected' : '' ?>><?php _e('According to Meest Post tariffs', MEEST_PLUGIN_DOMAIN) ?></option>
                            <option value="fixed" <?php echo $deliveryCostType === 'fixed' ? 'selected' : '' ?>><?php _e('Fixed', MEEST_PLUGIN_DOMAIN) ?></option>
                            <option value="range" <?php echo $deliveryCostType === 'range' ? 'selected' : '' ?>><?php _e('Range', MEEST_PLUGIN_DOMAIN) ?></option>
                        </select>
                    </td>
                </tr>
                <tr id="meest_delivery_cost_fixed_container" <?php echo $deliveryCostType !== 'fixed' ? 'style="display: none;"' : '' ?>>
                    <th scope="row">
                        <label><?php _e('Fixed delivery cost', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td style="grid-template-columns:2fr 6fr ; display: grid; grid-gap: 6px;">
                        <input
                                id="meest_delivery_cost"
                                type="text"
                                name="option[shipping][delivery_cost_fixed]"
                                value="<?= esc_attr($deliveryCosts[0][2] ?? null) ?>"
                        >
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="table-container" id="meest_delivery_cost_range_container" <?php echo $deliveryCostType !== 'range' ? 'style="display: none;"' : '' ?>>
        <div class="table-grid">
            <table id="meest_delivery_cost_table" class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                <tr>
                    <th scope="row"><label><?php _e('Min price', MEEST_PLUGIN_DOMAIN) ?></label></th>
                    <th scope="row"><label><?php _e('Max price', MEEST_PLUGIN_DOMAIN) ?></label></th>
                    <th scope="row"><label><?php _e('Delivery cost', MEEST_PLUGIN_DOMAIN) ?></label></th>
                    <th scope="row"><label><?php _e('Action', MEEST_PLUGIN_DOMAIN) ?></label></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($deliveryCosts as $index => $deliveryCost): ?>
                    <tr class="meest_delivery_cost_item" data-index="<?= $index ?>">
                        <td>
                            <input
                                    id="meest_shipping_delivery_cost"
                                    type="text"
                                    name="option[shipping][delivery_cost][<?= $index ?>][]"
                                    value="<?= esc_attr($deliveryCost[0] ?? null) ?>"
                            >
                        </td>
                        <td>
                            <input
                                    id="meest_shipping_delivery_cost"
                                    type="text"
                                    name="option[shipping][delivery_cost][<?= $index ?>][]"
                                    value="<?= esc_attr($deliveryCost[1] ?? null) ?>"
                            >
                        </td>
                        <td>
                            <input
                                    id="meest_delivery_cost"
                                    type="text"
                                    name="option[shipping][delivery_cost][<?= $index ?>][]"
                                    value="<?= esc_attr($deliveryCost[2] ?? null) ?>"
                            >
                        </td>
                        <td>
                            <button class="button button-danger meest_delivery_cost_delete">x</button>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4" style="text-align: center;">
                        <button id="meest_delivery_cost_add" class="button button-primary">+</button>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <p class="submit">
        <button type="submit" name="submit" class="button button-primary button-large" value="Save changes"><?php _e('Save') ?></button>
    </p>
</form>
