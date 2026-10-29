<?php
/**
 * WooCommerce Order Integration for India Post Shipping & Booking
 *
 * Supports HPOS (Custom Order Tables) and Classic WP Postmeta.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('IPW_Order_Meta')) {
class IPW_Order_Meta {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;

        // Register Metabox
        add_action('add_meta_boxes', array($this, 'register_metabox'));

        // Custom Order List Columns
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_columns'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_order_column_content'), 10, 2);
        // Legacy column hooks
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_columns'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_legacy_order_column_content'), 10, 2);

        // AJAX Booking and Tracking
        add_action('wp_ajax_ipw_book_order_shipment', array($this, 'ajax_book_order_shipment'));
        add_action('wp_ajax_ipw_refresh_order_tracking', array($this, 'ajax_refresh_order_tracking'));
        add_action('wp_ajax_ipw_cancel_order_shipment', array($this, 'ajax_cancel_order_shipment'));

        // Customer My Account display
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_customer_tracking_info'));

        // WooCommerce Orders Bulk Actions
        add_filter('bulk_actions-edit-shop_order', array($this, 'register_wc_bulk_actions'));
        add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'register_wc_bulk_actions'));
        add_filter('handle_bulk_actions-edit-shop_order', array($this, 'handle_wc_bulk_actions'), 10, 3);
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'handle_wc_bulk_actions'), 10, 3);
    }

    /**
     * Register Bulk Actions for WooCommerce Orders page.
     */
    public function register_wc_bulk_actions($actions) {
        $actions['ipw_print_sheet']   = __('📄 Print India Post Labels (A6 Sheet - 4 Labels per Sheet)', 'india-post-woocommerce');
        $actions['ipw_print_thermal'] = __('🏷️ Print India Post Labels (Single / Thermal)', 'india-post-woocommerce');
        return $actions;
    }

    /**
     * Handle WooCommerce Bulk Actions.
     */
    public function handle_wc_bulk_actions($redirect_to, $action, $order_ids) {
        if ($action === 'ipw_print_sheet' || $action === 'ipw_print_thermal') {
            $format = ($action === 'ipw_print_sheet') ? 'sheet' : 'thermal';
            $ids = implode(',', array_map('intval', $order_ids));
            return admin_url('admin.php?action=ipw_print_bulk_labels&order_ids=' . urlencode($ids) . '&format=' . $format);
        }
        return $redirect_to;
    }

    /**
     * Register Order Metabox.
     */
    public function register_metabox() {
        $screen = class_exists(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class) &&
            wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'ipw_shipping_metabox',
            __('India Post Shipping & Tracking', 'india-post-woocommerce'),
            array($this, 'render_metabox'),
            $screen,
            'side',
            'high'
        );
    }

    /**
     * Render the Metabox content.
     */
    public function render_metabox($post_or_order) {
        $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
        if (!$order) {
            return;
        }

        $order_id        = $order->get_id();
        $barcode         = $order->get_meta('_ipw_barcode_no');
        $default_service = get_option('ipw_default_service', 'BUSINESS_PARCEL');
        $article_type    = $order->get_meta('_ipw_article_type') ?: $default_service;
        $weight          = $order->get_meta('_ipw_weight') ?: $this->estimate_order_weight($order);
        $tariff          = $order->get_meta('_ipw_tariff');
        $delivery_status = $order->get_meta('_ipw_delivery_status') ?: 'pending';
        $booked_at       = $order->get_meta('_ipw_booked_at');
        $tracking_cache  = $order->get_meta('_ipw_tracking_cache');
        $events          = !empty($tracking_cache) ? json_decode($tracking_cache, true) : array();

        $print_url_thermal = wp_nonce_url(
            admin_url('admin.php?action=ipw_print_label&order_id=' . $order_id . '&format=thermal'),
            'ipw_print_label_' . $order_id
        );
        ?>
        <div class="ipw-metabox-wrapper" data-order-id="<?php echo esc_attr($order_id); ?>">
            <?php wp_nonce_field('ipw_order_meta_nonce', 'ipw_nonce'); ?>
            <input type="hidden" id="ipw_order_id_field" value="<?php echo esc_attr($order_id); ?>">
            <?php if (!empty($barcode)) : ?>
                <!-- Already Booked State -->
                <div class="ipw-meta-booked-panel">
                    <div class="ipw-meta-badge-row">
                        <span class="ipw-status-badge status-<?php echo esc_attr(sanitize_html_class($delivery_status)); ?>">
                            <?php 
                            $badge_status_text = $delivery_status === 'booked' ? 'BOOKED (AWAITING PICKUP)' : str_replace('_', ' ', strtoupper($delivery_status));
                            echo esc_html($badge_status_text); 
                            ?>
                        </span>
                        <?php 
                        $sh_type = strtoupper(trim((string)$article_type));
                        $service_label = ($sh_type === 'BUSINESS_PARCEL' || strpos($sh_type, 'BP') !== false || $sh_type === 'PARCEL') ? 'Business Parcel' : (($sh_type === 'SP_INLAND_PARCEL') ? 'Speed Post Parcel' : 'Speed Post');
                        ?>
                        <span class="ipw-type-pill"><?php echo esc_html($service_label); ?></span>
                    </div>

                    <div class="ipw-consignment-display">
                        <label><?php esc_html_e('Consignment No:', 'india-post-woocommerce'); ?></label>
                        <div class="ipw-awb-copy-wrap">
                            <input type="text" readonly value="<?php echo esc_attr($barcode); ?>" class="ipw-code-input" id="ipw-awb-val-<?php echo esc_attr($order_id); ?>">
                            <button type="button" class="button button-small ipw-btn-copy" data-copy-val="<?php echo esc_attr($barcode); ?>" title="<?php esc_attr_e('Copy AWB', 'india-post-woocommerce'); ?>">📋</button>
                        </div>
                    </div>

                    <div class="ipw-meta-info-table">
                        <div><strong><?php esc_html_e('Weight:', 'india-post-woocommerce'); ?></strong> <?php echo esc_html($weight); ?>g</div>
                        <div><strong><?php esc_html_e('Tariff:', 'india-post-woocommerce'); ?></strong> ₹<?php echo esc_html($tariff ? number_format($tariff, 2) : '0.00'); ?></div>
                        <div><strong><?php esc_html_e('Booked:', 'india-post-woocommerce'); ?></strong> <?php echo esc_html($booked_at ? date_i18n('d M Y, H:i', strtotime($booked_at)) : 'N/A'); ?></div>
                    </div>

                    <div class="ipw-meta-actions">
                        <a href="<?php echo esc_url($print_url_thermal); ?>" target="_blank" class="button button-primary ipw-btn-block" style="font-size:13px; font-weight:700; padding:6px 12px; margin-bottom:8px; text-align:center;">
                            🏷️ <?php esc_html_e('Print Shipping Label', 'india-post-woocommerce'); ?>
                        </a>
                        <div class="ipw-action-row">
                            <button type="button" class="button button-secondary ipw-btn-block ipw-btn-refresh-tracking" data-order-id="<?php echo esc_attr($order_id); ?>">
                                🔄 <?php esc_html_e('Sync Live Tracking', 'india-post-woocommerce'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Mini Tracking Timeline -->
                    <?php if (!empty($events)) : ?>
                        <div class="ipw-mini-timeline">
                            <div class="ipw-timeline-title"><?php esc_html_e('Recent Tracking Events', 'india-post-woocommerce'); ?></div>
                            <ul class="ipw-timeline-list">
                                <?php foreach (array_slice($events, 0, 3) as $ev) : ?>
                                    <li>
                                        <div class="ipw-ev-event"><?php echo esc_html($ev['event']); ?></div>
                                        <div class="ipw-ev-loc"><?php echo esc_html($ev['office']); ?> • <?php echo esc_html(isset($ev['time']) ? $ev['time'] : ''); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="ipw-meta-footer">
                        <button type="button" class="button-link ipw-cancel-link" data-order-id="<?php echo esc_attr($order_id); ?>" onclick="if(confirm('Are you sure you want to remove this consignment booking from order?')) { ipwCancelBooking(<?php echo esc_js($order_id); ?>); }">
                            <?php esc_html_e('Void / Reset Booking', 'india-post-woocommerce'); ?>
                        </button>
                    </div>
                </div>

            <?php else : ?>
                <!-- Ready to Book State -->
                <div class="ipw-meta-unbooked-panel">
                    <p class="description">
                        <?php esc_html_e('Book this shipment directly with India Post Business API and generate AWB label.', 'india-post-woocommerce'); ?>
                    </p>

                    <p>
                        <label for="ipw_article_type"><strong><?php esc_html_e('Service / Parcel Type:', 'india-post-woocommerce'); ?></strong></label>
                        <select name="ipw_article_type" id="ipw_article_type" class="widefat">
                            <option value="BUSINESS_PARCEL" <?php selected($article_type, 'BUSINESS_PARCEL'); ?>>Business Parcel (BP) — Default</option>
                            <option value="SP_INLAND_PARCEL" <?php selected($article_type, 'SP_INLAND_PARCEL'); ?>>Speed Post Parcel (SP)</option>
                            <option value="SP_INLAND_DOC" <?php selected($article_type, 'SP_INLAND_DOC'); ?>>Speed Post Document (&le; 500g)</option>
                            <option value="24_SPEEDPOST_DOC" <?php selected($article_type, '24_SPEEDPOST_DOC'); ?>>24 Speed Post DOC (NDD)</option>
                            <option value="24_SPP_PARSPL" <?php selected($article_type, '24_SPP_PARSPL'); ?>>24 SP Parcel Special</option>
                            <option value="48_SPEEDPOST_DOC" <?php selected($article_type, '48_SPEEDPOST_DOC'); ?>>48 Speed Post DOC</option>
                        </select>
                    </p>

                    <div class="ipw-fields-row">
                        <p>
                            <label for="ipw_weight"><strong><?php esc_html_e('Weight (grams):', 'india-post-woocommerce'); ?></strong></label>
                            <input type="number" name="ipw_weight" id="ipw_weight" class="widefat" value="<?php echo esc_attr($weight); ?>" min="1" max="35000">
                        </p>
                        <p>
                            <label for="ipw_pickup_dropoff"><strong><?php esc_html_e('Drop / Pick:', 'india-post-woocommerce'); ?></strong></label>
                            <select name="ipw_pickup_dropoff" id="ipw_pickup_dropoff" class="widefat">
                                <option value="DROPOFF"><?php esc_html_e('DROPOFF', 'india-post-woocommerce'); ?></option>
                                <option value="PICKUP"><?php esc_html_e('PICKUP', 'india-post-woocommerce'); ?></option>
                            </select>
                        </p>
                    </div>

                    <div class="ipw-dimensions-row">
                        <label><strong><?php esc_html_e('Dimensions (L x W x H cm):', 'india-post-woocommerce'); ?></strong></label>
                        <div class="ipw-dim-inputs">
                            <input type="number" id="ipw_dim_l" placeholder="L" value="20" class="widefat">
                            <input type="number" id="ipw_dim_w" placeholder="W" value="15" class="widefat">
                            <input type="number" id="ipw_dim_h" placeholder="H" value="2" class="widefat">
                        </div>
                    </div>

                    <p>
                        <label for="ipw_custom_awb"><strong><?php esc_html_e('Custom AWB Barcode (Optional):', 'india-post-woocommerce'); ?></strong></label>
                        <input type="text" id="ipw_custom_awb" placeholder="<?php esc_attr_e('Leave blank for auto-allocation', 'india-post-woocommerce'); ?>" class="widefat">
                        <small class="description"><?php esc_html_e('E.g. ET214330013IN. Leave blank to auto-allocate from configured series.', 'india-post-woocommerce'); ?></small>
                    </p>

                    <div class="ipw-book-action-wrap">
                        <button type="button" class="button button-primary button-large ipw-btn-block ipw-btn-book" data-order-id="<?php echo esc_attr($order_id); ?>">
                            📦 <?php esc_html_e('Book Shipment with India Post', 'india-post-woocommerce'); ?>
                        </button>
                        <div class="ipw-ajax-spinner" style="display:none; text-align:center; margin-top:8px;">
                            <span class="spinner is-active" style="float:none;"></span> <?php esc_html_e('Contacting India Post API...', 'india-post-woocommerce'); ?>
                        </div>
                        <div class="ipw-ajax-msg" style="display:none; margin-top:8px;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Estimate order weight in grams from WooCommerce cart/order items.
     */
    private function estimate_order_weight($order) {
        $weight_unit = get_option('woocommerce_weight_unit', 'kg');
        $total_weight_kg = 0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->has_weight()) {
                $item_weight = floatval($product->get_weight()) * $item->get_quantity();
                if ($weight_unit === 'kg') {
                    $total_weight_kg += $item_weight;
                } elseif ($weight_unit === 'g') {
                    $total_weight_kg += $item_weight / 1000;
                }
            }
        }

        $grams = intval(round($total_weight_kg * 1000));
        $default_dummy = intval(get_option('ipw_default_weight', 250));
        return $grams > 0 ? $grams : ($default_dummy > 0 ? $default_dummy : 250);
    }

    /**
     * Add columns to Orders table (India Post AWB & Delivery Status).
     */
    public function add_order_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'order_status') {
                $new_columns['ipw_delivery_status'] = __('Delivery Status', 'india-post-woocommerce');
                $new_columns['ipw_consignment']     = __('India Post AWB', 'india-post-woocommerce');
            }
        }
        if (!isset($new_columns['ipw_consignment'])) {
            $new_columns['ipw_delivery_status'] = __('Delivery Status', 'india-post-woocommerce');
            $new_columns['ipw_consignment']     = __('India Post AWB', 'india-post-woocommerce');
        }
        return $new_columns;
    }

    /**
     * Render column content for HPOS.
     */
    public function render_order_column_content($column, $order) {
        if ($column === 'ipw_delivery_status') {
            $this->render_delivery_status_badge($order);
        } elseif ($column === 'ipw_consignment') {
            $this->render_consignment_column($order);
        }
    }

    /**
     * Render column content for legacy posts table.
     */
    public function render_legacy_order_column_content($column, $post_id) {
        $order = wc_get_order($post_id);
        if (!$order) {
            return;
        }

        if ($column === 'ipw_delivery_status') {
            $this->render_delivery_status_badge($order);
        } elseif ($column === 'ipw_consignment') {
            $this->render_consignment_column($order);
        }
    }

    /**
     * Render Delivery Status Badge.
     */
    private function render_delivery_status_badge($order) {
        $barcode = $order->get_meta('_ipw_barcode_no');
        $status  = strtolower(trim($order->get_meta('_ipw_delivery_status') ?: ''));

        if (empty($barcode)) {
            echo '<span class="ipw-order-badge ipw-badge-unbooked">—</span>';
            return;
        }

        if (empty($status) || $status === 'booked') {
            echo '<span class="ipw-order-badge ipw-badge-booked">📦 ' . esc_html__('Booked (Awaiting Pickup)', 'india-post-woocommerce') . '</span>';
        } elseif ($status === 'in_transit') {
            echo '<span class="ipw-order-badge ipw-badge-transit">🚚 ' . esc_html__('In Transit', 'india-post-woocommerce') . '</span>';
        } elseif ($status === 'out_for_delivery') {
            echo '<span class="ipw-order-badge ipw-badge-ofd">🛵 ' . esc_html__('Out For Delivery', 'india-post-woocommerce') . '</span>';
        } elseif ($status === 'delivered') {
            echo '<span class="ipw-order-badge ipw-badge-delivered">✅ ' . esc_html__('Delivered', 'india-post-woocommerce') . '</span>';
        } else {
            echo '<span class="ipw-order-badge ipw-badge-transit">' . esc_html(strtoupper(str_replace('_', ' ', $status))) . '</span>';
        }
    }

    /**
     * Render AWB Consignment Link & Label Print.
     */
    private function render_consignment_column($order) {
        $barcode = $order->get_meta('_ipw_barcode_no');

        if (!empty($barcode)) {
            $print_url = wp_nonce_url(
                admin_url('admin.php?action=ipw_print_label&order_id=' . $order->get_id() . '&format=thermal'),
                'ipw_print_label_' . $order->get_id()
            );
            echo '<div class="ipw-order-awb-cell">';
            echo '<a href="' . esc_url($print_url) . '" target="_blank" class="ipw-awb-link" title="' . esc_attr__('Print Shipping Label', 'india-post-woocommerce') . '">';
            echo '<strong>' . esc_html($barcode) . '</strong> <span style="font-size:11px;">🏷️</span>';
            echo '</a>';
            echo '</div>';
        } else {
            echo '<span style="color:#94a3b8;">—</span>';
        }
    }

    /**
     * AJAX Booking Handler.
     */
    public function ajax_book_order_shipment() {
        if (!check_ajax_referer('ipw_order_meta_nonce', 'nonce', false) && !check_ajax_referer('ipw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page.', 'india-post-woocommerce')));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'india-post-woocommerce')));
        }

        $default_service = get_option('ipw_default_service', 'BUSINESS_PARCEL');
        $order_id       = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $article_type   = isset($_POST['article_type']) ? sanitize_text_field($_POST['article_type']) : $default_service;
        $default_weight = intval(get_option('ipw_default_weight', 250));
        $weight         = isset($_POST['weight']) ? intval($_POST['weight']) : $default_weight;
        $pickup_dropoff = isset($_POST['pickup_dropoff']) ? sanitize_text_field($_POST['pickup_dropoff']) : 'DROPOFF';
        $dim_l          = isset($_POST['dim_l']) ? floatval($_POST['dim_l']) : 20;
        $dim_w          = isset($_POST['dim_w']) ? floatval($_POST['dim_w']) : 15;
        $dim_h          = isset($_POST['dim_h']) ? floatval($_POST['dim_h']) : 2;
        $custom_awb     = isset($_POST['custom_awb']) ? sanitize_text_field($_POST['custom_awb']) : '';

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'india-post-woocommerce')));
        }

        // Determine Barcode
        if (!empty($custom_awb)) {
            $barcode = strtoupper(trim($custom_awb));
            if (!IPW_Barcode_Helper::validate_awb($barcode)) {
                wp_send_json_error(array('message' => __('Invalid custom AWB format. Must be 13 characters with valid UPU S10 check digit.', 'india-post-woocommerce')));
            }
        } else {
            $barcode = IPW_Barcode_Helper::get_next_available_awb($article_type);
            if (is_wp_error($barcode)) {
                wp_send_json_error(array('message' => $barcode->get_error_message()));
            }
        }

        // Determine Contract ID based on environment and article type
        $env = get_option('ipw_environment', 'sandbox');
        if ($env === 'sandbox') {
            // Official CEPT UAT-8 specifications:
            // Customer id: 3000064781, contract_id: 41585456 for all article types in sandbox
            $contract_id = '41585456';
        } else {
            $contract_id = get_option('ipw_contract_id_sp', '41585456');
            if (strpos($article_type, 'BP') !== false || $article_type === 'BUSINESS_PARCEL') {
                $contract_id = get_option('ipw_contract_id_bp', '41585456');
            } elseif ($article_type === '24_SPEEDPOST_DOC') {
                $contract_id = get_option('ipw_contract_id_24sp_doc', '41469430');
            } elseif ($article_type === '24_SPP_PARSPL') {
                $contract_id = get_option('ipw_contract_id_24spp_par', '41918281');
            } elseif ($article_type === '48_SPEEDPOST_DOC') {
                $contract_id = get_option('ipw_contract_id_48sp_doc', '41471113');
            }
        }
        // Build Payload according to official India Post specs (doc_dump.txt lines 996-1076)
        $customer_id = get_option('ipw_customer_id', '3000064781');
        $office_id   = intval(get_option('ipw_default_office_id', '21260024'));

        $is_cod = $order->get_payment_method() === 'cod';
        $cod_val = $is_cod ? round($order->get_total(), 2) : 0;

        $recipient_name = substr($order->get_formatted_shipping_full_name() ?: $order->get_formatted_billing_full_name(), 0, 80);
        $recipient_addr1 = substr($order->get_shipping_address_1() ?: $order->get_billing_address_1(), 0, 80);
        $recipient_addr2 = substr($order->get_shipping_address_2() ?: $order->get_billing_address_2(), 0, 80);
        $recipient_city  = substr($order->get_shipping_city() ?: $order->get_billing_city(), 0, 80);
        $recipient_state = substr($order->get_shipping_state() ?: $order->get_billing_state(), 0, 80);
        $recipient_pin   = preg_replace('/\D/', '', $order->get_shipping_postcode() ?: $order->get_billing_postcode());
        $recipient_phone = IPW_OTP_Service::sanitize_mobile($order->get_billing_phone());

        $sender_name     = substr(get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS'), 0, 80);
        $sender_company  = substr(get_option('ipw_sender_company', 'Dhruva Publications'), 0, 80);
        $sender_addr1    = substr(get_option('ipw_sender_add_line_1', 'Main Office Road'), 0, 80);
        $sender_addr2    = substr(get_option('ipw_sender_add_line_2', ''), 0, 80);
        $sender_city     = substr(get_option('ipw_sender_city', 'Bengaluru'), 0, 80);
        $sender_state    = substr(get_option('ipw_sender_state', 'Karnataka'), 0, 80);
        $sender_pin      = preg_replace('/\D/', '', get_option('ipw_sender_pincode', '560001'));
        $sender_phone    = IPW_OTP_Service::sanitize_mobile(get_option('ipw_sender_mobile_no', '9876543210'));

        $shape = (strpos($article_type, 'DOC') !== false) ? 'DOC' : 'NROL';

        $article_payload = array(
            'bulk_customer_id'         => (string) $customer_id,
            'contract_id'              => (string) $contract_id,
            'barcode_no'               => (string) $barcode,
            'pickup_or_dropoff'        => strtolower($pickup_dropoff),
            'pickup_dropoff_office_id' => $office_id,
            'article_type'             => $article_type,
            'physical_weight'          => $weight,
            'shape_of_article'         => $shape,
            'length'                   => (string) $dim_l,
            'breadth_diameter'         => (string) $dim_w,
            'height'                   => (string) $dim_h,
            'priority_flag'            => 'FALSE',
            'delivery_instruction'     => '',
            'delivery_slot'            => '',
            'instruction_rts'          => 'RTS',
            'sender_name'              => $sender_name,
            'sender_company'           => $sender_company,
            'sender_add_line_1'        => $sender_addr1,
            'sender_add_line_2'        => $sender_addr2,
            'sender_city'              => $sender_city,
            'sender_state'             => $sender_state,
            'sender_pincode'           => $sender_pin,
            'sender_emailid'           => get_option('ipw_sender_emailid', 'info@dhuruvapublications.com'),
            'sender_alt_contact'       => '',
            'sender_kyc'               => '',
            'sender_tax_reference'     => '',
            'receiver_name'            => $recipient_name ?: 'Customer',
            'receiver_company'         => '',
            'receiver_add_line_1'      => $recipient_addr1 ?: 'Main Street',
            'receiver_add_line_2'      => $recipient_addr2,
            'receiver_city'            => $recipient_city ?: 'City',
            'receiver_state'           => $recipient_state ?: 'State',
            'receiver_pincode'         => $recipient_pin ?: '110001',
            'receiver_emailid'         => $order->get_billing_email(),
            'receiver_alt_contact'     => '',
            'receiver_kyc'             => '',
            'receiver_tax_reference'   => '',
            'alt_address_flag'         => 'FALSE',
            'pickup_address_flag'      => ($pickup_dropoff === 'PICKUP') ? 'TRUE' : 'FALSE',
            'drop_off_pincode'         => $sender_pin,
            'sender_mobile_no'         => $sender_phone,
            'receiver_mobile_no'       => $recipient_phone ?: '9876543210',
            'prepayment_code'          => '',
            'value_of_prepayment'      => 0,
            'codr_cod'                 => $is_cod ? 'COD' : '',
            'value_for_codr_cod'       => $cod_val,
            'insurance_type'           => '',
            'value_of_insurance'       => 0,
            'ack'                      => 'FALSE',
            'reg'                      => 'FALSE',
            'otp'                      => ($article_type === '24_SPP_PARSPL') ? 'TRUE' : 'FALSE',
            'bulk_reference'           => 'ORD_' . $order_id . '_' . substr(time(), -5),
        );

        $api = new IPW_API_Client();
        $booking_res = $api->book_articles(array($article_payload));

        if (is_wp_error($booking_res)) {
            wp_send_json_error(array('message' => $booking_res->get_error_message()));
        }

        // Check if article had errors
        if (!empty($booking_res['error_articles'])) {
            $errs = implode(', ', (array) $booking_res['error_articles'][0]['errors']);
            wp_send_json_error(array('message' => sprintf(__('India Post booking error: %s', 'india-post-woocommerce'), $errs)));
        }

        $calculated_tariff = 0;
        if (!empty($booking_res['valid_articles'][0]['calculated_tariff'])) {
            $calculated_tariff = floatval($booking_res['valid_articles'][0]['calculated_tariff']);
        }

        $now = current_time('mysql');
        $initial_events = array(
            array(
                'date'     => date('Y-m-d\TH:i:s\Z', strtotime($now)),
                'time'     => date('H:i:s', strtotime($now)),
                'office'   => get_option('ipw_sender_city', 'Bengaluru') . ' NSH',
                'officeid' => $office_id ?: '21260024',
                'event'    => 'Item Booked',
                'remarks'  => 'Consignment Booked & Awaiting Dispatch',
                'rts'      => false,
            ),
        );

        // Save metadata on Order
        $order->update_meta_data('_ipw_barcode_no', $barcode);
        $order->update_meta_data('_ipw_article_type', $article_type);
        $order->update_meta_data('_ipw_weight', $weight);
        $order->update_meta_data('_ipw_tariff', $calculated_tariff);
        $order->update_meta_data('_ipw_contract_id', $contract_id);
        $order->update_meta_data('_ipw_delivery_status', 'booked');
        $order->update_meta_data('_ipw_booked_at', $now);
        $order->update_meta_data('_ipw_tracking_cache', wp_json_encode($initial_events));
        $order->update_meta_data('_ipw_tracking_last_sync', time());
        $order->save();

        // Save in custom shipments table
        global $wpdb;
        $table = $wpdb->prefix . 'ipw_shipments';
        $wpdb->replace($table, array(
            'order_id'            => $order_id,
            'barcode_no'          => $barcode,
            'article_type'        => $article_type,
            'physical_weight'     => $weight,
            'tariff'              => $calculated_tariff,
            'currency'            => 'INR',
            'booking_status'      => 'booked',
            'delivery_status'     => 'booked',
            'pickup_or_dropoff'   => $pickup_dropoff,
            'office_id'           => $office_id,
            'batch_id'            => isset($booking_res['batch_id']) ? $booking_res['batch_id'] : '',
            'mail_booking_dom_id' => isset($booking_res['mail_booking_dom_id']) ? $booking_res['mail_booking_dom_id'] : '',
            'tracking_cache'      => wp_json_encode($initial_events),
            'last_tracked_at'     => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ));

        // Add note to order
        $order->add_order_note(sprintf(
            __('Booked with India Post. Consignment No: %s (%s). Tariff: ₹%s.', 'india-post-woocommerce'),
            $barcode,
            $article_type,
            number_format($calculated_tariff, 2)
        ));

        wp_send_json_success(array(
            'message'  => sprintf(__('Successfully booked shipment with India Post! AWB: %s', 'india-post-woocommerce'), $barcode),
            'barcode'  => $barcode,
            'tariff'   => $calculated_tariff,
        ));
    }

    /**
     * AJAX Refresh Tracking Status.
     */
    public function ajax_refresh_order_tracking() {
        if (!check_ajax_referer('ipw_order_meta_nonce', 'nonce', false) && !check_ajax_referer('ipw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page.', 'india-post-woocommerce')));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'india-post-woocommerce')));
        }

        $barcode = $order->get_meta('_ipw_barcode_no');
        if (empty($barcode)) {
            wp_send_json_error(array('message' => __('Order has no consignment number.', 'india-post-woocommerce')));
        }

        $api = new IPW_API_Client();
        $res = $api->track_bulk(array($barcode));

        if (is_wp_error($res) || empty($res['data'])) {
            wp_send_json_error(array('message' => __('No tracking updates received from India Post API.', 'india-post-woocommerce')));
        }

        $article_data = $res['data'][0];
        $events       = isset($article_data['tracking_details']) ? $article_data['tracking_details'] : array();
        $del_status   = isset($article_data['del_status']['del_status']) ? $article_data['del_status']['del_status'] : 'in_transit';

        $order->update_meta_data('_ipw_tracking_cache', wp_json_encode($events));
        $order->update_meta_data('_ipw_delivery_status', $del_status);
        $order->save();

        global $wpdb;
        $table = $wpdb->prefix . 'ipw_shipments';
        $wpdb->update($table, array(
            'tracking_cache'  => wp_json_encode($events),
            'delivery_status' => $del_status,
            'last_tracked_at' => current_time('mysql'),
        ), array('order_id' => $order_id));

        wp_send_json_success(array(
            'message' => __('Tracking status refreshed successfully.', 'india-post-woocommerce'),
            'status'  => $del_status,
            'events'  => $events,
        ));
    }

    /**
     * AJAX Cancel / Void Booking.
     */
    public function ajax_cancel_order_shipment() {
        if (!check_ajax_referer('ipw_order_meta_nonce', 'nonce', false) && !check_ajax_referer('ipw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security verification failed. Please refresh the page.', 'india-post-woocommerce')));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'india-post-woocommerce')));
        }

        $barcode = $order->get_meta('_ipw_barcode_no');
        $order->delete_meta_data('_ipw_barcode_no');
        $order->delete_meta_data('_ipw_delivery_status');
        $order->delete_meta_data('_ipw_tracking_cache');
        $order->save();

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ipw_shipments', array('order_id' => $order_id));

        $order->add_order_note(sprintf(__('India Post Consignment %s was unlinked/voided from this order.', 'india-post-woocommerce'), $barcode));

        wp_send_json_success(array('message' => __('Booking reset successfully.', 'india-post-woocommerce')));
    }

    /**
     * Display tracking info on customer's My Account View Order page.
     */
    public function display_customer_tracking_info($order) {
        $barcode = $order->get_meta('_ipw_barcode_no');
        if (empty($barcode)) {
            return;
        }

        $status = $order->get_meta('_ipw_delivery_status') ?: 'in_transit';
        $cache  = $order->get_meta('_ipw_tracking_cache');
        $events = !empty($cache) ? json_decode($cache, true) : array();
        ?>
        <section class="woocommerce-customer-details ipw-customer-tracking-box" style="margin-top:20px; border:1px solid #e2e8f0; border-radius:8px; padding:16px; background:#fafafa;">
            <h2 class="woocommerce-column__title" style="margin-top:0; color:#8b151b; font-size:1.15rem; display:flex; align-items:center; gap:8px;">
                <span>📮</span> <?php esc_html_e('India Post Shipment Tracking', 'india-post-woocommerce'); ?>
            </h2>
            <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center; margin-bottom:12px;">
                <div>
                    <strong><?php esc_html_e('Consignment Number:', 'india-post-woocommerce'); ?></strong>
                    <span style="font-family:monospace; font-size:1.1rem; font-weight:bold; color:#0f172a; margin-left:6px;"><?php echo esc_html($barcode); ?></span>
                </div>
                <div>
                    <strong><?php esc_html_e('Status:', 'india-post-woocommerce'); ?></strong>
                    <span style="background:#e0f2fe; color:#0369a1; padding:3px 10px; border-radius:999px; font-weight:bold; font-size:0.85rem; text-transform:uppercase; margin-left:6px;">
                        <?php echo esc_html(str_replace('_', ' ', $status)); ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($events)) : ?>
                <div style="border-top:1px solid #e2e8f0; padding-top:10px; font-size:0.9rem;">
                    <strong style="display:block; margin-bottom:6px;"><?php esc_html_e('Latest Updates:', 'india-post-woocommerce'); ?></strong>
                    <ul style="margin:0; padding-left:20px;">
                        <?php foreach (array_slice($events, 0, 3) as $ev) : ?>
                            <li style="margin-bottom:4px;">
                                <strong><?php echo esc_html($ev['event']); ?></strong>
                                <span style="color:#64748b;">(<?php echo esc_html($ev['office']); ?> - <?php echo esc_html(isset($ev['time']) ? $ev['time'] : ''); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
}
