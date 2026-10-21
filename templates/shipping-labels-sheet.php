<?php
/**
 * A6 Sheet Shipping Labels Template for India Post (4 Labels per A6 Sheet)
 * 2x2 Grid per A6 sheet (105mm x 148mm), custom engineered for Dhruva Publications & India Post standards.
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
    <title><?php echo esc_html(sprintf(__('India Post Shipping Labels - %d Labels (%d A6 Sheet%s)', 'india-post-woocommerce'), $total_labels, $total_sheets, $total_sheets > 1 ? 's' : '')); ?></title>
    <style>
        /* Exact A6 Sheet Specification (105mm x 148mm) */
        @page {
            size: 105mm 148mm;
            margin: 2mm;
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
            -webkit-font-smoothing: antialiased;
        }

        /* Screen Preview Toolbar */
        .ipw-sheet-toolbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-bottom: 2px solid #0f766e;
            padding: 8px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            z-index: 9999;
        }

        .ipw-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ipw-toolbar-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ipw-toolbar-badge {
            background: #e6fffa;
            color: #0d9488;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #99f6e4;
        }

        .ipw-toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-toolbar-print {
            background: #0f766e;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            font-size: 12px;
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

        .btn-toolbar-repeat {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-toolbar-repeat:hover {
            background: #dcfce7;
        }

        .btn-toolbar-toggle {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-toolbar-toggle:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-toolbar-close {
            background: #64748b;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-toolbar-close:hover {
            background: #475569;
        }

        /* Screen Preview Container */
        .ipw-sheets-container {
            padding: 20px 0 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }

        .ipw-sheet-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ipw-sheet-header-meta {
            width: 101mm;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* The A6 Sheet (101mm x 144mm inside 2mm page margin, 2x2 Grid) */
        .ipw-a6-sheet {
            width: 101mm;
            height: 144mm;
            max-height: 144mm;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            box-sizing: border-box;
            display: grid;
            grid-template-columns: calc(50% - 1mm) calc(50% - 1mm);
            grid-template-rows: calc(50% - 1mm) calc(50% - 1mm);
            gap: 2mm;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .ipw-a6-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* Individual Label Slot (49.5mm x 71mm) */
        .ipw-grid-label-slot {
            width: 100%;
            height: 100%;
            max-height: 71mm;
            box-sizing: border-box;
            border: 1px solid #000000;
            background: #ffffff;
            padding: 1.5mm 2mm;
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
            font-size: 9px;
            font-style: italic;
        }

        /* Sections Divider */
        .ipw-slot-section {
            padding-bottom: 1px;
            margin-bottom: 1px;
            border-bottom: 0.8px solid #000000;
        }

        .ipw-slot-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        /* SECTION 1: TO (Recipient) */
        .ipw-to-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1px;
        }

        .ipw-to-badge {
            background: #000000;
            color: #ffffff;
            font-size: 6.5px;
            font-weight: 900;
            padding: 0.5px 3px;
            border-radius: 2px;
            letter-spacing: 0.3px;
        }

        .ipw-to-phone {
            font-size: 6.5px;
            font-weight: 800;
            color: #000000;
        }

        .ipw-recipient-name {
            font-size: 8px;
            font-weight: 900;
            line-height: 1.1;
            margin: 0.5px 0 1px;
            color: #000000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0.1px;
        }

        .ipw-recipient-address {
            font-size: 6.5px;
            line-height: 1.15;
            color: #111111;
            max-height: 20px;
            overflow: hidden;
        }

        .ipw-city-pin {
            font-size: 7px;
            font-weight: 800;
            color: #000000;
            margin-top: 0.5px;
        }

        /* SECTION 2: FROM & LOGO */
        .ipw-section-from {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2px;
        }

        .ipw-from-left {
            flex: 1;
            min-width: 0;
        }

        .ipw-from-title {
            font-size: 6.5px;
            font-weight: 800;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ipw-from-address {
            font-size: 5.8px;
            line-height: 1.1;
            color: #333333;
        }

        .ipw-from-right {
            flex: 0 0 16mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .ipw-from-logo {
            max-height: 14px;
            max-width: 16mm;
            object-fit: contain;
            display: block;
        }

        /* SECTION 3: ITEM DETAIL TABLE */
        .ipw-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
            line-height: 1.1;
        }

        .ipw-items-table th {
            text-align: left;
            padding: 1px 2px;
            border-bottom: 0.8px solid #000000;
            font-weight: 800;
            background: #f1f5f9;
            font-size: 5.8px;
        }

        .ipw-items-table td {
            padding: 1px 2px;
            vertical-align: top;
            border-bottom: 0.5px solid #e2e8f0;
        }

        .col-sr { width: 12px; text-align: center; }
        .col-name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 22mm; }
        .col-hsn { width: 22px; text-align: center; }
        .col-qty { width: 24px; text-align: right; white-space: nowrap; }

        /* SECTION 4: INDIA POST DETAILS & BARCODE */
        .ipw-section-post {
            text-align: center;
        }

        .ipw-post-service-title {
            font-size: 6.5px;
            font-weight: 900;
            letter-spacing: 0.2px;
            text-decoration: underline;
            display: block;
            margin-bottom: 0.5px;
        }

        .ipw-contract-line {
            font-size: 5.5px;
            font-weight: 700;
            color: #222;
            margin-bottom: 1px;
            letter-spacing: 0.1px;
        }

        .ipw-barcode-wrap {
            text-align: center;
            margin: 0.5px 0;
            overflow: hidden;
        }

        .ipw-barcode-graphic svg {
            width: 44mm !important;
            max-width: 98% !important;
            height: auto !important;
            max-height: 11mm !important;
            display: block !important;
            margin: 0 auto !important;
        }

        .ipw-barcode-subinfo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1px;
            margin-top: 0.5px;
            border-top: 0.8px dashed #64748b;
            font-size: 6.5px;
            font-weight: 800;
        }

        .ipw-dest-pin-box {
            font-weight: 800;
        }

        .ipw-dest-pin-box .pin-val {
            font-size: 8px;
            font-weight: 900;
        }

        .ipw-ord-wt {
            font-weight: 700;
            font-size: 6.5px;
            text-align: right;
        }

        .ipw-cod-strip {
            background: #000000;
            color: #ffffff;
            padding: 1px 2px;
            font-size: 6px;
            font-weight: 900;
            text-align: center;
            margin-top: 1px;
            letter-spacing: 0.3px;
        }

        /* PRINT MEDIA STYLES - Strict A6 Sheet (105mm x 148mm) */
        @media print {
            @page {
                size: 105mm 148mm;
                margin: 2mm;
            }

            html, body {
                width: 101mm !important;
                height: 144mm !important;
                max-width: 101mm !important;
                max-height: 144mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
            }

            .ipw-sheet-toolbar,
            .ipw-sheet-header-meta,
            .no-print {
                display: none !important;
            }

            .ipw-sheets-container {
                padding: 0 !important;
                margin: 0 !important;
                gap: 0 !important;
                display: block !important;
            }

            .ipw-sheet-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .ipw-sheet-wrapper:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .ipw-a6-sheet {
                width: 101mm !important;
                height: 144mm !important;
                max-height: 144mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                display: grid !important;
                grid-template-columns: calc(50% - 1mm) calc(50% - 1mm) !important;
                grid-template-rows: calc(50% - 1mm) calc(50% - 1mm) !important;
                gap: 2mm !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .ipw-grid-label-slot {
                width: 100% !important;
                height: 100% !important;
                max-height: 71mm !important;
                border: 1px solid #000000 !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
            }

            .ipw-grid-label-slot.is-empty {
                border: 1px dashed #cbd5e1 !important;
                background: transparent !important;
                visibility: visible !important;
            }
        }
    </style>
</head>
<body onload="if(window.location.search.indexOf('noprint') === -1 && window.location.search.indexOf('test') === -1) { setTimeout(function(){ window.print(); }, 400); }">

    <!-- Screen Preview Controls Toolbar -->
    <div class="ipw-sheet-toolbar no-print">
        <div class="ipw-toolbar-left">
            <span class="ipw-toolbar-title">📄 <?php esc_html_e('India Post A6 Multi-Label Print (4 Labels per Sheet)', 'india-post-woocommerce'); ?></span>
            <span class="ipw-toolbar-badge" id="ipw-badge-status">
                <?php echo esc_html(sprintf(__('%d Label%s • %d A6 Sheet%s', 'india-post-woocommerce'), $total_labels, $total_labels > 1 ? 's' : '', $total_sheets, $total_sheets > 1 ? 's' : '')); ?>
            </span>
        </div>
        <div class="ipw-toolbar-right">
            <?php if ($total_labels < 4) : ?>
                <button type="button" onclick="toggleFillAllSlots()" class="btn-toolbar-repeat" id="btn-toggle-repeat" title="Fill all 4 label stickers on this A6 sheet with this order">
                    🔁 <span id="repeat-btn-text"><?php esc_html_e('Fill All 4 Slots', 'india-post-woocommerce'); ?></span>
                </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-toolbar-print">🖨️ <?php esc_html_e('Print A6 Sheet Now', 'india-post-woocommerce'); ?></button>
            <a href="<?php echo esc_url($thermal_url); ?>" class="btn-toolbar-toggle">🏷️ <?php esc_html_e('Switch to Single Thermal Roll', 'india-post-woocommerce'); ?></a>
            <button onclick="window.close()" class="btn-toolbar-close"><?php esc_html_e('Close Window', 'india-post-woocommerce'); ?></button>
        </div>
    </div>

    <!-- Container of A6 Sheets -->
    <div class="ipw-sheets-container">
        <?php foreach ($sheets as $sheet_idx => $chunk) : 
            $sheet_number = $sheet_idx + 1;
            $start_label = ($sheet_idx * 4) + 1;
            $end_label   = min($total_labels, ($sheet_idx + 1) * 4);
        ?>
            <div class="ipw-sheet-wrapper">
                <div class="ipw-sheet-header-meta no-print">
                    <span>📄 A6 Sheet <?php echo esc_html($sheet_number); ?> of <?php echo esc_html($total_sheets); ?></span>
                    <span>Labels <?php echo esc_html($start_label); ?> - <?php echo esc_html($end_label); ?> of <?php echo esc_html($total_labels); ?></span>
                </div>

                <div class="ipw-a6-sheet" data-sheet="<?php echo esc_attr($sheet_number); ?>">
                    <?php for ($slot_idx = 0; $slot_idx < 4; $slot_idx++) : ?>
                        <?php if (isset($chunk[$slot_idx])) : 
                            $lbl = $chunk[$slot_idx];
                            $order = $lbl['order'];
                            $items = $lbl['items'];
                        ?>
                            <!-- LABEL SLOT <?php echo esc_html($slot_idx + 1); ?>: ORDER #<?php echo esc_html($lbl['order_id']); ?> -->
                            <div class="ipw-grid-label-slot slot-filled" data-slot="<?php echo esc_attr($slot_idx); ?>">

                                <!-- SECTION 1: TO (RECIPIENT) -->
                                <div class="ipw-slot-section ipw-section-to">
                                    <div class="ipw-to-header">
                                        <span class="ipw-to-badge">TO</span>
                                        <span class="ipw-to-phone">PH: <?php echo esc_html($lbl['recipient_phone'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="ipw-recipient-name"><?php echo esc_html(strtoupper($lbl['recipient_name'])); ?></div>
                                    <div class="ipw-recipient-address">
                                        <div><?php echo esc_html(strtoupper($lbl['addr1'])); ?></div>
                                        <?php if (!empty($lbl['addr2'])) : ?>
                                            <div><?php echo esc_html(strtoupper($lbl['addr2'])); ?></div>
                                        <?php endif; ?>
                                        <div class="ipw-city-pin"><?php echo esc_html(strtoupper(trim("{$lbl['city']}, {$lbl['state']} - {$lbl['recipient_pin']}"))); ?></div>
                                    </div>
                                </div>

                                <!-- SECTION 2: FROM & LOGO -->
                                <div class="ipw-slot-section ipw-section-from">
                                    <div class="ipw-from-left">
                                        <div class="ipw-from-title">FROM: <strong><?php echo esc_html(strtoupper($lbl['sender_name'])); ?></strong></div>
                                        <div class="ipw-from-address">
                                            <div><?php echo esc_html(strtoupper(trim("{$lbl['s_city']}, {$lbl['s_state']} - {$lbl['s_pin']}"))); ?></div>
                                            <div>PH: <?php echo esc_html($lbl['s_phone']); ?></div>
                                        </div>
                                    </div>
                                    <div class="ipw-from-right">
                                        <?php if ($has_client_logo) : ?>
                                            <img src="<?php echo esc_url($client_logo_url); ?>" alt="Dhruva Publications" class="ipw-from-logo">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- SECTION 3: ITEM DETAIL TABLE -->
                                <div class="ipw-slot-section ipw-section-items">
                                    <table class="ipw-items-table">
                                        <thead>
                                            <tr>
                                                <th class="col-sr">SR</th>
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
                                                if ($item_count > 1) {
                                                    // Keep within 71mm boundary: show first item and summary of rest
                                                    echo '<tr><td colspan="4" style="text-align:center; font-style:italic; font-size:5.5px; padding:0.5px;">+ ' . esc_html(count($items) - 1) . ' more item(s) in order</td></tr>';
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
                                <div class="ipw-slot-section ipw-section-post">
                                    <span class="ipw-post-service-title">INDIAN POST (<?php echo esc_html($lbl['service_display']); ?>)</span>
                                    <div class="ipw-contract-line">
                                        CUSTOMER ID - <?php echo esc_html($lbl['customer_id']); ?> CONTRACT ID- <?php echo esc_html($lbl['active_contract_id']); ?>
                                    </div>

                                    <div class="ipw-barcode-wrap">
                                        <div class="ipw-barcode-graphic">
                                            <?php echo $lbl['barcode_svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </div>
                                        <div class="ipw-barcode-subinfo">
                                            <div class="ipw-dest-pin-box">
                                                <span>DEST PIN: </span>
                                                <span class="pin-val"><?php echo esc_html($lbl['recipient_pin']); ?></span>
                                            </div>
                                            <div class="ipw-ord-wt">
                                                <?php if ($show_weight) : ?>
                                                    <span>WT: <?php echo esc_html($lbl['weight'] ?: get_option('ipw_default_weight', 250)); ?>g • </span>
                                                <?php endif; ?>
                                                <span>ORD #<?php echo esc_html($order->get_order_number()); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($lbl['is_cod'] && $lbl['cod_amount'] > 0) : ?>
                                        <div class="ipw-cod-strip">
                                            C.O.D. COLLECT: ₹<?php echo esc_html(number_format($lbl['cod_amount'], 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php else : ?>
                            <!-- EMPTY SLOT (To maintain 2x2 grid geometry) -->
                            <div class="ipw-grid-label-slot is-empty" data-slot="<?php echo esc_attr($slot_idx); ?>">
                                <span>[ Empty Slot ]</span>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        var isFilled = false;
        var originalEmptySlotsHtml = [];

        function toggleFillAllSlots() {
            var firstSlot = document.querySelector('.ipw-grid-label-slot.slot-filled');
            if (!firstSlot) return;

            var emptySlots = document.querySelectorAll('.ipw-grid-label-slot.is-empty, .ipw-grid-label-slot.slot-cloned');
            var btnText = document.getElementById('repeat-btn-text');
            var badge = document.getElementById('ipw-badge-status');

            if (!isFilled) {
                // Save original empty slot state
                originalEmptySlotsHtml = [];
                emptySlots.forEach(function(slot) {
                    originalEmptySlotsHtml.push(slot.innerHTML);
                    slot.innerHTML = firstSlot.innerHTML;
                    slot.classList.remove('is-empty');
                    slot.classList.add('slot-cloned');
                });
                isFilled = true;
                if (btnText) btnText.textContent = 'Revert to Single Slot';
                if (badge) badge.textContent = '4 Labels (Filled Sheet) • 1 A6 Sheet';
            } else {
                // Revert to empty
                emptySlots.forEach(function(slot, idx) {
                    slot.innerHTML = originalEmptySlotsHtml[idx] || '<span>[ Empty Slot ]</span>';
                    slot.classList.add('is-empty');
                    slot.classList.remove('slot-cloned');
                });
                isFilled = false;
                if (btnText) btnText.textContent = 'Fill All 4 Slots';
                if (badge) badge.textContent = '1 Label • 1 A6 Sheet';
            }
        }
    </script>
</body>
</html>
