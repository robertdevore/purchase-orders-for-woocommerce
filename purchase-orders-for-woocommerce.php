<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Purchase_Orders_For_WooCommerce
 *
 * @wordpress-plugin
 *
 * Plugin Name: Purchase Orders for WooCommerce®
 * Description: Adds a "Purchase Order" option to WooCommerce® checkout, allowing customers to provide a PO number.
 * Plugin URI:  https://github.com/robertdevore/purchase-orders-for-woocommerce/
 * Version:     1.0.1
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-purchase-orders
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/purchase-orders-for-woocommerce/
 */

defined( 'ABSPATH' ) || exit;

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/purchase-orders-for-woocommerce/',
    __FILE__,
    'purchase-orders-for-woocommerce'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

// Define the plugin version.
define( 'POWC_VERSION', '1.0.1' );

/**
 * Load plugin text domain for translations
 * 
 * @since 1.1.0
 * @return void
 */
function powc_load_textdomain() {
    load_plugin_textdomain( 
        'wc-purchase-orders', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'powc_load_textdomain' );

/**
 * Check if WooCommerce® is active and initialize the plugin.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_initialize() {
    if ( class_exists( 'WooCommerce' ) ) {
        add_filter( 'woocommerce_payment_gateways', 'wc_purchase_orders_add_gateway' );
        add_action( 'admin_menu', 'wc_purchase_orders_add_settings_page' );
        add_action( 'admin_enqueue_scripts', 'wc_purchase_orders_enqueue_scripts' );
    } else {
        add_action( 'admin_notices', 'wc_purchase_orders_missing_woocommerce_notice' );
    }
}
add_action( 'woocommerce_init', 'wc_purchase_orders_initialize' );

/**
 * Enqueue custom scripts and styles for the Purchase Orders settings page.
 *
 * @param string $hook The current admin page hook.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_enqueue_scripts( $hook ) {
    if ( 'woocommerce_page_wc-purchase-orders' !== $hook ) {
        return;
    }

    // Enqueue custom CSS for the plugin.
    wp_enqueue_style(
        'purchase-orders-css',
        plugins_url( 'assets/css/style.css', __FILE__ ),
        [],
        POWC_VERSION
    );
}

/**
 * Admin notice for missing WooCommerce® dependency.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_missing_woocommerce_notice() {
    echo '<div class="error"><p>' . esc_html__( 'Purchase Orders for WooCommerce® requires WooCommerce® to be active.', 'wc-purchase-orders' ) . '</p></div>';
}

/**
 * Adds a custom "Purchase Order" payment gateway to WooCommerce®.
 *
 * @param array $gateways Existing payment gateways.
 *
 * @since  1.0.0
 * @return array Updated payment gateways.
 */
function wc_purchase_orders_add_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Purchase_Order';
    return $gateways;
}

/**
 * Defines the custom "Purchase Order" payment gateway class.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_include_gateway_class() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_Gateway_Purchase_Order extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         * 
         * @since 1.0.0
         */
        public function __construct() {
            $this->id                 = 'purchase_order';
            $this->method_title       = esc_html__( 'Purchase Order', 'wc-purchase-orders' );
            $this->method_description = esc_html__( 'Allow customers to pay via Purchase Order.', 'wc-purchase-orders' );
            $this->has_fields         = true;

            // Load settings and initialize.
            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );

            // Admin settings save hook.
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

            // Display PO number in admin order details.
            add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_admin_order_meta' ], 10, 1 );
        }

        /**
         * Initialize the settings form fields.
         *
         * @since  1.0.0
         * @return void
         */
        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'   => esc_html__( 'Enable/Disable', 'wc-purchase-orders' ),
                    'type'    => 'checkbox',
                    'label'   => esc_html__( 'Enable Purchase Order Payment', 'wc-purchase-orders' ),
                    'default' => 'yes'
                ],
                'title' => [
                    'title'       => esc_html__( 'Title', 'wc-purchase-orders' ),
                    'type'        => 'text',
                    'description' => esc_html__( 'This controls the title displayed during checkout.', 'wc-purchase-orders' ),
                    'default'     => esc_html__( 'Purchase Order', 'wc-purchase-orders' ),
                    'desc_tip'    => true,
                ],
                'description' => [
                    'title'       => esc_html__( 'Description', 'wc-purchase-orders' ),
                    'type'        => 'textarea',
                    'description' => esc_html__( 'This controls the description displayed during checkout.', 'wc-purchase-orders' ),
                    'default'     => esc_html__( 'Place an order using a Purchase Order Number.', 'wc-purchase-orders' ),
                ],
            ];
        }

        /**
         * Output the payment fields on the checkout page.
         *
         * @since  1.0.0
         * @return void
         */
        public function payment_fields() {
            ?>
            <p><?php echo wp_kses_post( wpautop( $this->description ) ); ?></p>
            <fieldset>
                <p class="form-row form-row-wide">
                    <label for="po_number"><?php esc_html_e( 'Purchase Order Number', 'wc-purchase-orders' ); ?> <span class="required">*</span></label>
                    <input type="text" class="input-text" name="po_number" id="po_number" required />
                </p>
            </fieldset>
            <?php
        }

        /**
         * Validate the payment fields on the checkout page.
         *
         * @since  1.0.0
         * @return bool
         */
        public function validate_fields() {
            if ( empty( $_POST['po_number'] ) ) {
                wc_add_notice( __( 'Please enter a Purchase Order Number.', 'wc-purchase-orders' ), 'error' );
                return false;
            }
            return true;
        }

        /**
         * Process the order based on admin settings.
         *
         * @param int $order_id The ID of the order being processed.
         *
         * @since  1.0.0
         * @return array Result of the payment processing.
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            // Save the PO number.
            if ( ! empty( $_POST['po_number'] ) ) {
                $order->update_meta_data( '_po_number', sanitize_text_field( $_POST['po_number'] ) );
                $order->save();
            }

            // Get custom settings for the order status and stock reduction.
            $po_order_status = get_option( 'wc_po_order_status', 'on-hold' );
            $reduce_stock    = get_option( 'wc_po_order_reduce_stock', 'yes' );

            // Set order status.
            $order->update_status( $po_order_status, esc_html__( 'Purchase order received.', 'wc-purchase-orders' ) );

            // Optionally reduce stock levels.
            if ( 'yes' === $reduce_stock ) {
                wc_reduce_stock_levels( $order_id );
            }

            // Clear the cart.
            WC()->cart->empty_cart();

            // Return success result.
            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            ];
        }

        /**
         * Display the Purchase Order Number in the admin order details.
         *
         * @param WC_Order $order The order object.
         *
         * @since  1.0.0
         * @return void
         */
        public function display_admin_order_meta( $order ) {
            $po_number = $order->get_meta( '_po_number' );
            if ( $po_number ) {
                echo '<h2 style="color: #287aa3;font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left"><strong>' . esc_html__( 'Purchase Order Number', 'wc-purchase-orders' ) . ':</strong> ' . esc_html( $po_number ) . '</h2>';
            }
        }
    }
}
add_action( 'plugins_loaded', 'wc_purchase_orders_include_gateway_class', 11 );

/**
 * Add settings page to customize the purchase order options.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        esc_html__( 'Purchase Orders Settings', 'wc-purchase-orders' ),
        esc_html__( 'Purchase Orders', 'wc-purchase-orders' ),
        'manage_options',
        'wc-purchase-orders',
        'wc_purchase_orders_render_settings_page'
    );
}

/**
 * Render the settings page.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_render_settings_page() {
    if ( isset( $_POST['wc_purchase_orders_save_settings'] ) && check_admin_referer( 'wc_purchase_orders_save_settings_action' ) ) {
        // Sanitize and save settings.
        $status       = sanitize_text_field( $_POST['wc_po_order_status'] );
        $reduce_stock = sanitize_text_field( $_POST['wc_po_order_reduce_stock'] );

        update_option( 'wc_po_order_status', $status );
        update_option( 'wc_po_order_reduce_stock', $reduce_stock );
        echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'wc-purchase-orders' ) . '</p></div>';
    }

    // Fetch saved options with defaults.
    $status       = get_option( 'wc_po_order_status', 'on-hold' );
    $reduce_stock = get_option( 'wc_po_order_reduce_stock', 'yes' );
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e( 'Purchase Orders Settings', 'wc-purchase-orders' ); ?>
            <?php
                echo sprintf(
                    '<a id="wcpo-support-btn" href="%1$s" target="_blank" class="button button-alt" style="margin-left: 10px;">
                        <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> %2$s
                    </a>
                    <a id="wcpo-docs-btn" href="%3$s" target="_blank" class="button button-alt" style="margin-left: 5px;">
                        <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> %4$s
                    </a>',
                    esc_url( 'https://robertdevore.com/contact/' ),
                    esc_html__( 'Support', 'wc-purchase-orders' ),
                    esc_url( 'https://robertdevore.com/articles/purchase-orders-for-woocommerce/' ),
                    esc_html__( 'Documentation', 'wc-purchase-orders' )
                );
            ?>
        </h1>
        <hr />
        <form method="post" action="">
            <?php wp_nonce_field( 'wc_purchase_orders_save_settings_action' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wc_po_order_status"><?php esc_html_e( 'Order Status', 'wc-purchase-orders' ); ?></label>
                    </th>
                    <td>
                        <select name="wc_po_order_status" id="wc_po_order_status">
                            <option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'wc-purchase-orders' ); ?></option>
                            <option value="processing" <?php selected( $status, 'processing' ); ?>><?php esc_html_e( 'Processing', 'wc-purchase-orders' ); ?></option>
                            <option value="on-hold" <?php selected( $status, 'on-hold' ); ?>><?php esc_html_e( 'On Hold', 'wc-purchase-orders' ); ?></option>
                            <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending Payment', 'wc-purchase-orders' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wc_po_order_reduce_stock"><?php esc_html_e( 'Reduce Stock', 'wc-purchase-orders' ); ?></label>
                    </th>
                    <td>
                        <select name="wc_po_order_reduce_stock" id="wc_po_order_reduce_stock">
                            <option value="yes" <?php selected( $reduce_stock, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wc-purchase-orders' ); ?></option>
                            <option value="no" <?php selected( $reduce_stock, 'no' ); ?>><?php esc_html_e( 'No', 'wc-purchase-orders' ); ?></option>
                        </td>
                    </tr>
            </table>
            <?php submit_button( esc_html__( 'Save Settings', 'wc-purchase-orders' ), 'primary', 'wc_purchase_orders_save_settings' ); ?>
        </form>
        <style type="text/css">
            #wcpo-docs-btn,
            #wcpo-support-btn {
                float: right;
            }
        </style>
    </div>
    <?php
}

/**
 * Display the Purchase Order Number on the order thank you page and order details page.
 *
 * @param int $order_id The order ID.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_display_order_meta( $order_id ) {
    // Ensure we have a valid order ID.
    if ( ! $order_id ) {
        return;
    }

    // Get the order object.
    $order = wc_get_order( $order_id );

    // Check if we have a valid order object.
    if ( ! $order ) {
        return;
    }

    // Get the Purchase Order Number.
    $po_number = $order->get_meta( '_po_number' );

    // Display the PO Number if it exists.
    if ( $po_number ) {
        echo '<h2 style="color: #287aa3;font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left"><strong>' . esc_html__( 'Purchase Order Number', 'wc-purchase-orders' ) . ':</strong> ' . esc_html( $po_number ) . '</h2>';
    }
}
add_action( 'woocommerce_thankyou_purchase_order', 'wc_purchase_orders_display_order_meta', 20 );
add_action( 'woocommerce_view_order', 'wc_purchase_orders_display_order_meta', 20 );

/**
 * Add the Purchase Order Number to the customer and admin emails.
 *
 * @param WC_Order $order         The order object.
 * @param bool     $sent_to_admin If the email is sent to admin.
 * @param bool     $plain_text    If the email is plain text.
 * @param WC_Email $email         The email object.
 *
 * @since  1.0.0
 * @return void
 */
function wc_purchase_orders_display_order_meta_in_email( $order, $sent_to_admin, $plain_text, $email ) {
    if ( ! $sent_to_admin ) {
        // For customer emails.
        $po_number = $order->get_meta( '_po_number' );
        if ( $po_number ) {
            if ( $plain_text ) {
                echo esc_html__( 'Purchase Order Number:', 'wc-purchase-orders' ) . ' ' . esc_html( $po_number ) . "\n";
            } else {
                echo '<h2 style="color: #287aa3;font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left"><strong>' . esc_html__( 'Purchase Order Number', 'wc-purchase-orders' ) . ':</strong> ' . esc_html( $po_number ) . '</h2>';
            }
        }
    } else {
        // For admin emails.
        $po_number = $order->get_meta( '_po_number' );
        if ( $po_number ) {
            if ( $plain_text ) {
                echo esc_html__( 'Purchase Order Number:', 'wc-purchase-orders' ) . ' ' . esc_html( $po_number ) . "\n";
            } else {
                echo '<h2 style="color: #287aa3;font-family: &quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size: 18px;font-weight: bold;line-height: 130%;margin: 0 0 18px;text-align: left"><strong>' . esc_html__( 'Purchase Order Number', 'wc-purchase-orders' ) . ':</strong> ' . esc_html( $po_number ) . '</h2>';
            }
        }
    }
}
add_action( 'woocommerce_email_after_order_table', 'wc_purchase_orders_display_order_meta_in_email', 20, 4 );
