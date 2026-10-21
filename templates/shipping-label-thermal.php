<?php
/**
 * Thermal 4x6 / A6 Shipping Label Template for India Post
 * Styled strictly according to the Dhruva Publications reference format with itemized details and barcodes.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$client_logo_path = IPW_PLUGIN_PATH . 'assets/img/DHRUVA_PUBLICATIONS.png';
$client_logo_url  = IPW_PLUGIN_URL . 'assets/img/DHRUVA_PUBLICATIONS.png';
$has_client_logo  = file_exists($client_logo_path);

$customer_id = get_option('ipw_customer_id', '1156062849');

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
$items = $order->get_items();
$shipping_total = floatval($order->get_shipping_total());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(sprintf(__('Shipping Label - %s (Order #%s)', 'india-post-woocommerce'), $barcode, $order->get_order_number())); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/css/shipping-label.css'); ?>?ver=<?php echo file_exists(IPW_PLUGIN_PATH . 'assets/css/shipping-label.css') ? filemtime(IPW_PLUGIN_PATH . 'assets/css/shipping-label.css') : IPW_VERSION; ?>">
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0mm;
        }
        @media print {
            html, body {
                width: 100mm !important;
                height: 150mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
            }
            .no-print, .ipw-print-controls {
                display: none !important;
            }
            .ipw-label-wrapper {
                width: 100mm !important;
                height: 150mm !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                background: #ffffff !important;
            }
            .ipw-label-container {
                width: 96mm !important;
                height: 146mm !important;
                max-height: 146mm !important;
                margin: 0 !important;
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ipw-label-wrapper">
        <div class="ipw-label-container">

            <!-- SECTION 1: TO (RECIPIENT) -->
            <div class="ipw-section ipw-section-to">
                <div class="ipw-sec-title">TO</div>
                <div class="ipw-recipient-name"><?php echo esc_html(strtoupper($recipient_name)); ?></div>
                <div class="ipw-address-lines">
                    <?php 
                    $addr1 = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
                    $addr2 = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
                    $city  = $order->get_shipping_city() ?: $order->get_billing_city();
                    $state = $order->get_shipping_state() ?: $order->get_billing_state();
                    $pin   = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
                    ?>
                    <div><?php echo esc_html(strtoupper($addr1)); ?></div>
                    <?php if (!empty($addr2)) : ?>
                        <div><?php echo esc_html(strtoupper($addr2)); ?></div>
                    <?php endif; ?>
                    <div><?php echo esc_html(strtoupper(trim("$city $state $pin"))); ?></div>
                </div>
                <div class="ipw-contact-row">
                    <div>CONTACT PERSON :<?php echo esc_html(strtoupper($recipient_name)); ?></div>
                    <div>PHONE :<?php echo esc_html($recipient_phone ?: 'N/A'); ?></div>
                </div>
            </div>

            <!-- SECTION 2: FROM (SENDER) & CLIENT LOGO -->
            <div class="ipw-section ipw-section-from">
                <div class="ipw-from-left">
                    <div class="ipw-sec-title">FROM</div>
                    <div class="ipw-sender-name"><?php echo esc_html(strtoupper(get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS'))); ?></div>
                    <div class="ipw-sender-address">
                        <?php 
                        $s_line1 = get_option('ipw_sender_add_line_1', 'PLOT NO-168,TOWN CENTRE LAYOUT, NEAR');
                        $s_line2 = get_option('ipw_sender_add_line_2', 'KALYANA VENKATESWARA SWAMY TEMPLE, 100FT');
                        $s_city  = get_option('ipw_sender_city', 'RING ROAD, VIZIANAGARAM');
                        $s_state = get_option('ipw_sender_state', 'ANDHRA PRADESH');
                        $s_pin   = get_option('ipw_sender_pincode', '535002');
                        $s_phone = get_option('ipw_sender_mobile_no', '7799718787');
                        ?>
                        <div><?php echo esc_html(strtoupper($s_line1)); ?></div>
                        <?php if (!empty($s_line2)) : ?>
                            <div><?php echo esc_html(strtoupper($s_line2)); ?></div>
                        <?php endif; ?>
                        <div><?php echo esc_html(strtoupper(trim("$s_city, $s_state - $s_pin"))); ?></div>
                        <div>PHONE :<?php echo esc_html($s_phone); ?></div>
                    </div>
                </div>
                <div class="ipw-from-right">
                    <?php if ($has_client_logo) : ?>
                        <div class="ipw-client-logo-box">
                            <img src="<?php echo esc_url($client_logo_url); ?>" alt="Dhruva Publications" class="ipw-client-logo-img">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 3: ITEM DETAIL TABLE -->
            <div class="ipw-section ipw-section-items">
                <div class="ipw-items-header">ITEM DETAIL</div>
                <table class="ipw-items-table">
                    <thead>
                        <tr>
                            <th class="col-sr">SR. NO.</th>
                            <th class="col-name">NAME OF PRODUCT / SERVICE</th>
                            <th class="col-hsn">HSN / SAC</th>
                            <th class="col-qty">QTY</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sr = 1;
                        $default_hsn = get_option('ipw_default_hsn', '4901');
                        foreach ($items as $item_id => $item) : 
                            $product = $item->get_product();
                            $hsn = $default_hsn;
                            if ($product) {
                                foreach (array('_hsn_code', 'hsn_code', 'hsn', '_hsn', '_ts_hsn_code', 'gst_hsn_code') as $hkey) {
                                    $val = $product->get_meta($hkey);
                                    if (!empty($val)) {
                                        $hsn = $val;
                                        break;
                                    }
                                }
                            }
                            $qty = floatval($item->get_quantity());
                        ?>
                            <tr>
                                <td class="col-sr"><?php echo esc_html($sr++); ?></td>
                                <td class="col-name">
                                    <strong><?php echo esc_html(strtoupper($item->get_name())); ?></strong>
                                    <?php 
                                    $meta_data = $item->get_formatted_meta_data();
                                    if (!empty($meta_data)) {
                                        $meta_strings = array();
                                        foreach ($meta_data as $meta) {
                                            $meta_strings[] = esc_html($meta->display_key . ': ' . strip_tags($meta->display_value));
                                        }
                                        echo '<div class="ipw-item-meta">' . esc_html(strtoupper(implode(', ', $meta_strings))) . '</div>';
                                    }
                                    ?>
                                </td>
                                <td class="col-hsn"><?php echo esc_html($hsn); ?></td>
                                <td class="col-qty"><?php echo esc_html(number_format($qty, 2)); ?> NOS</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- SECTION 4: INDIA POST DETAILS & BARCODE -->
            <div class="ipw-section ipw-section-post">
                <div class="ipw-post-header">
                    <span class="ipw-post-service-title"><u>INDIAN POST (<?php echo esc_html($service_display); ?>) 📭</u></span>
                </div>
                <div class="ipw-contract-line">
                    CUSTOMER ID - <?php echo esc_html($customer_id); ?> CONTRACT ID- <?php echo esc_html($active_contract_id); ?>
                </div>

                <!-- Existing India Post Barcode & Details Integration -->
                <div class="ipw-post-barcode-wrap">
                    <div class="ipw-barcode-graphic">
                        <?php echo $barcode_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <div class="ipw-barcode-subinfo">
                        <div class="ipw-dest-pin-box">
                            <span class="label">DEST PIN:</span>
                            <span class="pin-val"><?php echo esc_html($recipient_pin); ?></span>
                        </div>
                        <div class="ipw-wt-tariff">
                            <?php if (get_option('ipw_show_weight_on_label', 'no') === 'yes') : ?>
                                <span>WT: <?php echo esc_html($weight ?: get_option('ipw_default_weight', 250)); ?>g</span>
                            <?php endif; ?>
                            <span>ORD #<?php echo esc_html($order->get_order_number()); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Cash On Delivery Badge if Applicable -->
                <?php if ($is_cod && $cod_amount > 0) : ?>
                    <div class="ipw-cod-banner">
                        <span>CASH ON DELIVERY (C.O.D.) COLLECT: ₹<?php echo esc_html(number_format($cod_amount, 2)); ?></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="ipw-print-controls no-print">
            <button onclick="window.print()" class="btn-print">🖨️ Print Label</button>
            <button onclick="window.close()" class="btn-close">Close Window</button>
        </div>
    </div>
</body>
</html>
