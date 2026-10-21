<?php
/**
 * A4 Sheet Shipping Labels Template for India Post (4 A6 Labels per Sheet)
 * 2x2 Grid per A4 sheet, custom styled for Dhruva Publications & India Post UPU standards.
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
$sheets = array_chunk($labels_data, 4);
$total_sheets = count($sheets);
$order_ids_csv = implode(',', array_column($labels_data, 'order_id'));

$thermal_url = admin_url('admin.php?action=ipw_print_bulk_labels&order_ids=' . urlencode($order_ids_csv) . '&format=thermal');
$show_weight = get_option('ipw_show_weight_on_label', 'no') === 'yes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(sprintf(__('India Post Shipping Labels - %d Labels (%d Sheet%s)', 'india-post-woocommerce'), $total_labels, $total_sheets, $total_sheets > 1 ? 's' : '')); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/css/shipping-label.css'); ?>?ver=<?php echo IPW_VERSION; ?>">
    <style>
        /* A4 Page Specification */
        @page {
            size: A4 portrait;
            margin: 4mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background-color: #f1f5f9;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Screen Preview Toolbar */
        .ipw-sheet-toolbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-bottom: 2px solid #0f766e;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            z-index: 9999;
        }

        .ipw-toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ipw-toolbar-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f766e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ipw-toolbar-badge {
            background: #e6fffa;
            color: #0d9488;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid #99f6e4;
        }

        .ipw-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-toolbar-print {
            background: #0f766e;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(15, 118, 110, 0.3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-toolbar-print:hover {
            background: #115e59;
        }

        .btn-toolbar-toggle {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-toolbar-toggle:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-toolbar-close {
            background: #64748b;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-toolbar-close:hover {
            background: #475569;
        }

        /* Screen Sheet Container */
        .ipw-sheets-container {
            padding: 24px 0 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }

        .ipw-sheet-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ipw-sheet-header-meta {
            width: 202mm;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* The A4 Sheet (2x2 Grid) */
        .ipw-a4-sheet {
            width: 202mm;
            height: 288mm;
            max-height: 288mm;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            box-sizing: border-box;
            padding: 2mm;
            display: grid;
            grid-template-columns: 98mm 98mm;
            grid-template-rows: 141mm 141mm;
            gap: 2mm;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .ipw-a4-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* Individual A6 Label Cell inside Grid */
        .ipw-grid-label-slot {
            width: 98mm;
            height: 141mm;
            max-height: 141mm;
            box-sizing: border-box;
            border: 1.5px solid #000000;
            background: #ffffff;
            padding: 5px 7px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .ipw-grid-label-slot.is-empty {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 11px;
            font-style: italic;
        }

        /* Scaled A6 Label Content */
        .ipw-grid-label-slot .ipw-section {
            padding: 3px 0;
            border-bottom: 1.5px solid #000;
        }

        .ipw-grid-label-slot .ipw-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .ipw-grid-label-slot .ipw-sec-title {
            font-size: 10px;
            font-weight: 800;
            line-height: 1.1;
        }

        /* SECTION 1: TO */
        .ipw-grid-label-slot .ipw-recipient-name {
            font-size: 12px;
            font-weight: 800;
            margin: 1px 0 2px;
            line-height: 1.15;
            letter-spacing: 0.2px;
        }

        .ipw-grid-label-slot .ipw-address-lines {
            font-size: 9px;
            line-height: 1.25;
            color: #111;
        }

        .ipw-grid-label-slot .ipw-contact-row {
            font-size: 8.5px;
            font-weight: 700;
            margin-top: 2px;
            line-height: 1.2;
            display: flex;
            justify-content: space-between;
        }

        /* SECTION 2: FROM */
        .ipw-grid-label-slot .ipw-section-from {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 6px;
        }

        .ipw-grid-label-slot .ipw-from-left {
            flex: 1;
        }

        .ipw-grid-label-slot .ipw-sender-name {
            font-size: 10px;
            font-weight: 800;
            margin: 1px 0;
        }

        .ipw-grid-label-slot .ipw-sender-address {
            font-size: 8px;
            line-height: 1.25;
        }

        .ipw-grid-label-slot .ipw-from-right {
            flex: 0 0 34%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ipw-grid-label-slot .ipw-client-logo-box {
            border: 1px solid #94a3b8;
            padding: 3px;
            border-radius: 2px;
            background: #ffffff;
            width: 100%;
            height: 100%;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ipw-grid-label-slot .ipw-client-logo-img {
            max-height: 38px;
            max-width: 100%;
            object-fit: contain;
            display: block;
        }

        /* SECTION 3: ITEM DETAIL TABLE */
        .ipw-grid-label-slot .ipw-items-header {
            font-size: 9px;
            font-weight: 800;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .ipw-grid-label-slot .ipw-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            line-height: 1.15;
        }

        .ipw-grid-label-slot .ipw-items-table th {
            text-align: left;
            padding: 2px 3px;
            border-bottom: 1px solid #000;
            font-weight: 800;
            background: #f1f5f9;
            font-size: 7.5px;
        }

        .ipw-grid-label-slot .ipw-items-table td {
            padding: 2px 3px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .ipw-grid-label-slot .col-sr { width: 22px; text-align: center; }
        .ipw-grid-label-slot .col-name { width: 55%; font-weight: 700; }
        .ipw-grid-label-slot .col-hsn { width: 36px; text-align: center; }
        .ipw-grid-label-slot .col-qty { width: 44px; text-align: right; white-space: nowrap; }

        /* SECTION 4: INDIA POST & BARCODE */
        .ipw-grid-label-slot .ipw-section-post {
            padding-bottom: 1px;
        }

        .ipw-grid-label-slot .ipw-post-header {
            text-align: center;
        }

        .ipw-grid-label-slot .ipw-post-service-title {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .ipw-grid-label-slot .ipw-contract-line {
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            margin: 1px 0 2px;
        }

        .ipw-grid-label-slot .ipw-post-barcode-wrap {
            text-align: center;
            margin-top: 1px;
        }

        .ipw-grid-label-slot .ipw-barcode-graphic svg {
            max-width: 100%;
            height: 32px !important;
            display: inline-block;
        }

        .ipw-grid-label-slot .ipw-barcode-subinfo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2px;
            margin-top: 2px;
            border-top: 1px dashed #64748b;
            font-size: 8.5px;
        }

        .ipw-grid-label-slot .ipw-dest-pin-box {
            font-weight: 800;
        }

        .ipw-grid-label-slot .ipw-dest-pin-box .pin-val {
            font-size: 11px;
            font-weight: 900;
        }

        .ipw-grid-label-slot .ipw-wt-tariff {
            font-weight: 700;
            text-align: right;
            font-size: 8px;
        }

        .ipw-grid-label-slot .ipw-cod-banner {
            background: #000;
            color: #fff;
            padding: 2px 4px;
            font-size: 8.5px;
            font-weight: 900;
            text-align: center;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }

        /* PRINT MEDIA STYLES - Strict A4 sheets */
        @media print {
            body {
                background: #ffffff !important;
            }

            .ipw-sheet-toolbar,
            .ipw-sheet-header-meta,
            .no-print {
                display: none !important;
            }

            .ipw-sheets-container {
                padding: 0 !important;
                gap: 0 !important;
            }

            .ipw-sheet-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .ipw-a4-sheet {
                width: 202mm !important;
                height: 288mm !important;
                max-height: 288mm !important;
                margin: 0 auto !important;
                padding: 1.5mm !important;
                border: none !important;
                box-shadow: none !important;
                display: grid !important;
                grid-template-columns: 98mm 98mm !important;
                grid-template-rows: 141mm 141mm !important;
                gap: 2mm !important;
                page-break-after: always !important;
                break-after: page !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
            }

            .ipw-a4-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .ipw-grid-label-slot {
                width: 98mm !important;
                height: 141mm !important;
                max-height: 141mm !important;
                border: 1.5px solid #000 !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
            }

            .ipw-grid-label-slot.is-empty {
                border: none !important;
                background: transparent !important;
                visibility: hidden !important;
            }
        }
    </style>
</head>
<body onload="if(window.location.search.indexOf('noprint') === -1 && window.location.search.indexOf('test') === -1) { setTimeout(function(){ window.print(); }, 400); }">

    <!-- Screen Preview Controls Toolbar -->
    <div class="ipw-sheet-toolbar no-print">
        <div class="ipw-toolbar-left">
            <span class="ipw-toolbar-title">📄 <?php esc_html_e('India Post Multi-Label Print (A4 Sheet - 4 Labels per Page)', 'india-post-woocommerce'); ?></span>
            <span class="ipw-toolbar-badge">
                <?php echo esc_html(sprintf(__('%d Labels • %d A4 Sheet(s)', 'india-post-woocommerce'), $total_labels, $total_sheets)); ?>
            </span>
        </div>
        <div class="ipw-toolbar-right">
            <button onclick="window.print()" class="btn-toolbar-print">🖨️ <?php esc_html_e('Print Sheets Now', 'india-post-woocommerce'); ?></button>
            <a href="<?php echo esc_url($thermal_url); ?>" class="btn-toolbar-toggle">🏷️ <?php esc_html_e('Switch to Thermal Roll Format', 'india-post-woocommerce'); ?></a>
            <button onclick="window.close()" class="btn-toolbar-close"><?php esc_html_e('Close Window', 'india-post-woocommerce'); ?></button>
        </div>
    </div>

    <!-- Container of Sheets -->
    <div class="ipw-sheets-container">
        <?php foreach ($sheets as $sheet_idx => $chunk) : 
            $sheet_number = $sheet_idx + 1;
            $start_label = ($sheet_idx * 4) + 1;
            $end_label   = min($total_labels, ($sheet_idx + 1) * 4);
        ?>
            <div class="ipw-sheet-wrapper">
                <div class="ipw-sheet-header-meta no-print">
                    <span>📄 Sheet <?php echo esc_html($sheet_number); ?> of <?php echo esc_html($total_sheets); ?></span>
                    <span>Labels <?php echo esc_html($start_label); ?> - <?php echo esc_html($end_label); ?> of <?php echo esc_html($total_labels); ?></span>
                </div>

                <div class="ipw-a4-sheet" data-sheet="<?php echo esc_attr($sheet_number); ?>">
                    <?php for ($slot_idx = 0; $slot_idx < 4; $slot_idx++) : ?>
                        <?php if (isset($chunk[$slot_idx])) : 
                            $lbl = $chunk[$slot_idx];
                            $order = $lbl['order'];
                            $items = $lbl['items'];
                        ?>
                            <!-- LABEL SLOT <?php echo esc_html($slot_idx + 1); ?>: ORDER #<?php echo esc_html($lbl['order_id']); ?> -->
                            <div class="ipw-grid-label-slot">

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
                                        <span>CONTACT: <?php echo esc_html(strtoupper($lbl['recipient_name'])); ?></span>
                                        <span>PHONE: <?php echo esc_html($lbl['recipient_phone'] ?: 'N/A'); ?></span>
                                    </div>
                                </div>

                                <!-- SECTION 2: FROM & LOGO -->
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
                                            <div>PHONE: <?php echo esc_html($lbl['s_phone']); ?></div>
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
                                                <th class="col-sr">SR.</th>
                                                <th class="col-name">NAME OF PRODUCT / SERVICE</th>
                                                <th class="col-hsn">HSN</th>
                                                <th class="col-qty">QTY</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sr = 1;
                                            $default_hsn = get_option('ipw_default_hsn', '4901');
                                            $item_count = 0;
                                            foreach ($items as $item_id => $item) : 
                                                $item_count++;
                                                if ($item_count > 2) {
                                                    // Summarize excess items to keep within A6 boundary
                                                    echo '<tr><td colspan="4" style="text-align:center; font-style:italic; font-size:7.5px; padding:1px;">+ ' . esc_html(count($items) - 2) . ' more item(s) in order #' . esc_html($order->get_order_number()) . '</td></tr>';
                                                    break;
                                                }
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
                                                    <td class="col-name"><?php echo esc_html(strtoupper($item->get_name())); ?></td>
                                                    <td class="col-hsn"><?php echo esc_html($hsn); ?></td>
                                                    <td class="col-qty"><?php echo esc_html(number_format($qty, 0)); ?> NOS</td>
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
                                                <span>DEST PIN: </span>
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
                        <?php else : ?>
                            <!-- EMPTY SLOT (To maintain 2x2 grid geometry) -->
                            <div class="ipw-grid-label-slot is-empty">
                                <span>[ Empty Slot ]</span>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
