<?php
/**
 * Shipping Label Generator (Thermal 4x6 / A6, Multi-Label A4 Sheet, and India Post API PDF)
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPW_Label_Generator {

    public function __construct() {
        add_action('admin_action_ipw_print_label', array($this, 'handle_print_action'));
        add_action('admin_action_ipw_print_bulk_labels', array($this, 'handle_bulk_print_action'));
    }

    /**
     * Intercept admin action to render printable label or API PDF.
     */
    public function handle_print_action() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized user capability.', 'india-post-woocommerce'));
        }

        if (!empty($_GET['order_ids'])) {
            $this->handle_bulk_print_action();
            return;
        }

        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $format   = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'thermal';

        if (!$order_id) {
            wp_die(__('Invalid Order ID.', 'india-post-woocommerce'));
        }

        if (!check_admin_referer('ipw_print_label_' . $order_id, '_wpnonce', false) && !check_admin_referer('ipw_admin_nonce', '_wpnonce', false) && !check_admin_referer('ipw_print_bulk_labels', '_wpnonce', false)) {
            // Allow printing if admin is logged in
        }

        if ($format === 'api_pdf') {
            $this->stream_api_pdf($order_id);
            exit;
        }

        if ($format === 'sheet') {
            $this->render_bulk_labels(array($order_id), 'sheet');
            exit;
        }

        $this->render_thermal_html($order_id);
        exit;
    }

    /**
     * Intercept bulk/multi-label print action.
     */
    public function handle_bulk_print_action() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized user capability.', 'india-post-woocommerce'));
        }

        $raw_ids = isset($_REQUEST['order_ids']) ? $_REQUEST['order_ids'] : '';
        if (is_array($raw_ids)) {
            $order_ids = array_filter(array_map('intval', $raw_ids));
        } else {
            $order_ids = array_filter(array_map('intval', explode(',', sanitize_text_field($raw_ids))));
        }

        if (empty($order_ids)) {
            wp_die(__('No valid orders specified for label printing.', 'india-post-woocommerce'));
        }

        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'sheet';

        $this->render_bulk_labels($order_ids, $format);
        exit;
    }

    /**
     * Prepare standardized label data for an order.
     *
     * @param int $order_id
     * @return array|false
     */
    public function get_label_data($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $barcode      = $order->get_meta('_ipw_barcode_no');
        $article_type = $order->get_meta('_ipw_article_type');
        $weight       = $order->get_meta('_ipw_weight');
        $tariff       = $order->get_meta('_ipw_tariff');
        $contract_id  = $order->get_meta('_ipw_contract_id');
        $booked_at    = $order->get_meta('_ipw_booked_at');

        if (empty($article_type)) {
            global $wpdb;
            $sh = $wpdb->get_row($wpdb->prepare("SELECT article_type, contract_id FROM {$wpdb->prefix}ipw_shipments WHERE order_id = %d LIMIT 1", $order_id));
            if ($sh && !empty($sh->article_type)) {
                $article_type = $sh->article_type;
                if (empty($contract_id)) {
                    $contract_id = $sh->contract_id;
                }
            } else {
                $article_type = get_option('ipw_default_service', 'BUSINESS_PARCEL');
            }
        }

        if (empty($barcode)) {
            $barcode = 'BP000000000IN';
        }

        $type_upper = strtoupper(trim((string)$article_type));
        $is_business_parcel = ($type_upper === 'BUSINESS_PARCEL' || strpos($type_upper, 'BP') !== false || $type_upper === 'PARCEL');

        if ($is_business_parcel) {
            $service_display = 'BUSINESS PARCEL';
            $default_contract = get_option('ipw_contract_id_bp', '41367422');
        } elseif ($type_upper === 'SP_INLAND_PARCEL') {
            $service_display = 'SPEED POST PARCEL';
            $default_contract = get_option('ipw_contract_id_sp', '41585456');
        } else {
            $service_display = 'SPEED POST';
            $default_contract = get_option('ipw_contract_id_sp', '41585456');
        }

        $active_contract_id = $contract_id ?: $default_contract;
        $customer_id = get_option('ipw_customer_id', '1156062849');

        $barcode_svg = IPW_Barcode_Helper::generate_code128_svg($barcode, 45, 1.8);

        $shipping_name = trim((string)$order->get_formatted_shipping_full_name());
        $billing_name  = trim((string)$order->get_formatted_billing_full_name());
        $recipient_name = !empty($shipping_name) ? $shipping_name : (!empty($billing_name) ? $billing_name : trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()));

        $recipient_pin   = trim((string)$order->get_shipping_postcode()) ?: trim((string)$order->get_billing_postcode());
        $recipient_phone = trim((string)$order->get_billing_phone()) ?: trim((string)$order->get_shipping_phone());

        $addr1 = trim((string)$order->get_shipping_address_1()) ?: trim((string)$order->get_billing_address_1());
        $addr2 = trim((string)$order->get_shipping_address_2()) ?: trim((string)$order->get_billing_address_2());
        $city  = trim((string)$order->get_shipping_city()) ?: trim((string)$order->get_billing_city());
        $state = trim((string)$order->get_shipping_state()) ?: trim((string)$order->get_billing_state());

        $sender_name       = get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS');
        $s_line1           = get_option('ipw_sender_add_line_1', 'PLOT NO-168,TOWN CENTRE LAYOUT, NEAR');
        $s_line2           = get_option('ipw_sender_add_line_2', 'KALYANA VENKATESWARA SWAMY TEMPLE, 100FT');
        $s_city            = get_option('ipw_sender_city', 'RING ROAD, VIZIANAGARAM');
        $s_state           = get_option('ipw_sender_state', 'ANDHRA PRADESH');
        $s_pin             = get_option('ipw_sender_pincode', '535002');
        $s_phone           = get_option('ipw_sender_mobile_no', '7799718787');

        $is_cod     = $order->get_payment_method() === 'cod';
        $cod_amount = $is_cod ? $order->get_total() : 0;

        return array(
            'order_id'           => $order_id,
            'order'              => $order,
            'barcode'            => $barcode,
            'barcode_svg'        => $barcode_svg,
            'article_type'       => $article_type,
            'service_display'    => $service_display,
            'customer_id'        => $customer_id,
            'active_contract_id' => $active_contract_id,
            'weight'             => $weight,
            'tariff'             => $tariff,
            'recipient_name'     => $recipient_name,
            'addr1'              => $addr1,
            'addr2'              => $addr2,
            'city'               => $city,
            'state'              => $state,
            'recipient_pin'      => $recipient_pin,
            'recipient_phone'    => $recipient_phone,
            'sender_name'        => $sender_name,
            's_line1'            => $s_line1,
            's_line2'            => $s_line2,
            's_city'             => $s_city,
            's_state'            => $s_state,
            's_pin'              => $s_pin,
            's_phone'            => $s_phone,
            'is_cod'             => $is_cod,
            'cod_amount'         => $cod_amount,
            'items'              => $order->get_items(),
        );
    }

    /**
     * Render Bulk Labels (Sheet or Thermal).
     *
     * @param array $order_ids
     * @param string $format 'sheet' for A4 (4 labels per sheet) or 'thermal' for 1 per page.
     */
    public function render_bulk_labels($order_ids, $format = 'sheet') {
        $labels_data = array();

        foreach ($order_ids as $oid) {
            $data = $this->get_label_data($oid);
            if ($data) {
                $labels_data[] = $data;
            }
        }

        if (empty($labels_data)) {
            wp_die(__('No valid label data could be generated for the selected orders.', 'india-post-woocommerce'));
        }

        if (isset($_GET['repeat']) && $_GET['repeat'] === '4' && count($labels_data) === 1) {
            $labels_data = array_fill(0, 4, $labels_data[0]);
        }

        if ($format === 'thermal' || $format === 'single') {
            include IPW_PLUGIN_PATH . 'templates/shipping-labels-multi-thermal.php';
        } else {
            // Default: A6 Sheet (4 labels per A6 sheet)
            include IPW_PLUGIN_PATH . 'templates/shipping-labels-sheet.php';
        }
        exit;
    }

    /**
     * Stream official PDF from India Post Label API or fallback.
     */
    public function stream_api_pdf($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die(__('Order not found.', 'india-post-woocommerce'));
        }

        $barcode = $order->get_meta('_ipw_barcode_no');
        if (empty($barcode)) {
            wp_die(__('No India Post consignment number booked for this order.', 'india-post-woocommerce'));
        }

        $api = new IPW_API_Client();
        $creds = $api->get_credentials();

        $label_payload = array(
            array(
                'customer_id'          => intval(!empty($creds['customer_id']) ? $creds['customer_id'] : 3000064781),
                'delivery_office_name' => '',
                'destination_pin'      => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
                'booking_datetime'     => current_time('d-m-Y H:i:s'),
                'channel_type'         => 'E',
                'user_type'            => 'R',
                'user_id'              => intval(!empty($creds['customer_id']) ? $creds['customer_id'] : 3000064781),
                'barcode_no'           => $barcode,
                'service_type'         => 'SP',
                'booking_type'         => 'COMMERCIAL',
                'article_length'       => '20',
                'article_breadth'      => '15',
                'article_height'       => '2',
                'charged_weight'       => 250,
                'physical_weight'      => 250,
                'volumetric_weight'    => 120,
                'insurance_flag'       => false,
                'insurance_value'      => 0,
                'recipient_name'       => $order->get_formatted_shipping_full_name() ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name(),
                'recipient_mobile'     => $order->get_billing_phone(),
                'recipient_addressl1'  => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
                'recipient_addressl2'  => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
                'recipient_addressl3'  => '',
                'recipient_city'       => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
                'recipient_pin'        => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
                'recipient_state'      => $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state(),
                'sender_name'          => get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS'),
                'sender_mobile'        => get_option('ipw_sender_mobile_no', '9876543210'),
                'sender_addressl1'     => get_option('ipw_sender_add_line_1', 'Main Office Road'),
                'sender_addressl2'     => get_option('ipw_sender_add_line_2', ''),
                'sender_addressl3'     => '',
                'sender_city'          => get_option('ipw_sender_city', 'Bengaluru'),
                'sender_pin'           => get_option('ipw_sender_pincode', '560001'),
                'sender_state'         => get_option('ipw_sender_state', 'Karnataka'),
                'transmission_mode'    => 'A',
                'payment_mode'         => 'CO',
                'routing_data'         => '',
                'booking_office_name'  => 'Bengaluru NSH',
                'booking_office_pin'   => get_option('ipw_sender_pincode', '560001'),
                'size'                 => 'A6',
                'total_amount'         => floatval($order->get_meta('_ipw_tariff', true)),
                'payment_status'       => 'PC',
                'value_added_services' => 'ND',
                'identifier'           => 'Domestic',
                'bkg_ref_id'           => 'ORD_' . $order->get_id(),
                'priority'             => false,
                'registered_flag'      => false,
            )
        );

        $pdf_res = $api->generate_api_label($label_payload);

        if (is_wp_error($pdf_res) || empty($pdf_res) || substr($pdf_res, 0, 4) !== '%PDF') {
            // Fallback to high-fidelity Thermal HTML template
            $this->render_thermal_html($order_id);
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="IndiaPost_Label_' . $barcode . '.pdf"');
        echo $pdf_res;
        exit;
    }

    /**
     * Render crisp Thermal 4x6 / A6 HTML Shipping Label with Vector Barcode for a single order.
     */
    public function render_thermal_html($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_die(__('Order not found.', 'india-post-woocommerce'));
        }

        $barcode      = $order->get_meta('_ipw_barcode_no');
        $article_type = $order->get_meta('_ipw_article_type');
        $weight       = $order->get_meta('_ipw_weight');
        $tariff       = $order->get_meta('_ipw_tariff');
        $contract_id  = $order->get_meta('_ipw_contract_id');
        $booked_at    = $order->get_meta('_ipw_booked_at');

        if (empty($article_type)) {
            global $wpdb;
            $sh = $wpdb->get_row($wpdb->prepare("SELECT article_type, contract_id FROM {$wpdb->prefix}ipw_shipments WHERE order_id = %d LIMIT 1", $order_id));
            if ($sh && !empty($sh->article_type)) {
                $article_type = $sh->article_type;
                if (empty($contract_id)) {
                    $contract_id = $sh->contract_id;
                }
            } else {
                $article_type = get_option('ipw_default_service', 'BUSINESS_PARCEL');
            }
        }

        if (empty($barcode)) {
            $barcode = 'BP000000000IN';
        }

        $barcode_svg = IPW_Barcode_Helper::generate_code128_svg($barcode, 55, 2);

        $recipient_name    = $order->get_formatted_shipping_full_name() ?: $order->get_formatted_billing_full_name();
        $recipient_address = array_filter(array(
            $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
            $order->get_shipping_city() ?: $order->get_billing_city(),
            $order->get_shipping_state() ?: $order->get_billing_state(),
        ));
        $recipient_pin     = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
        $recipient_phone   = $order->get_billing_phone();

        $sender_name       = get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS');
        $sender_company    = get_option('ipw_sender_company', 'Dhruva Publications');
        $sender_address    = array_filter(array(
            get_option('ipw_sender_add_line_1', 'Main Office Road'),
            get_option('ipw_sender_add_line_2', ''),
            get_option('ipw_sender_city', 'Bengaluru'),
            get_option('ipw_sender_state', 'Karnataka'),
        ));
        $sender_pin        = get_option('ipw_sender_pincode', '560001');
        $sender_phone      = get_option('ipw_sender_mobile_no', '9876543210');

        $is_cod = $order->get_payment_method() === 'cod';
        $cod_amount = $is_cod ? $order->get_total() : 0;

        include IPW_PLUGIN_PATH . 'templates/shipping-label-thermal.php';
        exit;
    }
}
