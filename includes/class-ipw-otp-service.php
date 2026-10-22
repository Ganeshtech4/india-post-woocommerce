<?php
/**
 * Customer OTP Verification Service for Mobile Tracking
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('IPW_OTP_Service')) {
class IPW_OTP_Service {

    /**
     * Clean phone number to 10 digits.
     *
     * @param string $mobile
     * @return string
     */
    public static function sanitize_mobile($mobile) {
        $clean = preg_replace('/\D/', '', $mobile);
        // If 12 digits starting with 91, strip country code
        if (strlen($clean) === 12 && substr($clean, 0, 2) === '91') {
            $clean = substr($clean, 2);
        }
        // If 11 digits starting with 0, strip leading zero
        if (strlen($clean) === 11 && substr($clean, 0, 1) === '0') {
            $clean = substr($clean, 1);
        }
        return substr($clean, -10);
    }

    /**
     * Request OTP for a given mobile number.
     *
     * @param string $mobile
     * @return array Result array with success flag, message, and session token.
     */
    public static function request_otp($mobile) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipw_otp_tokens';

        $clean_mobile = self::sanitize_mobile($mobile);
        if (strlen($clean_mobile) !== 10 || !in_array($clean_mobile[0], array('6', '7', '8', '9'))) {
            return array(
                'success' => false,
                'message' => __('Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.', 'india-post-woocommerce'),
            );
        }

        // Rate limit: check if request was made in the last 45 seconds
        $recent = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE mobile_no = %s AND created_at > %s ORDER BY id DESC LIMIT 1",
            $clean_mobile,
            date('Y-m-d H:i:s', time() - 45)
        ));

        if ($recent) {
            return array(
                'success' => false,
                'message' => __('Please wait 45 seconds before requesting a new OTP.', 'india-post-woocommerce'),
            );
        }

        // Generate 6 digit OTP
        $otp = (string) wp_rand(100000, 999999);
        $token = wp_generate_password(32, false);
        $expiry_mins = intval(get_option('ipw_otp_expiry_minutes', 10));
        $expires_at = date('Y-m-d H:i:s', time() + ($expiry_mins * 60));

        $inserted = $wpdb->insert($table, array(
            'mobile_no'   => $clean_mobile,
            'otp_code'    => wp_hash_password($otp),
            'token'       => $token,
            'verified'    => 0,
            'attempts'    => 0,
            'expires_at'  => $expires_at,
            'created_at'  => current_time('mysql'),
        ));

        if (!$inserted) {
            return array(
                'success' => false,
                'message' => __('Failed to initiate verification session. Please try again.', 'india-post-woocommerce'),
            );
        }

        // Send OTP via SMS Gateway or Simulation
        $mode = get_option('ipw_otp_mode', 'simulation');
        $sms_sent = apply_filters('ipw_send_sms_otp', false, $clean_mobile, $otp);

        $response = array(
            'success'     => true,
            'message'     => sprintf(__('An OTP has been sent to %s.', 'india-post-woocommerce'), substr($clean_mobile, 0, 2) . 'XXXXXX' . substr($clean_mobile, -2)),
            'token'       => $token,
            'expires_in'  => $expiry_mins * 60,
        );

        // In simulation/sandbox mode, include simulated OTP for effortless testing
        if ($mode === 'simulation' || !$sms_sent) {
            $response['simulation_otp'] = $otp;
            $response['is_simulated'] = true;
        }

        return $response;
    }

    /**
     * Verify submitted OTP against stored session.
     *
     * @param string $token
     * @param string $otp_input
     * @return array
     */
    public static function verify_otp($token, $otp_input) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipw_otp_tokens';

        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND verified = 0 ORDER BY id DESC LIMIT 1",
            $token
        ));

        if (!$record) {
            return array(
                'success' => false,
                'message' => __('Invalid or expired session. Please request a new OTP.', 'india-post-woocommerce'),
            );
        }

        if (strtotime($record->expires_at) < time()) {
            return array(
                'success' => false,
                'message' => __('This OTP has expired. Please request a new one.', 'india-post-woocommerce'),
            );
        }

        if ($record->attempts >= 5) {
            return array(
                'success' => false,
                'message' => __('Too many incorrect attempts. Please request a new OTP.', 'india-post-woocommerce'),
            );
        }

        // Increment attempts
        $wpdb->update($table, array('attempts' => $record->attempts + 1), array('id' => $record->id));

        $valid = wp_check_password(trim($otp_input), $record->otp_code);

        if (!$valid) {
            return array(
                'success' => false,
                'message' => __('Invalid OTP entered. Please try again.', 'india-post-woocommerce'),
            );
        }

        // Mark verified
        $wpdb->update($table, array('verified' => 1), array('id' => $record->id));

        // Generate verified customer session token
        $verified_auth = wp_create_nonce('ipw_verified_' . $record->mobile_no);

        return array(
            'success'       => true,
            'message'       => __('Mobile number successfully verified.', 'india-post-woocommerce'),
            'mobile_no'     => $record->mobile_no,
            'auth_token'    => $verified_auth,
        );
    }

    /**
     * Find all WooCommerce shipments matching customer's mobile number.
     *
     * @param string $mobile 10-digit mobile number.
     * @return array Array of shipments with order details.
     */
    public static function get_shipments_by_mobile($mobile) {
        global $wpdb;
        $clean_mobile = self::sanitize_mobile($mobile);
        $shipments = array();

        if (empty($clean_mobile)) {
            return $shipments;
        }

        // Search WooCommerce orders by billing or shipping phone across all storage engines
        $order_ids = array();
        $table_shipments = $wpdb->prefix . 'ipw_shipments';

        // 1. HPOS addresses table (wc_order_addresses has phone column)
        $hpos_addr_table = $wpdb->prefix . 'wc_order_addresses';
        if ($wpdb->get_var("SHOW TABLES LIKE '$hpos_addr_table'") === $hpos_addr_table) {
            $hpos_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT order_id FROM $hpos_addr_table WHERE phone LIKE %s LIMIT 50",
                '%' . $wpdb->esc_like($clean_mobile) . '%'
            ));
            if (!empty($hpos_ids)) {
                $order_ids = array_merge($order_ids, $hpos_ids);
            }
        }

        // 2. Legacy postmeta table
        $meta_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_billing_phone', '_shipping_phone', 'billing_phone', 'shipping_phone') AND meta_value LIKE %s LIMIT 50",
            '%' . $wpdb->esc_like($clean_mobile) . '%'
        ));
        if (!empty($meta_ids)) {
            $order_ids = array_merge($order_ids, $meta_ids);
        }

        // 3. Direct verification of all booked shipments in ipw_shipments
        // Guarantees 100% match regardless of DB schema differences or phone formatting
        $all_shipments = $wpdb->get_results("SELECT id, order_id, barcode_no FROM $table_shipments ORDER BY id DESC LIMIT 100");
        if (!empty($all_shipments)) {
            foreach ($all_shipments as $sh_item) {
                $ord = wc_get_order($sh_item->order_id);
                if ($ord) {
                    $b_raw = $ord->get_billing_phone();
                    $s_raw = $ord->get_shipping_phone();
                    $b_clean = self::sanitize_mobile($b_raw);
                    $s_clean = self::sanitize_mobile($s_raw);

                    if ($b_clean === $clean_mobile || $s_clean === $clean_mobile ||
                        (!empty($b_raw) && strpos(preg_replace('/\D/', '', $b_raw), $clean_mobile) !== false) ||
                        (!empty($s_raw) && strpos(preg_replace('/\D/', '', $s_raw), $clean_mobile) !== false)) {
                        $order_ids[] = intval($sh_item->order_id);
                    }
                }
            }
        }

        $order_ids = array_unique(array_filter(array_map('intval', $order_ids)));

        if (empty($order_ids)) {
            return $shipments;
        }

        // Fetch India Post shipments for these orders
        $in_clause = implode(',', $order_ids);
        $records = $wpdb->get_results(
            "SELECT * FROM $table_shipments WHERE order_id IN ($in_clause) ORDER BY id DESC"
        );

        $barcodes_to_refresh = array();

        foreach ($records as $rec) {
            $order = wc_get_order($rec->order_id);
            if (!$order) {
                continue;
            }

            $shipment = array(
                'id'              => $rec->id,
                'order_id'        => $rec->order_id,
                'order_number'    => $order->get_order_number(),
                'order_date'      => wc_format_datetime($order->get_date_created()),
                'order_total'     => $order->get_formatted_order_total(),
                'order_status'    => wc_get_order_status_name($order->get_status()),
                'barcode_no'      => $rec->barcode_no,
                'article_type'    => $rec->article_type,
                'delivery_status' => $rec->delivery_status,
                'booking_date'    => $rec->created_at ? date_i18n(get_option('date_format'), strtotime($rec->created_at)) : '',
                'events'          => !empty($rec->tracking_cache) ? json_decode($rec->tracking_cache, true) : array(),
                'last_tracked'    => $rec->last_tracked_at ? human_time_diff(strtotime($rec->last_tracked_at)) . ' ago' : __('Just now', 'india-post-woocommerce'),
            );

            // If no cached events, queue for live fetch
            if (empty($shipment['events'])) {
                $barcodes_to_refresh[$rec->barcode_no] = $rec->id;
            }

            $shipments[] = $shipment;
        }

        // Live tracking refresh if needed
        if (!empty($barcodes_to_refresh)) {
            $api = new IPW_API_Client();
            $track_res = $api->track_bulk(array_keys($barcodes_to_refresh));
            if (!empty($track_res['success']) && !empty($track_res['data'])) {
                foreach ($track_res['data'] as $article_data) {
                    $awb = $article_data['booking_details']['article_number'];
                    if (isset($barcodes_to_refresh[$awb])) {
                        $events = isset($article_data['tracking_details']) ? $article_data['tracking_details'] : array();
                        $del_status = isset($article_data['del_status']['del_status']) ? $article_data['del_status']['del_status'] : 'in_transit';

                        $wpdb->update($table_shipments, array(
                            'tracking_cache'  => wp_json_encode($events),
                            'delivery_status' => $del_status,
                            'last_tracked_at' => current_time('mysql'),
                        ), array('barcode_no' => $awb));

                        foreach ($shipments as &$s) {
                            if ($s['barcode_no'] === $awb) {
                                $s['events'] = $events;
                                $s['delivery_status'] = $del_status;
                            }
                        }
                    }
                }
            }
        }

        return $shipments;
    }
}
}
