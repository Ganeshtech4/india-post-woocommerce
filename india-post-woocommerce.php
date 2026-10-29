<?php
/**
 * Plugin Name: India Post Shipping & Tracking for WooCommerce
 * Plugin URI: https://dhuruvapublications.com
 * Description: Lightweight, enterprise-grade India Post Business API integration for WooCommerce. Features shipment booking, consignment barcode allocation (UPU S10), thermal/A6 shipping label generation, OTP-verified customer mobile tracking, and India Post Self-Service Portal styled admin dashboard.
 * Version: 1.3.1
 * Author: Dhruva Publications / Antigravity
 * Author URI: https://dhuruvapublications.com
 * Text Domain: india-post-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('IPW_VERSION')) {
    define('IPW_VERSION', '1.3.1');
}
if (!defined('IPW_PLUGIN_FILE')) {
    define('IPW_PLUGIN_FILE', __FILE__);
}
if (!defined('IPW_PLUGIN_PATH')) {
    define('IPW_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('IPW_PLUGIN_URL')) {
    define('IPW_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('IPW_PLUGIN_BASENAME')) {
    define('IPW_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Declare compatibility with WooCommerce HPOS (High-Performance Order Storage)
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Main Plugin Class
 */
if (!class_exists('India_Post_WooCommerce')) {
final class India_Post_WooCommerce {

    /**
     * Single instance of the class.
     * @var India_Post_WooCommerce
     */
    private static $instance = null;

    /**
     * API Client instance.
     * @var IPW_API_Client
     */
    public $api = null;

    /**
     * Admin Handler instance.
     * @var IPW_Admin
     */
    public $admin = null;

    /**
     * Order Meta Handler instance.
     * @var IPW_Order_Meta
     */
    public $order_meta = null;

    /**
     * Tracking Shortcode Handler instance.
     * @var IPW_Tracking_Shortcode
     */
    public $tracking = null;

    /**
     * Barcode Helper instance.
     * @var IPW_Barcode_Helper
     */
    public $barcode = null;

    /**
     * Label Generator instance.
     * @var IPW_Label_Generator
     */
    public $label = null;

    /**
     * Webhook Handler instance.
     * @var IPW_Webhook
     */
    public $webhook = null;

    /**
     * Main Instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-activator.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-barcode-helper.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-api-client.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-otp-service.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-label-generator.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-admin.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-order-meta.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-tracking-shortcode.php';
        require_once IPW_PLUGIN_PATH . 'includes/class-ipw-webhook.php';
    }

    /**
     * Initialize hooks and sub-classes.
     */
    private function init_hooks() {
        // Activation & Deactivation hooks
        register_activation_hook(IPW_PLUGIN_FILE, array('IPW_Activator', 'activate'));
        register_deactivation_hook(IPW_PLUGIN_FILE, array('IPW_Activator', 'deactivate'));

        // Initialize components
        $this->barcode    = new IPW_Barcode_Helper();
        $this->api        = new IPW_API_Client();
        $this->label      = new IPW_Label_Generator();
        $this->admin      = new IPW_Admin($this);
        $this->order_meta = new IPW_Order_Meta($this);
        $this->tracking   = new IPW_Tracking_Shortcode($this);
        $this->webhook    = new IPW_Webhook($this);

        // Load plugin textdomain
        add_action('init', array($this, 'load_textdomain'));

        // Check WooCommerce dependency
        add_action('admin_notices', array($this, 'check_woocommerce_active'));
    }

    /**
     * Load text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain('india-post-woocommerce', false, dirname(IPW_PLUGIN_BASENAME) . '/languages/');
    }

    /**
     * Check if WooCommerce is installed and active.
     */
    public function check_woocommerce_active() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('India Post Shipping & Tracking requires WooCommerce to be installed and active.', 'india-post-woocommerce') .
                '</p></div>';
        }
    }
}
}

/**
 * Global helper function to access plugin instance.
 *
 * @return India_Post_WooCommerce
 */
if (!function_exists('ipw')) {
function ipw() {
    return India_Post_WooCommerce::instance();
}
}

// Kick off the plugin
ipw();
