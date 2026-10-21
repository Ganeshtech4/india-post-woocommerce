<?php
/**
 * Customer Tracking Page Shortcode & AJAX Handlers
 * [india_post_tracking]
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPW_Tracking_Shortcode {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;

        add_shortcode('india_post_tracking', array($this, 'render_shortcode'));

        // Public AJAX hooks for Direct Customer Tracking
        add_action('wp_ajax_ipw_public_track_by_phone', array($this, 'ajax_track_by_phone'));
        add_action('wp_ajax_nopriv_ipw_public_track_by_phone', array($this, 'ajax_track_by_phone'));

        // Legacy OTP hooks (preserved for backwards compatibility if needed)
        add_action('wp_ajax_ipw_public_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_nopriv_ipw_public_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_ipw_public_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_nopriv_ipw_public_verify_otp', array($this, 'ajax_verify_otp'));

        add_action('wp_ajax_ipw_public_direct_track', array($this, 'ajax_direct_track'));
        add_action('wp_ajax_nopriv_ipw_public_direct_track', array($this, 'ajax_direct_track'));
    }

    /**
     * Render the tracking shortcode.
     */
    public function render_shortcode($atts) {
        // Enqueue frontend scripts & styles with automatic cache busting
        $css_ver = file_exists(IPW_PLUGIN_PATH . 'assets/css/customer-tracking.css') ? filemtime(IPW_PLUGIN_PATH . 'assets/css/customer-tracking.css') : IPW_VERSION;
        $js_ver  = file_exists(IPW_PLUGIN_PATH . 'assets/js/customer-tracking.js') ? filemtime(IPW_PLUGIN_PATH . 'assets/js/customer-tracking.js') : IPW_VERSION;

        wp_enqueue_style('ipw-customer-tracking', IPW_PLUGIN_URL . 'assets/css/customer-tracking.css', array(), $css_ver);
        wp_enqueue_script('ipw-customer-tracking', IPW_PLUGIN_URL . 'assets/js/customer-tracking.js', array('jquery'), $js_ver, true);

        wp_localize_script('ipw-customer-tracking', 'ipw_tracking_data', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('ipw_public_tracking_nonce'),
            'i18n'       => array(
                'sending_otp'      => __('Sending OTP...', 'india-post-woocommerce'),
                'verifying_otp'    => __('Verifying Code...', 'india-post-woocommerce'),
                'tracking'         => __('Fetching Live Tracking Events...', 'india-post-woocommerce'),
                'enter_valid_phone'=> __('Please enter a valid 10-digit mobile number.', 'india-post-woocommerce'),
                'enter_otp'        => __('Please enter the 6-digit OTP received on your mobile.', 'india-post-woocommerce'),
                'resend'           => __('Resend OTP', 'india-post-woocommerce'),
                'resend_in'        => __('Resend in %ds', 'india-post-woocommerce'),
                'no_shipments'     => __('No shipments found associated with this mobile number.', 'india-post-woocommerce'),
            ),
        ));

        ob_start();
        include IPW_PLUGIN_PATH . 'templates/customer-tracking-form.php';
        return ob_get_clean();
    }

    /**
     * Direct AJAX Tracking by Mobile Number (NO OTP required).
     */
    public function ajax_track_by_phone() {
        check_ajax_referer('ipw_public_tracking_nonce', 'nonce');

        $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
        $clean_mobile = IPW_OTP_Service::sanitize_mobile($mobile);

        if (empty($clean_mobile) || strlen($clean_mobile) !== 10) {
            wp_send_json_error(array('message' => __('Please enter a valid 10-digit mobile number.', 'india-post-woocommerce')));
        }

        // Retrieve shipments linked to this mobile number directly
        $shipments = IPW_OTP_Service::get_shipments_by_mobile($clean_mobile);

        if (empty($shipments)) {
            wp_send_json_error(array('message' => sprintf(__('No India Post shipments found associated with mobile number +91 %s.', 'india-post-woocommerce'), esc_html($clean_mobile))));
        }

        wp_send_json_success(array(
            'message'    => __('Shipments retrieved successfully.', 'india-post-woocommerce'),
            'mobile_no'  => $clean_mobile,
            'shipments'  => $shipments,
            'count'      => count($shipments),
        ));
    }

    /**
     * AJAX Send OTP Handler.
     */
    public function ajax_send_otp() {
        check_ajax_referer('ipw_public_tracking_nonce', 'nonce');

        $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
        $result = IPW_OTP_Service::request_otp($mobile);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['message']));
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX Verify OTP and fetch customer shipments.
     */
    public function ajax_verify_otp() {
        check_ajax_referer('ipw_public_tracking_nonce', 'nonce');

        $token     = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        $otp_code  = isset($_POST['otp_code']) ? sanitize_text_field($_POST['otp_code']) : '';

        $verify_result = IPW_OTP_Service::verify_otp($token, $otp_code);

        if (!$verify_result['success']) {
            wp_send_json_error(array('message' => $verify_result['message']));
        }

        // Retrieve shipments linked to this mobile number
        $shipments = IPW_OTP_Service::get_shipments_by_mobile($verify_result['mobile_no']);

        wp_send_json_success(array(
            'message'    => $verify_result['message'],
            'mobile_no'  => $verify_result['mobile_no'],
            'shipments'  => $shipments,
            'count'      => count($shipments),
        ));
    }

    /**
     * Direct Consignment Tracking by AWB Barcode.
     */
    public function ajax_direct_track() {
        check_ajax_referer('ipw_public_tracking_nonce', 'nonce');

        $awb = isset($_POST['awb']) ? strtoupper(trim(sanitize_text_field($_POST['awb']))) : '';

        if (empty($awb) || strlen($awb) !== 13) {
            wp_send_json_error(array('message' => __('Please enter a valid 13-character India Post Consignment Number (e.g. ET214330013IN).', 'india-post-woocommerce')));
        }

        $api = new IPW_API_Client();
        $res = $api->track_bulk(array($awb));

        if (is_wp_error($res) || empty($res['data'])) {
            wp_send_json_error(array('message' => __('Unable to find tracking records for this consignment number.', 'india-post-woocommerce')));
        }

        $item = $res['data'][0];
        wp_send_json_success(array(
            'consignment'      => $awb,
            'booking_details'  => isset($item['booking_details']) ? $item['booking_details'] : array(),
            'tracking_details' => isset($item['tracking_details']) ? $item['tracking_details'] : array(),
            'del_status'       => isset($item['del_status']['del_status']) ? $item['del_status']['del_status'] : 'in_transit',
        ));
    }
}
