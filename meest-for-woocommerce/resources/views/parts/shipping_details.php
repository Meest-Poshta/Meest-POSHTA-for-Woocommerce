<h2><?php _e('Shipping details', MEEST_PLUGIN_DOMAIN) ?></h2>
<table class="woocommerce-table shop_table gift_info">
    <tbody>
    <tr>
        <th><?php _e('Tracking number', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_html($parcel->barcode) ?></td>
    </tr>
    <tr>
        <th><?php _e('Delivery date', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_html($parcel->delivery_date) ?></td>
    </tr>
    <tr>
        <th><?php _e('Cost services', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_html($parcel->cost_services) ?></td>
    </tr>
    <tr>
        <th><?php _e('COD', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_html($parcel->cod) ?></td>
    </tr>
    </tbody>
</table>