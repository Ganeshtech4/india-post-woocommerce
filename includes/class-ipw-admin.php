<?php
/**
 * Admin Portal & Dashboard for India Post WooCommerce
 * Faithful implementation of the Department of Posts Self-Service Portal layout.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('IPW_Admin')) {
class IPW_Admin {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;

        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Prevent 3rd-party plugin notices (HubSpot, MAS, update nags) from breaking portal layout
        add_action('in_admin_header', array($this, 'clean_admin_notices'), 100);

        // AJAX admin endpoints
        add_action('wp_ajax_ipw_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_ipw_admin_pincode_search', array($this, 'ajax_pincode_search'));
        add_action('wp_ajax_ipw_sync_all_tracking', array($this, 'ajax_sync_all_tracking'));
    }

    /**
     * Suppress 3rd party notices on India Post custom portal screens so layout is not disrupted.
     */
    public function clean_admin_notices() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'india-post') !== false) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('user_admin_notices');
        }
    }

    /**
     * Register Admin Menus with India Post branding.
     */
    public function register_admin_menus() {
        // India Post icon (SVG Data URI)
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>'
        );

        $hook = add_menu_page(
            __('India Post Portal', 'india-post-woocommerce'),
            __('India Post', 'india-post-woocommerce'),
            'manage_woocommerce',
            'india-post-dashboard',
            array($this, 'render_dashboard_page'),
            $icon_svg,
            56
        );

        add_submenu_page(
            'india-post-dashboard',
            __('India Post Dashboard', 'india-post-woocommerce'),
            __('Dashboard', 'india-post-woocommerce'),
            'manage_woocommerce',
            'india-post-dashboard',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'india-post-dashboard',
            __('Shipments & Tracking', 'india-post-woocommerce'),
            __('Shipments & Tracking', 'india-post-woocommerce'),
            'manage_woocommerce',
            'india-post-shipments',
            array($this, 'render_shipments_page')
        );

        add_submenu_page(
            'india-post-dashboard',
            __('API Settings & Config', 'india-post-woocommerce'),
            __('Settings & API', 'india-post-woocommerce'),
            'manage_woocommerce',
            'india-post-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue assets only on India Post admin pages or order edit screens.
     */
    public function enqueue_admin_assets($hook) {
        $is_plugin_screen = strpos($hook, 'india-post') !== false;
        $is_order_screen  = in_array($hook, array('post.php', 'post-new.php', 'woocommerce_page_wc-orders', 'edit.php')) || strpos($hook, 'wc-orders') !== false || strpos($hook, 'shop_order') !== false;

        if (!$is_plugin_screen && !$is_order_screen) {
            return;
        }

        $css_ver = file_exists(IPW_PLUGIN_PATH . 'assets/css/admin-portal.css') ? filemtime(IPW_PLUGIN_PATH . 'assets/css/admin-portal.css') : IPW_VERSION;
        $js_ver  = file_exists(IPW_PLUGIN_PATH . 'assets/js/admin-portal.js') ? filemtime(IPW_PLUGIN_PATH . 'assets/js/admin-portal.js') : IPW_VERSION;

        wp_enqueue_style('ipw-admin-portal', IPW_PLUGIN_URL . 'assets/css/admin-portal.css', array(), $css_ver);
        wp_enqueue_script('ipw-admin-portal', IPW_PLUGIN_URL . 'assets/js/admin-portal.js', array('jquery'), $js_ver, true);

        wp_localize_script('ipw-admin-portal', 'ipw_admin_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ipw_admin_nonce'),
            'i18n'     => array(
                'testing'        => __('Connecting to India Post API...', 'india-post-woocommerce'),
                'booking'        => __('Booking shipment with India Post...', 'india-post-woocommerce'),
                'syncing'        => __('Synchronizing tracking events...', 'india-post-woocommerce'),
                'confirm_cancel' => __('Are you sure you want to void this booking?', 'india-post-woocommerce'),
            ),
        ));
    }

    /**
     * Render the Department of Posts Self-Service Portal Dashboard.
     */
    public function render_dashboard_page() {
        global $wpdb;

        // Auto-heal: reset any shipments created today that erroneously became 'out_for_delivery' in sandbox simulation
        $table = $wpdb->prefix . 'ipw_shipments';
        $affected = $wpdb->get_results("SELECT id, order_id, barcode_no, created_at FROM $table WHERE delivery_status = 'out_for_delivery' AND created_at >= CURDATE()");
        if (!empty($affected)) {
            foreach ($affected as $aff) {
                $created_time = $aff->created_at ? strtotime($aff->created_at) : time();
                $init_events = array(
                    array(
                        'date'     => date('Y-m-d\TH:i:s\Z', $created_time),
                        'time'     => date('H:i:s', $created_time),
                        'office'   => get_option('ipw_sender_city', 'Bengaluru') . ' NSH',
                        'officeid' => '21260024',
                        'event'    => 'Item Booked',
                        'remarks'  => 'Consignment Booked & Awaiting Dispatch',
                        'rts'      => false,
                    ),
                );
                $wpdb->update($table, array(
                    'delivery_status' => 'booked',
                    'tracking_cache'  => wp_json_encode($init_events),
                    'last_tracked_at' => current_time('mysql'),
                ), array('id' => $aff->id));

                if ($aff->order_id) {
                    $ord = wc_get_order($aff->order_id);
                    if ($ord) {
                        $ord->update_meta_data('_ipw_delivery_status', 'booked');
                        $ord->update_meta_data('_ipw_tracking_cache', wp_json_encode($init_events));
                        $ord->save();
                    }
                }
            }
        }

        // Fetch counts
        $total_shipments = intval($wpdb->get_var("SELECT COUNT(*) FROM $table"));
        $in_transit = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE delivery_status IN ('in_transit', 'booked')"));
        $delivered = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE delivery_status = 'delivered'"));
        $out_for_delivery = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE delivery_status = 'out_for_delivery'"));

        // Fetch recent unbooked WooCommerce orders
        $unbooked_orders = $this->get_recent_unbooked_orders(5);

        // Fetch recent booked shipments
        $recent_shipments = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 5");

        include IPW_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }

    /**
     * Render Shipments & Tracking Management Table.
     */
    public function render_shipments_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'ipw_shipments';

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $filter_status = isset($_GET['delivery_status']) ? sanitize_text_field($_GET['delivery_status']) : '';

        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= $wpdb->prepare(" AND (barcode_no LIKE %s OR order_id = %d)", '%' . $wpdb->esc_like($search) . '%', intval($search));
        }
        if (!empty($filter_status)) {
            $where .= $wpdb->prepare(" AND delivery_status = %s", $filter_status);
        }

        $shipments = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC LIMIT 50");

        include IPW_PLUGIN_PATH . 'templates/admin-shipments.php';
    }

    /**
     * Render Plugin Settings & API Configuration.
     */
    public function render_settings_page() {
        if (isset($_POST['ipw_save_settings']) && check_admin_referer('ipw_settings_verify')) {
            $this->save_settings();
            echo '<div class="notice notice-success is-dismissible ipw-allowed-notice"><p>' . esc_html__('India Post configuration updated successfully.', 'india-post-woocommerce') . '</p></div>';
        }

        include IPW_PLUGIN_PATH . 'templates/admin-settings.php';
    }

    /**
     * Save settings form submissions.
     */
    private function save_settings() {
        $text_fields = array(
            'environment', 'sandbox_base_url', 'prod_base_url',
            'sandbox_username', 'sandbox_password', 'prod_username', 'prod_password',
            'customer_id', 'contract_id_sp', 'contract_id_bp', 'contract_id_24sp_doc',
            'contract_id_24spp_par', 'contract_id_48sp_doc', 'default_office_id',
            'default_service',
            'pickup_or_dropoff', 'awb_prefix', 'awb_counter_start', 'awb_counter_end',
            'awb_current_counter', 'awb_suffix', 'sender_name', 'sender_company',
            'sender_add_line_1', 'sender_add_line_2', 'sender_city', 'sender_state',
            'sender_pincode', 'sender_mobile_no', 'sender_emailid', 'default_hsn', 'default_weight', 'otp_mode',
            'enable_sandbox_simulation'
        );

        foreach ($text_fields as $field) {
            if (isset($_POST['ipw_' . $field])) {
                update_option('ipw_' . $field, sanitize_text_field($_POST['ipw_' . $field]));
            }
        }

        // Toggles / Checkboxes
        $checkboxes = array('show_weight_on_label');
        foreach ($checkboxes as $cb) {
            $val = isset($_POST['ipw_' . $cb]) ? 'yes' : 'no';
            update_option('ipw_' . $cb, $val);
        }

        // Clear cached token so new credentials take effect immediately
        delete_transient(IPW_API_Client::TOKEN_TRANSIENT);
    }

    /**
     * Helper to get unbooked WooCommerce orders.
     */
    private function get_recent_unbooked_orders($limit = 5) {
        $args = array(
            'limit'        => $limit,
            'status'       => array('processing', 'on-hold', 'completed'),
            'meta_query'   => array(
                array(
                    'key'     => '_ipw_barcode_no',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
        return wc_get_orders($args);
    }

    /**
     * AJAX Test Connection.
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('ipw_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'india-post-woocommerce')));
        }

        $api = new IPW_API_Client();
        $res = $api->test_connection();

        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * AJAX Pincode Search.
     */
    public function ajax_pincode_search() {
        check_ajax_referer('ipw_admin_nonce', 'nonce');

        $pincode = isset($_POST['pincode']) ? sanitize_text_field($_POST['pincode']) : '';
        if (strlen($pincode) !== 6) {
            wp_send_json_error(array('message' => __('Please enter a 6-digit PIN code.', 'india-post-woocommerce')));
        }

        $api = new IPW_API_Client();
        $res = $api->search_pincode($pincode);

        if (is_wp_error($res)) {
            wp_send_json_error(array('message' => $res->get_error_message()));
        }

        $notice = '';
        if (is_array($res) && !empty($res[0]['_notice'])) {
            $notice = $res[0]['_notice'];
        }

        wp_send_json_success(array('offices' => $res, 'notice' => $notice));
    }

    /**
     * AJAX Sync All In-Transit Tracking.
     */
    public function ajax_sync_all_tracking() {
        check_ajax_referer('ipw_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'india-post-woocommerce')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipw_shipments';
        $active_records = $wpdb->get_results("SELECT barcode_no, order_id FROM $table WHERE delivery_status NOT IN ('delivered', 'rts') LIMIT 100");

        if (empty($active_records)) {
            wp_send_json_success(array('message' => __('No active consignments pending tracking updates.', 'india-post-woocommerce'), 'updated' => 0));
        }

        $barcodes = wp_list_pluck($active_records, 'barcode_no');
        $api = new IPW_API_Client();
        $track_res = $api->track_bulk($barcodes);

        if (is_wp_error($track_res) || empty($track_res['data'])) {
            wp_send_json_error(array('message' => __('Failed to fetch tracking updates from India Post.', 'india-post-woocommerce')));
        }

        $updated_count = 0;
        foreach ($track_res['data'] as $art) {
            $awb = $art['booking_details']['article_number'];
            $events = isset($art['tracking_details']) ? $art['tracking_details'] : array();
            $del_status = isset($art['del_status']['del_status']) ? $art['del_status']['del_status'] : 'in_transit';

            $wpdb->update($table, array(
                'tracking_cache'  => wp_json_encode($events),
                'delivery_status' => $del_status,
                'last_tracked_at' => current_time('mysql'),
            ), array('barcode_no' => $awb));

            // Update order meta as well
            $rec = $wpdb->get_row($wpdb->prepare("SELECT order_id FROM $table WHERE barcode_no = %s", $awb));
            if ($rec && $rec->order_id) {
                $order = wc_get_order($rec->order_id);
                if ($order) {
                    $order->update_meta_data('_ipw_delivery_status', $del_status);
                    $order->update_meta_data('_ipw_tracking_cache', wp_json_encode($events));
                    $order->save();
                }
            }
            $updated_count++;
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Successfully synchronized %d consignments with India Post!', 'india-post-woocommerce'), $updated_count),
            'updated' => $updated_count,
        ));
    }
}
}
