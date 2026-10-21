<?php
/**
 * Multi-Order Thermal 4x6 / A6 Shipping Labels Template for India Post
 * Continuous 1-per-page thermal format with page breaks.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$client_logo_path = IPW_PLUGIN_PATH . 'assets/img/DHRUVA_PUBLICATIONS.png';
$client_logo_url  = IPW_PLUGIN_URL . 'assets/img/DHRUVA_PUBLICATIONS.png';
$has_client_logo  = file_exists($client_logo_path);

$total_labels = count($labels_data);
$order_ids_csv = implode(',', array_column($labels_data, 'order_id'));
$sheet_url = admin_url('admin.php?action=ipw_print_bulk_labels&order_ids=' . urlencode($order_ids_csv) . '&format=sheet');
$show_weight = get_option('ipw_show_weight_on_label', 'no') === 'yes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(sprintf(__('India Post Thermal Labels - %d Labels', 'india-post-woocommerce'), $total_labels)); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/css/shipping-label.css'); ?>?ver=<?php echo IPW_VERSION; ?>">
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        body {
            background-color: #f1f5f9;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .ipw-sheet-toolbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-bottom: 2px solid #0052cc;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            z-index: 9999;
        }

        .ipw-thermal-multi-wrap {
            padding: 20px 0 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .ipw-label-page {
            width: 100mm;
            height: 150mm;
            max-height: 150mm;
            background: #fff;
            border: 2px solid #000;
            border-radius: 4px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .ipw-label-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        @media print {
            body {
                background: #ffffff !important;
            }

            .ipw-sheet-toolbar, .no-print {
                display: none !important;
            }

            .ipw-thermal-multi-wrap {
                padding: 0 !important;
                gap: 0 !important;
            }

            .ipw-label-page {
                width: 96mm !important;
                height: 146mm !important;
                max-height: 146mm !important;
                margin: 0 !important;
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: always !important;
                break-after: page !important;
                box-sizing: border-box !important;
            }

            .ipw-label-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
    </style>
</head>
<body onload="if(window.location.search.indexOf('noprint') === -1 && window.location.search.indexOf('test') === -1) { setTimeout(function(){ window.print(); }, 400); }">

    <!-- Screen Preview Controls Toolbar -->
    <div class="ipw-sheet-toolbar no-print">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-size:15px; font-weight:800; color:#0052cc;">🏷️ <?php esc_html_e('India Post Thermal Roll Labels (1 per Page)', 'india-post-woocommerce'); ?></span>
            <span style="background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:700; padding:3px 10px; border-radius:999px; border:1px solid #bfdbfe;">
                <?php echo esc_html(sprintf(__('%d Labels Total', 'india-post-woocommerce'), $total_labels)); ?>
            </span>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button onclick="window.print()" style="background:#0052cc; color:#fff; border:none; padding:8px 18px; font-size:13px; font-weight:700; border-radius:6px; cursor:pointer;">🖨️ <?php esc_html_e('Print Labels Now', 'india-post-woocommerce'); ?></button>
            <a href="<?php echo esc_url($sheet_url); ?>" style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; padding:7px 14px; font-size:12px; font-weight:700; border-radius:6px; text-decoration:none;">📄 <?php esc_html_e('Switch to A6 Sheet (4 per Page)', 'india-post-woocommerce'); ?></a>
            <button onclick="window.close()" style="background:#64748b; color:#fff; border:none; padding:7px 14px; font-size:12px; font-weight:700; border-radius:6px; cursor:pointer;"><?php esc_html_e('Close', 'india-post-woocommerce'); ?></button>
        </div>
    </div>

    <div class="ipw-thermal-multi-wrap">
        <?php foreach ($labels_data as $lbl) : 
            $order = $lbl['order'];
            $items = $lbl['items'];
        ?>
            <div class="ipw-label-page">
                <!-- SECTION 1: TO (RECIPIENT) -->
                <div class="ipw-section ipw-section-to">
                    <div class="ipw-sec-title">TO</div>
                    <div class="ipw-recipient-name"><?php echo esc_html(strtoupper($lbl['recipient_name'])); ?></div>
                    <div class="ipw-address-lines">
                        <div><?php echo esc_html(strtoupper($lbl['addr1'])); ?></div>
                        <?php if (!empty($lbl['addr2'])) : ?>
                            <div><?php echo esc_html(strtoupper($lbl['addr2'])); ?></div>
                        <?php endif; ?>
                        <div><?php echo esc_html(strtoupper(trim("{$lbl['city']} {$lbl['state']} {$lbl['recipient_pin']}"))); ?></div>
                    </div>
                    <div class="ipw-contact-row">
                        <div>CONTACT PERSON :<?php echo esc_html(strtoupper($lbl['recipient_name'])); ?></div>
                        <div>PHONE :<?php echo esc_html($lbl['recipient_phone'] ?: 'N/A'); ?></div>
                    </div>
                </div>

                <!-- SECTION 2: FROM (SENDER) & CLIENT LOGO -->
                <div class="ipw-section ipw-section-from">
                    <div class="ipw-from-left">
                        <div class="ipw-sec-title">FROM</div>
                        <div class="ipw-sender-name"><?php echo esc_html(strtoupper($lbl['sender_name'])); ?></div>
                        <div class="ipw-sender-address">
                            <div><?php echo esc_html(strtoupper($lbl['s_line1'])); ?></div>
                            <?php if (!empty($lbl['s_line2'])) : ?>
                                <div><?php echo esc_html(strtoupper($lbl['s_line2'])); ?></div>
                            <?php endif; ?>
                            <div><?php echo esc_html(strtoupper(trim("{$lbl['s_city']}, {$lbl['s_state']} - {$lbl['s_pin']}"))); ?></div>
                            <div>PHONE :<?php echo esc_html($lbl['s_phone']); ?></div>
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
                        <span class="ipw-post-service-title"><u>INDIAN POST (<?php echo esc_html($lbl['service_display']); ?>) 📭</u></span>
                    </div>
                    <div class="ipw-contract-line">
                        CUSTOMER ID - <?php echo esc_html($lbl['customer_id']); ?> CONTRACT ID- <?php echo esc_html($lbl['active_contract_id']); ?>
                    </div>

                    <div class="ipw-post-barcode-wrap">
                        <div class="ipw-barcode-graphic">
                            <?php echo $lbl['barcode_svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <div class="ipw-barcode-subinfo">
                            <div class="ipw-dest-pin-box">
                                <span class="label">DEST PIN:</span>
                                <span class="pin-val"><?php echo esc_html($lbl['recipient_pin']); ?></span>
                            </div>
                            <div class="ipw-wt-tariff">
                                <?php if ($show_weight) : ?>
                                    <span>WT: <?php echo esc_html($lbl['weight'] ?: get_option('ipw_default_weight', 250)); ?>g</span>
                                <?php endif; ?>
                                <span>ORD #<?php echo esc_html($order->get_order_number()); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($lbl['is_cod'] && $lbl['cod_amount'] > 0) : ?>
                        <div class="ipw-cod-banner">
                            <span>CASH ON DELIVERY (C.O.D.) COLLECT: ₹<?php echo esc_html(number_format($lbl['cod_amount'], 2)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
