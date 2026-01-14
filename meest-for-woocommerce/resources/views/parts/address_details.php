<h2><?php _e('Address details', MEEST_PLUGIN_DOMAIN) ?></h2>
<?php if (is_array($address) && !empty($address)): ?>
<table class="woocommerce-table shop_table gift_info">
    <tbody>
    <?php if (isset($address['country']['id']) && isset($options['country_id']['ua']) && $address['country']['id'] !== $options['country_id']['ua']): ?>
        <tr>
            <th><?php _e('Region', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_html($address['region']['text'] ?? '') ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <th><?php _e('City', MEEST_PLUGIN_DOMAIN) ?>:</th>
        <td><?php echo esc_html($address['city']['text'] ?? '') ?></td>
    </tr>
    <?php if (isset($address['delivery_type']) && $address['delivery_type'] === 'branch'): ?>
        <tr>
            <th><?php _e('Branch', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_html($address['branch']['text'] ?? '') ?></td>
        </tr>
    <?php elseif (isset($address['delivery_type']) && $address['delivery_type'] === 'poshtomat'): ?>
        <tr>
            <th><?php _e('Poshtomat', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_html($address['poshtomat']['text'] ?? '') ?></td>
        </tr>
    <?php else: ?>
        <tr>
            <th><?php _e('Street', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_html($address['street']['text'] ?? '') ?></td>
        </tr>
        <tr>
            <th><?php _e('Building', MEEST_PLUGIN_DOMAIN) ?> / <?php _e('Flat', MEEST_PLUGIN_DOMAIN) ?>:</th>
            <td><?php echo esc_html($address['building'] ?? '') ?> / <?php echo esc_html($address['flat'] ?? '') ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<?php else: ?>
<p><?php _e('Address information is not available.', MEEST_PLUGIN_DOMAIN) ?></p>
<?php endif; ?>
