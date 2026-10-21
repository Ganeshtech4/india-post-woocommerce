<?php
/**
 * Shipments & Tracking Management Screen
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ipw-portal-container">
    <div class="ipw-subpage-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <img src="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/img/indiapost.png'); ?>" alt="India Post" class="ipw-subpage-logo" style="height:38px !important; max-height:38px !important; width:auto !important; max-width:110px !important; object-fit:contain !important; display:block !important;">
            <div>
                <h2><?php esc_html_e('India Post Shipments & Consignment Tracking', 'india-post-woocommerce'); ?></h2>
                <p class="description"><?php esc_html_e('Manage all booked India Post parcels, print shipping labels, and sync live tracking status.', 'india-post-woocommerce'); ?></p>
            </div>
        </div>
        <div class="ipw-header-actions">
            <button type="button" class="button button-primary" id="ipw-btn-bulk-sync">
                🔄 <?php esc_html_e('Sync Live Tracking for All', 'india-post-woocommerce'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-dashboard')); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Portal Dashboard', 'india-post-woocommerce'); ?>
            </a>
        </div>
    </div>

    <!-- Filter and Search Bar -->
    <div class="ipw-filter-bar">
        <form method="get" class="ipw-filter-form">
            <input type="hidden" name="page" value="india-post-shipments">
            <div class="ipw-search-box">
                <input type="search" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>" placeholder="<?php esc_attr_e('Search by AWB / Barcode or Order ID...', 'india-post-woocommerce'); ?>">
                <button type="submit" class="button"><?php esc_html_e('Search', 'india-post-woocommerce'); ?></button>
            </div>
            <div class="ipw-status-filter">
                <select name="delivery_status" onchange="this.form.submit()">
                    <option value=""><?php esc_html_e('All Statuses', 'india-post-woocommerce'); ?></option>
                    <option value="booked" <?php selected(isset($_GET['delivery_status']) ? $_GET['delivery_status'] : '', 'booked'); ?>><?php esc_html_e('Booked', 'india-post-woocommerce'); ?></option>
                    <option value="in_transit" <?php selected(isset($_GET['delivery_status']) ? $_GET['delivery_status'] : '', 'in_transit'); ?>><?php esc_html_e('In Transit', 'india-post-woocommerce'); ?></option>
                    <option value="out_for_delivery" <?php selected(isset($_GET['delivery_status']) ? $_GET['delivery_status'] : '', 'out_for_delivery'); ?>><?php esc_html_e('Out For Delivery', 'india-post-woocommerce'); ?></option>
                    <option value="delivered" <?php selected(isset($_GET['delivery_status']) ? $_GET['delivery_status'] : '', 'delivered'); ?>><?php esc_html_e('Delivered', 'india-post-woocommerce'); ?></option>
                </select>
            </div>
        </form>
    </div>

    <!-- Multi-Label / Bulk Actions Bar -->
    <div class="ipw-bulk-action-bar" id="ipw-bulk-shipments-bar" style="display:none; align-items:center; justify-content:space-between; background:#f0fdf4; border:1px solid #86efac; padding:12px 18px; border-radius:8px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,0.04);">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-weight:700; color:#166534; font-size:13px;" id="ipw-shipments-selected-count">0 shipments selected</span>
            <button type="button" class="button button-small" id="ipw-btn-select-all-shipments"><?php esc_html_e('Select All', 'india-post-woocommerce'); ?></button>
            <button type="button" class="button button-small" id="ipw-btn-deselect-all-shipments"><?php esc_html_e('Clear Selection', 'india-post-woocommerce'); ?></button>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="button button-primary button-large" id="ipw-btn-print-sheet" style="background:#15803d; border-color:#15803d; font-weight:700;">
                📄 <?php esc_html_e('Print Selected (A4 Sheet - 4 Labels per Page)', 'india-post-woocommerce'); ?>
            </button>
            <button type="button" class="button button-secondary" id="ipw-btn-print-thermal">
                🏷️ <?php esc_html_e('Print Selected (Single / Thermal)', 'india-post-woocommerce'); ?>
            </button>
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="ipw-table-wrapper">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column" style="width:36px; text-align:center; padding:8px 0;">
                        <input type="checkbox" id="ipw-cb-select-all-shipments" title="<?php esc_attr_e('Select All', 'india-post-woocommerce'); ?>">
                    </th>
                    <th style="width:160px;"><?php esc_html_e('AWB Barcode', 'india-post-woocommerce'); ?></th>
                    <th style="width:90px;"><?php esc_html_e('Order #', 'india-post-woocommerce'); ?></th>
                    <th><?php esc_html_e('Customer / Destination', 'india-post-woocommerce'); ?></th>
                    <th style="width:140px;"><?php esc_html_e('Article Type', 'india-post-woocommerce'); ?></th>
                    <th style="width:90px;"><?php esc_html_e('Weight', 'india-post-woocommerce'); ?></th>
                    <th style="width:90px;"><?php esc_html_e('Tariff', 'india-post-woocommerce'); ?></th>
                    <th style="width:130px;"><?php esc_html_e('Status', 'india-post-woocommerce'); ?></th>
                    <th style="width:140px;"><?php esc_html_e('Last Tracked', 'india-post-woocommerce'); ?></th>
                    <th style="width:190px;"><?php esc_html_e('Actions', 'india-post-woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($shipments)) : ?>
                    <?php foreach ($shipments as $sh) :
                        $order = wc_get_order($sh->order_id);
                        $recipient = $order ? ($order->get_formatted_shipping_full_name() ?: $order->get_formatted_billing_full_name()) : 'N/A';
                        $dest_pin = $order ? ($order->get_shipping_postcode() ?: $order->get_billing_postcode()) : '';
                        $print_thermal = wp_nonce_url(
                            admin_url('admin.php?action=ipw_print_label&order_id=' . $sh->order_id . '&format=thermal'),
                            'ipw_print_label_' . $sh->order_id
                        );
                        $print_sheet = wp_nonce_url(
                            admin_url('admin.php?action=ipw_print_label&order_id=' . $sh->order_id . '&format=sheet'),
                            'ipw_print_label_' . $sh->order_id
                        );
                    ?>
                        <tr>
                            <td style="text-align:center; padding:8px 0;">
                                <input type="checkbox" class="ipw-shipment-select-cb" value="<?php echo esc_attr($sh->order_id); ?>" data-barcode="<?php echo esc_attr($sh->barcode_no); ?>">
                            </td>
                            <td>
                                <strong class="font-mono"><?php echo esc_html($sh->barcode_no); ?></strong>
                                <button type="button" class="ipw-btn-icon-copy" data-copy-val="<?php echo esc_attr($sh->barcode_no); ?>" title="<?php esc_attr_e('Copy AWB', 'india-post-woocommerce'); ?>">📋</button>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $sh->order_id . '&action=edit')); ?>" class="ipw-order-link">
                                    #<?php echo esc_html($sh->order_id); ?>
                                </a>
                            </td>
                            <td>
                                <strong><?php echo esc_html($recipient); ?></strong>
                                <?php if ($dest_pin) : ?>
                                    <div class="ipw-small-pin">PIN: <?php echo esc_html($dest_pin); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $sh_t = strtoupper(trim((string)$sh->article_type));
                                $sh_svc = ($sh_t === 'BUSINESS_PARCEL' || strpos($sh_t, 'BP') !== false || $sh_t === 'PARCEL') ? 'Business Parcel' : (($sh_t === 'SP_INLAND_PARCEL') ? 'Speed Post Parcel' : 'Speed Post');
                                ?>
                                <span class="ipw-type-pill"><?php echo esc_html($sh_svc); ?></span>
                            </td>
                            <td><?php echo esc_html($sh->physical_weight); ?>g</td>
                            <td>₹<?php echo esc_html(number_format($sh->tariff, 2)); ?></td>
                            <td>
                                <?php 
                                $st = strtolower($sh->delivery_status);
                                $st_label = ($st === 'booked' || empty($st)) ? '📦 BOOKED (AWAITING PICKUP)' : (($st === 'in_transit') ? '🚚 IN TRANSIT' : (($st === 'out_for_delivery') ? '🛵 OUT FOR DELIVERY' : (($st === 'delivered') ? '✅ DELIVERED' : str_replace('_', ' ', strtoupper($sh->delivery_status)))));
                                ?>
                                <span class="ipw-status-pill status-<?php echo esc_attr(sanitize_html_class($sh->delivery_status)); ?>">
                                    <?php echo esc_html($st_label); ?>
                                </span>
                            </td>
                            <td>
                                <span class="ipw-time-text">
                                    <?php echo esc_html($sh->last_tracked_at ? human_time_diff(strtotime($sh->last_tracked_at)) . ' ago' : 'Never'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="ipw-row-actions">
                                    <a href="<?php echo esc_url($print_sheet); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Print Label in A4 Sheet (4 per Page)', 'india-post-woocommerce'); ?>">📄 <?php esc_html_e('A4 Sheet', 'india-post-woocommerce'); ?></a>
                                    <a href="<?php echo esc_url($print_thermal); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Print Thermal Label', 'india-post-woocommerce'); ?>">🏷️ <?php esc_html_e('Thermal', 'india-post-woocommerce'); ?></a>
                                    <button type="button" class="button button-small ipw-btn-refresh-row" data-order-id="<?php echo esc_attr($sh->order_id); ?>" title="<?php esc_attr_e('Sync Live Tracking', 'india-post-woocommerce'); ?>">🔄</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:30px; color:#64748b;">
                            <?php esc_html_e('No shipments match the selected criteria.', 'india-post-woocommerce'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
