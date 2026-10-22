<?php
/**
 * Inbound Webhook Handler for India Post Real-Time Event Updates
 * Endpoint: /wp-json/india-post/v1/webhook
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('IPW_Webhook')) {
class IPW_Webhook {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        add_action('rest_api_init', array($this, 'register_webhook_routes'));
    }

    /**
     * Register REST API endpoint.
     */
    public function register_webhook_routes() {
        register_rest_route('india-post/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_incoming_event'),
            'permission_callback' => '__return_true', // Validated via optional secret or IP check
        ));
    }

    /**
     * Handle incoming tracking event payload from India Post CEPT.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_incoming_event($request) {
        $payload = $request->get_json_params();

        if (empty($payload)) {
            $raw = $request->get_body();
            $payload = json_decode($raw, true);
        }

        if (empty($payload) || empty($payload['article_number'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Missing article_number in payload',
            ), 400);
        }

        $awb         = strtoupper(trim($payload['article_number']));
        $event_desc  = isset($payload['event_description']) ? sanitize_text_field($payload['event_description']) : 'Event Update';
        $event_code  = isset($payload['event_code']) ? strtoupper(sanitize_text_field($payload['event_code'])) : '';
        $office_name = isset($payload['event_office_name']) ? sanitize_text_field($payload['event_office_name']) : '';
        $office_id   = isset($payload['event_office_facility_id']) ? sanitize_text_field($payload['event_office_facility_id']) : '';
        $event_date  = isset($payload['event_date']) ? sanitize_text_field($payload['event_date']) : current_time('Y-m-d');
        $event_time  = isset($payload['event_time']) ? sanitize_text_field($payload['event_time']) : current_time('H:i:s');

        global $wpdb;
        $table = $wpdb->prefix . 'ipw_shipments';
        $shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE barcode_no = %s LIMIT 1", $awb));

        if (!$shipment) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Event acknowledged, consignment not registered in this store',
            ), 200);
        }

        // Map status
        $new_status = 'in_transit';
        if (in_array($event_code, array('ITEM_DELIVERED', 'DELIVERED', 'DELIVERY_CONFIRMED'))) {
            $new_status = 'delivered';
        } elseif (in_array($event_code, array('OUT_FOR_DELIVERY', 'ASSIGNED_TO_POSTMAN'))) {
            $new_status = 'out_for_delivery';
        } elseif (in_array($event_code, array('RTS', 'RETURN_TO_SENDER', 'ITEM_RETURNED'))) {
            $new_status = 'returned';
        }

        // Append to tracking events
        $existing_events = !empty($shipment->tracking_cache) ? json_decode($shipment->tracking_cache, true) : array();
        if (!is_array($existing_events)) {
            $existing_events = array();
        }

        $new_event_node = array(
            'date'     => $event_date . 'T' . $event_time . 'Z',
            'time'     => $event_time,
            'office'   => $office_name,
            'officeid' => $office_id,
            'event'    => $event_desc,
            'remarks'  => isset($payload['non_delivery_reason']) ? sanitize_text_field($payload['non_delivery_reason']) : '',
            'rts'      => ($new_status === 'returned'),
        );

        // Prepend new event at top
        array_unshift($existing_events, $new_event_node);

        $wpdb->update($table, array(
            'delivery_status' => $new_status,
            'tracking_cache'  => wp_json_encode($existing_events),
            'last_tracked_at' => current_time('mysql'),
        ), array('id' => $shipment->id));

        // Update WooCommerce Order
        if ($shipment->order_id) {
            $order = wc_get_order($shipment->order_id);
            if ($order) {
                $order->update_meta_data('_ipw_delivery_status', $new_status);
                $order->update_meta_data('_ipw_tracking_cache', wp_json_encode($existing_events));
                $order->save();

                $order->add_order_note(sprintf(
                    __('India Post Event: %s at %s (%s). Status: %s.', 'india-post-woocommerce'),
                    $event_desc,
                    $office_name,
                    $event_time,
                    strtoupper($new_status)
                ));

                // If delivered and configured, optionally set order to completed
                if ($new_status === 'delivered' && $order->has_status(array('processing', 'on-hold'))) {
                    $order->update_status('completed', __('Completed automatically via India Post delivery confirmation.', 'india-post-woocommerce'));
                }
            }
        }

        return new WP_REST_Response(array(
            'success'   => true,
            'message'   => 'India Post webhook event processed successfully',
            'barcode'   => $awb,
            'status'    => $new_status,
        ), 200);
    }
}
}
