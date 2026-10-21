<?php
/**
 * Department of Posts Self-Service Portal Themed Admin Dashboard
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$env = get_option('ipw_environment', 'sandbox');
$server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field($_SERVER['LOCAL_ADDR']) : 'Whitelisting Required');
$token = get_transient(IPW_API_Client::TOKEN_TRANSIENT);
$token_status = !empty($token) ? __('Active (Cached)', 'india-post-woocommerce') : __('Needs Generation / Test', 'india-post-woocommerce');
$token_class  = !empty($token) ? 'status-active' : 'status-pending';
?>
<div class="wrap ipw-portal-container">
    <!-- Top Government Header Banner -->
    <header class="ipw-portal-header">
        <div class="ipw-header-top">
            <div class="ipw-gov-title-block">
                <div class="ipw-emblem-badge" style="max-height:48px; max-width:140px; display:flex; align-items:center;">
                    <img src="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/img/indiapost.png'); ?>" alt="India Post" class="ipw-main-logo" style="height:46px !important; max-height:46px !important; width:auto !important; max-width:130px !important; object-fit:contain !important; display:block !important;">
                </div>
                <div class="ipw-gov-text">
                    <h1>Department of Posts</h1>
                    <h2>Ministry of Communications, Government of India</h2>
                </div>
            </div>
            <div class="ipw-user-profile-block">
                <div class="ipw-avatar-circle">DP</div>
                <div class="ipw-welcome-text">
                    <span>Welcome,</span>
                    <strong><?php echo esc_html(get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS')); ?></strong>
                </div>
            </div>
        </div>
        <div class="ipw-portal-banner-tag">
            <span>CUSTOMER SELFSERVICE PORTAL</span>
        </div>
    </header>

    <!-- Main Portal Frame: Red Sidebar + Content Workspace -->
    <div class="ipw-portal-frame">
        <!-- India Post Deep Red Sidebar -->
        <aside class="ipw-portal-sidebar">
            <div class="ipw-sidebar-brand">
                <span class="ipw-menu-icon">☰</span>
                <span class="ipw-brand-title">Dashboard</span>
            </div>

            <nav class="ipw-sidebar-nav">
                <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-dashboard')); ?>" class="ipw-nav-item active">
                    <span class="ipw-nav-icon">🏠</span>
                    <span class="ipw-nav-text">Dashboard</span>
                </a>

                <div class="ipw-nav-heading">SERVICES</div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-shipments')); ?>" class="ipw-nav-item">
                    <span class="ipw-nav-icon">📦</span>
                    <span class="ipw-nav-text">Shipments & Tracking</span>
                </a>

                <div class="ipw-nav-heading">TOOLS</div>
                <a href="#pincode-tool" class="ipw-nav-item" id="ipw-sidebar-pincode-link">
                    <span class="ipw-nav-icon">📍</span>
                    <span class="ipw-nav-text">PIN Code Lookup</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-settings#awb-series')); ?>" class="ipw-nav-item">
                    <span class="ipw-nav-icon">🏷️</span>
                    <span class="ipw-nav-text">AWB Barcode Series</span>
                </a>

                <div class="ipw-nav-heading">CONFIGURATION</div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-settings')); ?>" class="ipw-nav-item">
                    <span class="ipw-nav-icon">⚙️</span>
                    <span class="ipw-nav-text">API Settings</span>
                </a>

                <div class="ipw-nav-heading">HELP DESK</div>
                <a href="mailto:integrations.cept@indiapost.gov.in" class="ipw-nav-item">
                    <span class="ipw-nav-icon">🎧</span>
                    <span class="ipw-nav-text">CEPT Support</span>
                </a>
            </nav>
        </aside>

        <!-- Right Portal Content Area -->
        <main class="ipw-portal-content">
            <!-- Connection Status Strip -->
            <div class="ipw-status-banner">
                <div class="ipw-status-col">
                    <span class="label">API ENVIRONMENT:</span>
                    <span class="val badge-env-<?php echo esc_attr($env); ?>"><?php echo esc_html(strtoupper($env)); ?></span>
                </div>
                <div class="ipw-status-col">
                    <span class="label">BEARER TOKEN:</span>
                    <span class="val <?php echo esc_attr($token_class); ?>"><?php echo esc_html($token_status); ?></span>
                </div>
                <div class="ipw-status-col">
                    <span class="label">CUSTOMER ID:</span>
                    <span class="val font-mono"><?php echo esc_html(get_option('ipw_customer_id', '3000064781')); ?></span>
                </div>
                <div class="ipw-status-col">
                    <span class="label">SERVER IP (FOR WHITELIST):</span>
                    <span class="val font-mono" id="ipw-server-ip"><?php echo esc_html($server_ip); ?></span>
                    <button type="button" class="ipw-copy-ip-btn" id="ipw-server-ip-copy" data-copy-val="<?php echo esc_attr($server_ip); ?>">Copy</button>
                </div>
                <div class="ipw-status-action">
                    <button type="button" class="button button-secondary" id="ipw-btn-test-connection">
                        ⚡ <?php esc_html_e('Test Connection', 'india-post-woocommerce'); ?>
                    </button>
                </div>
            </div>

            <!-- Inline Test Connection Feedback Container (NO alert popup) -->
            <div id="ipw-connection-test-result" style="display:none; margin-bottom:20px;"></div>

            <!-- Portal Card Grid (Inspired by the Technical Integration Portal Screenshot) -->
            <div class="ipw-cards-grid">
                <!-- Card 1: Registration Status -->
                <div class="ipw-portal-card">
                    <div class="ipw-card-top">
                        <span class="ipw-card-icon-circle">ⓘ</span>
                        <div class="ipw-card-content">
                            <h3>Registration for Technical Integration</h3>
                            <p>Status: Registered in <?php echo esc_html(ucfirst($env)); ?> with CEPT India Post.</p>
                        </div>
                    </div>
                    <div class="ipw-card-bottom">
                        <span class="status-text-pill status-ready">Configured</span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-settings')); ?>" class="ipw-card-arrow">&rarr;</a>
                    </div>
                </div>

                <!-- Card 2: Select APIs -->
                <div class="ipw-portal-card">
                    <div class="ipw-card-top">
                        <span class="ipw-card-icon-circle">ⓘ</span>
                        <div class="ipw-card-content">
                            <h3>Subscribed Business APIs</h3>
                            <p>Speed Post, Business Parcel, Bulk Booking, Labels & Tracking.</p>
                        </div>
                    </div>
                    <div class="ipw-card-bottom">
                        <span class="status-text-pill status-ready">5 Endpoints Active</span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-settings')); ?>" class="ipw-card-arrow">&rarr;</a>
                    </div>
                </div>

                <!-- Card 3: Whitelist IP -->
                <div class="ipw-portal-card">
                    <div class="ipw-card-top">
                        <span class="ipw-card-icon-circle">ⓘ</span>
                        <div class="ipw-card-content">
                            <h3>Whitelist Server IP Address</h3>
                            <p>Submit <code><?php echo esc_html($server_ip); ?></code> to integrations.cept@indiapost.gov.in</p>
                        </div>
                    </div>
                    <div class="ipw-card-bottom">
                        <span class="status-text-pill status-pending">Whitelisting Helper</span>
                        <a href="mailto:integrations.cept@indiapost.gov.in?subject=IP%20Whitelisting%20Request%20-%20Dhruva%20Publications&body=Dear%20CEPT%20Team,%0A%0APlease%20whitelist%20our%20server%20IP:%20<?php echo esc_attr($server_ip); ?>%20for%20Customer%20ID:%20<?php echo esc_attr(get_option('ipw_customer_id', '3000064781')); ?>.%0A%0AThank%20you." class="ipw-card-arrow" target="_blank">&rarr;</a>
                    </div>
                </div>

                <!-- Card 4: Production Env -->
                <div class="ipw-portal-card">
                    <div class="ipw-card-top">
                        <span class="ipw-card-icon-circle">ⓘ</span>
                        <div class="ipw-card-content">
                            <h3>Production Environment Access</h3>
                            <p>Switch between Sandbox and Production anytime after UAT sign-off.</p>
                        </div>
                    </div>
                    <div class="ipw-card-bottom">
                        <span class="status-text-pill <?php echo $env === 'production' ? 'status-ready' : 'status-sandbox'; ?>"><?php echo esc_html(ucfirst($env)); ?> Mode</span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-settings')); ?>" class="ipw-card-arrow">&rarr;</a>
                    </div>
                </div>

                <!-- Card 5: Logs & Sync -->
                <div class="ipw-portal-card">
                    <div class="ipw-card-top">
                        <span class="ipw-card-icon-circle">ⓘ</span>
                        <div class="ipw-card-content">
                            <h3>Logs and Live Tracking</h3>
                            <p>Bulk tracking sync and shipment event monitoring.</p>
                        </div>
                    </div>
                    <div class="ipw-card-bottom">
                        <button type="button" class="ipw-btn-inline-sync" id="ipw-btn-bulk-sync">🔄 Sync In-Transit Consignments</button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-shipments')); ?>" class="ipw-card-arrow">&rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Shipment Analytics Counters -->
            <div class="ipw-metrics-strip">
                <div class="ipw-metric-card">
                    <div class="metric-val"><?php echo esc_html($total_shipments); ?></div>
                    <div class="metric-label"><?php esc_html_e('Total Consignments Booked', 'india-post-woocommerce'); ?></div>
                </div>
                <div class="ipw-metric-card metric-transit">
                    <div class="metric-val"><?php echo esc_html($in_transit); ?></div>
                    <div class="metric-label"><?php esc_html_e('In Transit / Processing', 'india-post-woocommerce'); ?></div>
                </div>
                <div class="ipw-metric-card metric-ofd">
                    <div class="metric-val"><?php echo esc_html($out_for_delivery); ?></div>
                    <div class="metric-label"><?php esc_html_e('Out For Delivery', 'india-post-woocommerce'); ?></div>
                </div>
                <div class="ipw-metric-card metric-delivered">
                    <div class="metric-val"><?php echo esc_html($delivered); ?></div>
                    <div class="metric-label"><?php esc_html_e('Successfully Delivered', 'india-post-woocommerce'); ?></div>
                </div>
            </div>

            <!-- Fast Tools: PIN Code Search Modal / Widget -->
            <div class="ipw-widget-section" id="pincode-tool">
                <div class="ipw-widget-header">
                    <h3>📍 PIN Code & Post Office Lookup (Official India Post API)</h3>
                </div>
                <div class="ipw-widget-body">
                    <div class="ipw-pincode-form">
                        <input type="text" id="ipw-quick-pin-input" placeholder="Enter 6-digit PIN code (e.g. 570001 / 560001)" maxlength="6">
                        <button type="button" class="button button-primary" id="ipw-btn-lookup-pin">Validate & Fetch Post Offices</button>
                    </div>
                    <div id="ipw-pincode-results" style="display:none; margin-top:14px;"></div>
                </div>
            </div>

            <!-- Section 1: Orders Pending Booking -->
            <div class="ipw-table-section">
                <div class="ipw-section-header">
                    <h3>📦 WooCommerce Orders Ready for India Post Booking</h3>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-orders')); ?>" class="button button-small"><?php esc_html_e('View All Orders', 'india-post-woocommerce'); ?></a>
                </div>

                <?php if (!empty($unbooked_orders)) : ?>
                    <table class="wp-list-table widefat fixed striped ipw-admin-table">
                        <thead>
                            <tr>
                                <th style="width:100px;"><?php esc_html_e('Order', 'india-post-woocommerce'); ?></th>
                                <th><?php esc_html_e('Customer / Recipient', 'india-post-woocommerce'); ?></th>
                                <th><?php esc_html_e('Destination PIN', 'india-post-woocommerce'); ?></th>
                                <th><?php esc_html_e('Order Total', 'india-post-woocommerce'); ?></th>
                                <th><?php esc_html_e('Payment', 'india-post-woocommerce'); ?></th>
                                <th style="width:180px;"><?php esc_html_e('Quick Action', 'india-post-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unbooked_orders as $ord) : ?>
                                <tr>
                                    <td><strong><a href="<?php echo esc_url($ord->get_edit_order_url()); ?>">#<?php echo esc_html($ord->get_order_number()); ?></a></strong></td>
                                    <td>
                                        <strong><?php echo esc_html($ord->get_formatted_shipping_full_name() ?: $ord->get_formatted_billing_full_name()); ?></strong>
                                        <div style="font-size:11px; color:#666;"><?php echo esc_html($ord->get_shipping_city() ?: $ord->get_billing_city()); ?></div>
                                    </td>
                                    <td><span class="ipw-pin-tag"><?php echo esc_html($ord->get_shipping_postcode() ?: $ord->get_billing_postcode()); ?></span></td>
                                    <td><?php echo $ord->get_formatted_order_total(); ?></td>
                                    <td>
                                        <?php if ($ord->get_payment_method() === 'cod') : ?>
                                            <span class="ipw-cod-badge">COD</span>
                                        <?php else : ?>
                                            <span>Prepaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($ord->get_edit_order_url()); ?>" class="button button-primary button-small">
                                            📦 <?php esc_html_e('Book Shipment', 'india-post-woocommerce'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p style="padding:15px; color:#64748b; font-style:italic;">
                        <?php esc_html_e('All current WooCommerce orders have been booked with India Post!', 'india-post-woocommerce'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Section 2: Recent Consignments -->
            <div class="ipw-table-section">
                <div class="ipw-section-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <h3 style="margin:0;">🚚 <?php esc_html_e('Recent Booked Consignments', 'india-post-woocommerce'); ?></h3>
                        <div id="ipw-dash-bulk-wrap" style="display:none; align-items:center; gap:8px;">
                            <span id="ipw-dash-selected-count" style="font-size:12px; font-weight:700; color:#15803d;">0 selected</span>
                            <button type="button" class="button button-small button-primary" id="ipw-dash-btn-print-sheet" style="background:#15803d; border-color:#15803d; font-weight:700;">
                                📄 <?php esc_html_e('Print Selected (A4 Sheet - 4 per Page)', 'india-post-woocommerce'); ?>
                            </button>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-shipments')); ?>" class="button button-small"><?php esc_html_e('View All Consignments', 'india-post-woocommerce'); ?></a>
                </div>

                <?php if (!empty($recent_shipments)) : ?>
                    <table class="wp-list-table widefat fixed striped ipw-admin-table">
                        <thead>
                            <tr>
                                <th class="check-column" style="width:34px; text-align:center; padding:8px 0;">
                                    <input type="checkbox" id="ipw-cb-dash-select-all" title="<?php esc_attr_e('Select All', 'india-post-woocommerce'); ?>">
                                </th>
                                <th style="width:160px;"><?php esc_html_e('AWB Barcode', 'india-post-woocommerce'); ?></th>
                                <th style="width:85px;"><?php esc_html_e('Order #', 'india-post-woocommerce'); ?></th>
                                <th style="width:140px;"><?php esc_html_e('Article Type', 'india-post-woocommerce'); ?></th>
                                <th style="width:80px;"><?php esc_html_e('Weight', 'india-post-woocommerce'); ?></th>
                                <th style="width:80px;"><?php esc_html_e('Tariff', 'india-post-woocommerce'); ?></th>
                                <th style="width:210px;"><?php esc_html_e('Delivery Status', 'india-post-woocommerce'); ?></th>
                                <th style="width:160px; text-align:right;"><?php esc_html_e('Actions', 'india-post-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_shipments as $sh) :
                                $print_thermal = wp_nonce_url(
                                    admin_url('admin.php?action=ipw_print_label&order_id=' . $sh->order_id . '&format=thermal'),
                                    'ipw_print_label_' . $sh->order_id
                                );
                                $print_sheet = wp_nonce_url(
                                    admin_url('admin.php?action=ipw_print_label&order_id=' . $sh->order_id . '&format=sheet'),
                                    'ipw_print_label_' . $sh->order_id
                                );
                                $sh_t = strtoupper(trim((string)$sh->article_type));
                                $sh_svc = ($sh_t === 'BUSINESS_PARCEL' || strpos($sh_t, 'BP') !== false || $sh_t === 'PARCEL') ? 'Business Parcel' : (($sh_t === 'SP_INLAND_PARCEL') ? 'Speed Post Parcel' : 'Speed Post');
                                $st = strtolower($sh->delivery_status);
                                $st_label = ($st === 'booked' || empty($st)) ? '📦 BOOKED (AWAITING PICKUP)' : (($st === 'in_transit') ? '🚚 IN TRANSIT' : (($st === 'out_for_delivery') ? '🛵 OUT FOR DELIVERY' : (($st === 'delivered') ? '✅ DELIVERED' : str_replace('_', ' ', strtoupper($sh->delivery_status)))));
                            ?>
                                <tr>
                                    <td style="text-align:center; padding:8px 0;">
                                        <input type="checkbox" class="ipw-dash-row-cb" value="<?php echo esc_attr($sh->order_id); ?>">
                                    </td>
                                    <td><strong class="font-mono"><?php echo esc_html($sh->barcode_no); ?></strong></td>
                                    <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $sh->order_id . '&action=edit')); ?>">#<?php echo esc_html($sh->order_id); ?></a></td>
                                    <td><span class="ipw-type-pill"><?php echo esc_html($sh_svc); ?></span></td>
                                    <td><?php echo esc_html($sh->physical_weight); ?>g</td>
                                    <td>₹<?php echo esc_html(number_format($sh->tariff, 2)); ?></td>
                                    <td><span class="ipw-status-pill status-<?php echo esc_attr(sanitize_html_class($sh->delivery_status)); ?>"><?php echo esc_html($st_label); ?></span></td>
                                    <td style="text-align:right;">
                                        <a href="<?php echo esc_url($print_sheet); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Print in A4 Sheet (4 per Page)', 'india-post-woocommerce'); ?>">📄 Sheet</a>
                                        <a href="<?php echo esc_url($print_thermal); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Print Thermal Label', 'india-post-woocommerce'); ?>">🏷️</a>
                                        <button type="button" class="button button-small ipw-btn-refresh-row" data-order-id="<?php echo esc_attr($sh->order_id); ?>">🔄</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p style="padding:15px; color:#64748b; font-style:italic;">
                        <?php esc_html_e('No consignments booked yet. Select an order to generate your first India Post shipment.', 'india-post-woocommerce'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
