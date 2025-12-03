<?php

namespace MeestShipping\Core;

class MeestShippingMethod extends \WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->instance_id = absint($instance_id);
        $this->id = MEEST_PLUGIN_NAME;
        $this->method_title = __('Meest Post', MEEST_PLUGIN_DOMAIN);
        $this->method_description = $this->get_description();
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Init your settings
     *
     * @access publicv
     * @return void
     */
    private function init()
    {
        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option('title') ? __($this->get_option('title'), MEEST_PLUGIN_DOMAIN) : __('Meest Post', MEEST_PLUGIN_DOMAIN);

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Name', 'woocommerce' ),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', MEEST_PLUGIN_DOMAIN),
                'default' => __('Meest Post', MEEST_PLUGIN_DOMAIN)
            ],
            'settings' => [
                'title' => null,
                'type' => 'hidden',
                'description' => sprintf(
                    __('Other setting find available at <a href="%s">link</a>.', MEEST_PLUGIN_DOMAIN),
                    'admin.php?page=meest_setting',
                ),
                'default' => ''
            ],
        ];
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param array $package
     * @return void
     */
    public function calculate_shipping($package = [])
    {
        $this->add_rate([
            'label' => $this->title,
            'cost' => 0,
            'package' => $package,
        ]);
    }

    /**
     * @return string
     */
    private function get_description(): string
    {
        return __('Shipping with popular Ukrainian logistic company Meest Group', MEEST_PLUGIN_DOMAIN);
    }
}
