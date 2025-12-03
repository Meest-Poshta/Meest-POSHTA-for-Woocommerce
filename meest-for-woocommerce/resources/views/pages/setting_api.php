<?php

use MeestShipping\Helpers\Html;

?>
<form method="post" id="meest-setting-api">
    <?php wp_nonce_field(MEEST_PLUGIN_DOMAIN) ?>
    <input type="hidden" name="action" value="update_api">
    <div class="table-container">
        <h3 class="table-container-title"><?php _e('API', MEEST_PLUGIN_DOMAIN) ?></h3>
        <div class="table-grid">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('Url', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td colspan="3">
                        <input
                                type="text"
                                name="option[url]"
                                value="<?php echo esc_attr($options['url']) ?>"
                        >
                    </td>
                </tr>
                <tr id="meest_access_token" <?= empty($options['credential']['username']) ? '' : 'hidden' ?>>
                    <th scope="row">
                        <label>
                            <select class="meest_access_type">
                                <option value="token" selected><?php _e('Token', MEEST_PLUGIN_DOMAIN) ?></option>
                                <option value="username"><?php _e('Username', MEEST_PLUGIN_DOMAIN) ?></option>
                            </select>
                            <abbr class="required" title="required">*</abbr>
                        </label>
                    </th>
                    <td colspan="3">
                        <input
                                type="password"
                                name="option[credential][token]"
                                value="<?php echo esc_attr($options['credential']['token']) ?>"
                        >
                    </td>
                </tr>
                <tr id="meest_access_username" <?= empty($options['credential']['username']) ? 'hidden' : '' ?>>
                    <th scope="row">
                        <label>
                            <select class="meest_access_type">
                                <option value="token"><?php _e('Token', MEEST_PLUGIN_DOMAIN) ?></option>
                                <option value="username" selected><?php _e('Username', MEEST_PLUGIN_DOMAIN) ?></option>
                            </select>
                            <abbr class="required" title="required">*</abbr>
                        </label>
                    </th>
                    <td>
                        <input
                                type="text"
                                name="option[credential][username]"
                                value="<?php echo esc_attr($options['credential']['username']) ?>"
                        >
                    </td>
                    <th scope="row">
                        <label><?php _e('Password', MEEST_PLUGIN_DOMAIN) ?> <abbr class="required" title="required">*</abbr></label>
                    </th>
                    <td>
                        <input
                                type="password"
                                name="option[credential][password]"
                                value="<?php echo esc_attr($options['credential']['password']) ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="4">
                        <p class="hint"><?php _e('If you do not have an API key, you can get it by following the link', MEEST_PLUGIN_DOMAIN) ?> <a target="_blank" href="https://wiki.meest-group.com/api/ua/v3.0/openAPI">openAPI</a></p>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Address data get from DB', MEEST_PLUGIN_DOMAIN) ?></label>
                    </th>
                    <td>
                        <?php echo Html::checkbox(
                            'meest_dictionary_is_db',
                            'option[dictionary][is_db]',
                            $options['dictionary']['is_db']
                        ) ?>
                    </td>
                </tr>
                <?php if ($options['dictionary']['is_db']): ?>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Auto update dictionary', MEEST_PLUGIN_DOMAIN) ?></label>
                        </th>
                        <td>
                            <?php echo Html::checkbox(
                                'meest_dictionary_auto_update',
                                'option[dictionary][auto_update]',
                                $options['dictionary']['auto_update']
                            ) ?>
                        </td>
                        <th scope="row">
                            <label><?php _e('Manual update dictionary', MEEST_PLUGIN_DOMAIN) ?></label>
                        </th>
                        <td>
                            <div style="grid-template-columns:1fr 7fr; display: grid; grid-gap: 6px;">
                            <button class="wpbtn button button-warning" id="meest_dictionary_manual_update"><?= __('Update', MEEST_PLUGIN_DOMAIN) ?></button>
                            <p id="meest_dictionary_manual_update_response"></p>
                            </div>
                        </td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="submit">
        <button type="submit" name="submit" class="button button-primary button-large" value="Save changes"><?php _e('Save') ?></button>
    </p>
</form>
