<?php
/**
 * Admin Settings & API Configuration Template
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$env = get_option('ipw_environment', 'sandbox');
?>
<div class="wrap ipw-portal-container">
    <div class="ipw-subpage-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <img src="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/img/indiapost.png'); ?>" alt="India Post" class="ipw-subpage-logo" style="height:38px !important; max-height:38px !important; width:auto !important; max-width:110px !important; object-fit:contain !important; display:block !important;">
            <div>
                <h2><?php esc_html_e('India Post API Settings & Configuration', 'india-post-woocommerce'); ?></h2>
                <p class="description"><?php esc_html_e('Configure your India Post Business API access, contract IDs, AWB series, sender details, and customer OTP tracking.', 'india-post-woocommerce'); ?></p>
            </div>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-dashboard')); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Portal Dashboard', 'india-post-woocommerce'); ?>
            </a>
        </div>
    </div>

    <form method="post" action="" class="ipw-settings-form">
        <?php wp_nonce_field('ipw_settings_verify'); ?>

        <!-- SECTION 1: API ENVIRONMENT & CREDENTIALS -->
        <div class="ipw-settings-card">
            <div class="ipw-card-header-bar">
                <h3>🌐 1. Environment & API Credentials</h3>
            </div>
            <div class="ipw-card-body-padded">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ipw_environment"><?php esc_html_e('API Environment', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <select name="ipw_environment" id="ipw_environment" class="regular-text">
                                <option value="sandbox" <?php selected($env, 'sandbox'); ?>><?php esc_html_e('Sandbox / UAT (Test Environment)', 'india-post-woocommerce'); ?></option>
                                <option value="production" <?php selected($env, 'production'); ?>><?php esc_html_e('Production (Live Environment)', 'india-post-woocommerce'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Switch to Production once your UAT testing and CEPT IP whitelisting is complete.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_customer_id"><?php esc_html_e('India Post Customer ID', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_customer_id" id="ipw_customer_id" value="<?php echo esc_attr(get_option('ipw_customer_id', '3000064781')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Your 10-digit Customer ID allocated by Department of Posts (e.g. 3000064781).', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="ipw-env-boxes">
                    <!-- Sandbox Credentials Box -->
                    <div class="ipw-env-box ipw-box-sandbox">
                        <h4>Sandbox / UAT Credentials</h4>
                        <p>
                            <label><?php esc_html_e('Sandbox API Base URL:', 'india-post-woocommerce'); ?></label>
                            <input type="text" name="ipw_sandbox_base_url" value="<?php echo esc_attr(get_option('ipw_sandbox_base_url', 'https://test.cept.gov.in/beextcustomer')); ?>" class="widefat">
                        </p>
                        <p>
                            <label><?php esc_html_e('Sandbox Username / Mobile:', 'india-post-woocommerce'); ?></label>
                            <input type="text" name="ipw_sandbox_username" value="<?php echo esc_attr(get_option('ipw_sandbox_username', '9999999999')); ?>" class="widefat">
                        </p>
                        <p>
                            <label><?php esc_html_e('Sandbox Password:', 'india-post-woocommerce'); ?></label>
                            <input type="password" name="ipw_sandbox_password" value="<?php echo esc_attr(get_option('ipw_sandbox_password', 'Dop@1234')); ?>" class="widefat">
                        </p>
                    </div>

                    <!-- Production Credentials Box -->
                    <div class="ipw-env-box ipw-box-prod">
                        <h4>Production Credentials (Live)</h4>
                        <p>
                            <label><?php esc_html_e('Production API Base URL:', 'india-post-woocommerce'); ?></label>
                            <input type="text" name="ipw_prod_base_url" value="<?php echo esc_attr(get_option('ipw_prod_base_url', 'https://api.cept.gov.in/beextcustomer')); ?>" class="widefat">
                        </p>
                        <p>
                            <label><?php esc_html_e('Production Username / User ID:', 'india-post-woocommerce'); ?></label>
                            <input type="text" name="ipw_prod_username" value="<?php echo esc_attr(get_option('ipw_prod_username', '')); ?>" class="widefat" placeholder="Issued by CEPT for Production">
                        </p>
                        <p>
                            <label><?php esc_html_e('Production Password:', 'india-post-woocommerce'); ?></label>
                            <input type="password" name="ipw_prod_password" value="<?php echo esc_attr(get_option('ipw_prod_password', '')); ?>" class="widefat" placeholder="Production Password">
                        </p>
                    </div>
                </div>

                <div style="margin-top:15px;">
                    <label>
                        <input type="checkbox" name="ipw_enable_sandbox_simulation" value="yes" <?php checked(get_option('ipw_enable_sandbox_simulation', 'yes'), 'yes'); ?>>
                        <strong><?php esc_html_e('Enable Sandbox Fallback Simulator', 'india-post-woocommerce'); ?></strong>
                    </label>
                    <p class="description" style="margin-left:24px;">
                        <?php esc_html_e('Allows full WooCommerce order booking and tracking testing even if India Post test servers are in maintenance or static IP is awaiting whitelist approval.', 'india-post-woocommerce'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- SECTION 2: CONTRACT IDS & POST OFFICE -->
        <div class="ipw-settings-card">
            <div class="ipw-card-header-bar">
                <h3>📜 2. India Post Contract IDs & Office Settings</h3>
            </div>
            <div class="ipw-card-body-padded">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ipw_contract_id_sp"><?php esc_html_e('Speed Post Contract ID', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_contract_id_sp" id="ipw_contract_id_sp" value="<?php echo esc_attr(get_option('ipw_contract_id_sp', '41585456')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Speed Post Contract (e.g. 41585456)', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_contract_id_bp"><?php esc_html_e('Business Parcel Contract ID', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_contract_id_bp" id="ipw_contract_id_bp" value="<?php echo esc_attr(get_option('ipw_contract_id_bp', '41367422')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Business Parcel Contract (e.g. 41367422)', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_default_service"><?php esc_html_e('Default Parcel / Booking Service', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <select name="ipw_default_service" id="ipw_default_service" class="regular-text">
                                <option value="BUSINESS_PARCEL" <?php selected(get_option('ipw_default_service', 'BUSINESS_PARCEL'), 'BUSINESS_PARCEL'); ?>><?php esc_html_e('Business Parcel (BP) — Recommended for normal book parcels', 'india-post-woocommerce'); ?></option>
                                <option value="SP_INLAND_PARCEL" <?php selected(get_option('ipw_default_service', 'BUSINESS_PARCEL'), 'SP_INLAND_PARCEL'); ?>><?php esc_html_e('Speed Post Parcel (SP_INLAND_PARCEL)', 'india-post-woocommerce'); ?></option>
                                <option value="SP_INLAND_DOC" <?php selected(get_option('ipw_default_service', 'BUSINESS_PARCEL'), 'SP_INLAND_DOC'); ?>><?php esc_html_e('Speed Post Document (SP_INLAND_DOC)', 'india-post-woocommerce'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Select which service type is chosen by default when booking shipments in WooCommerce.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_default_weight"><?php esc_html_e('Default Dummy Package Weight (Grams)', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="number" name="ipw_default_weight" id="ipw_default_weight" value="<?php echo esc_attr(get_option('ipw_default_weight', '250')); ?>" min="1" step="10" style="width:120px;"> g
                            <p class="description"><?php esc_html_e('Used when WooCommerce products do not have weight specified. India Post API strictly requires a weight value for all bookings.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_show_weight_on_label"><?php esc_html_e('Print Weight on Shipping Label', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <label class="ipw-switch-wrap">
                                <input type="checkbox" name="ipw_show_weight_on_label" id="ipw_show_weight_on_label" class="ipw-switch-input" value="yes" <?php checked(get_option('ipw_show_weight_on_label', 'no'), 'yes'); ?>>
                                <span class="ipw-switch-slider"></span>
                                <span class="ipw-switch-label"><?php echo get_option('ipw_show_weight_on_label', 'no') === 'yes' ? esc_html__('Show weight on label', 'india-post-woocommerce') : esc_html__('Hide weight on label (Default)', 'india-post-woocommerce'); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e('When turned off (recommended), the weight text is completely hidden from the printed shipping label.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_contract_id_24sp_doc"><?php esc_html_e('24 Speed Post DOC Contract ID', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_contract_id_24sp_doc" id="ipw_contract_id_24sp_doc" value="<?php echo esc_attr(get_option('ipw_contract_id_24sp_doc', '41469430')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_default_office_id"><?php esc_html_e('Default Booking Office ID', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_default_office_id" id="ipw_default_office_id" value="<?php echo esc_attr(get_option('ipw_default_office_id', '21260024')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('8-digit Office ID of the booking / pickup post office (e.g. 21260024). Can be searched via PIN Code Lookup tool.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_pickup_or_dropoff"><?php esc_html_e('Default Dispatch Mode', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <select name="ipw_pickup_or_dropoff" id="ipw_pickup_or_dropoff">
                                <option value="DROPOFF" <?php selected(get_option('ipw_pickup_or_dropoff', 'DROPOFF'), 'DROPOFF'); ?>>DROPOFF (Drop parcels at Post Office / Hub)</option>
                                <option value="PICKUP" <?php selected(get_option('ipw_pickup_or_dropoff', 'DROPOFF'), 'PICKUP'); ?>>PICKUP (Postman / Van pickup from merchant)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SECTION 3: AWB BARCODE SERIES (UPU S10) -->
        <div class="ipw-settings-card" id="awb-series">
            <div class="ipw-card-header-bar">
                <h3>🏷️ 3. AWB Barcode Range & Allocation (UPU S10 Standard)</h3>
            </div>
            <div class="ipw-card-body-padded">
                <p class="description">
                    <?php esc_html_e('India Post allocates an AWB range to your account (e.g. ET21433001XIN to ET21434000XIN). The plugin automatically computes the 9th check digit using the UPU S10 modulo 11 algorithm.', 'india-post-woocommerce'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ipw_awb_prefix"><?php esc_html_e('AWB Prefix (2 Letters)', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_awb_prefix" id="ipw_awb_prefix" value="<?php echo esc_attr(get_option('ipw_awb_prefix', 'BP')); ?>" maxlength="2" style="width:70px; text-transform:uppercase;">
                            <span class="description"><?php esc_html_e('E.g. BP (Business Parcel), ET (Speed Post), etc.', 'india-post-woocommerce'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_awb_counter_start"><?php esc_html_e('Range Start (8 Digits)', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="number" name="ipw_awb_counter_start" id="ipw_awb_counter_start" value="<?php echo esc_attr(get_option('ipw_awb_counter_start', '21433001')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_awb_counter_end"><?php esc_html_e('Range End (8 Digits)', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="number" name="ipw_awb_counter_end" id="ipw_awb_counter_end" value="<?php echo esc_attr(get_option('ipw_awb_counter_end', '21434000')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_awb_current_counter"><?php esc_html_e('Current Sequence Counter', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="number" name="ipw_awb_current_counter" id="ipw_awb_current_counter" value="<?php echo esc_attr(get_option('ipw_awb_current_counter', '21433001')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Next consignment will be allocated this number + check digit.', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_awb_suffix"><?php esc_html_e('Country Code Suffix', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_awb_suffix" id="ipw_awb_suffix" value="<?php echo esc_attr(get_option('ipw_awb_suffix', 'IN')); ?>" maxlength="2" style="width:70px; text-transform:uppercase;" readonly>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SECTION 4: SENDER DETAILS -->
        <div class="ipw-settings-card">
            <div class="ipw-card-header-bar">
                <h3>🏢 4. Sender / Dhruva Publications Return Address</h3>
            </div>
            <div class="ipw-card-body-padded">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ipw_sender_name"><?php esc_html_e('Sender / Contact Name', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_name" id="ipw_sender_name" value="<?php echo esc_attr(get_option('ipw_sender_name', 'DHRUVA PUBLICATIONS')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_company"><?php esc_html_e('Company Name', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_company" id="ipw_sender_company" value="<?php echo esc_attr(get_option('ipw_sender_company', 'Dhruva Publications')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_add_line_1"><?php esc_html_e('Address Line 1', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_add_line_1" id="ipw_sender_add_line_1" value="<?php echo esc_attr(get_option('ipw_sender_add_line_1', 'Main Office Road')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_add_line_2"><?php esc_html_e('Address Line 2', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_add_line_2" id="ipw_sender_add_line_2" value="<?php echo esc_attr(get_option('ipw_sender_add_line_2', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_city"><?php esc_html_e('City', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_city" id="ipw_sender_city" value="<?php echo esc_attr(get_option('ipw_sender_city', 'Bengaluru')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_state"><?php esc_html_e('State', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_state" id="ipw_sender_state" value="<?php echo esc_attr(get_option('ipw_sender_state', 'Karnataka')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_pincode"><?php esc_html_e('Origin PIN Code', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="text" name="ipw_sender_pincode" id="ipw_sender_pincode" value="<?php echo esc_attr(get_option('ipw_sender_pincode', '560001')); ?>" maxlength="6" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_mobile_no"><?php esc_html_e('Sender Mobile Number', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="tel" name="ipw_sender_mobile_no" id="ipw_sender_mobile_no" value="<?php echo esc_attr(get_option('ipw_sender_mobile_no', '9876543210')); ?>" maxlength="10" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_sender_emailid"><?php esc_html_e('Sender Email', 'india-post-woocommerce'); ?></label></th>
                        <td><input type="email" name="ipw_sender_emailid" id="ipw_sender_emailid" value="<?php echo esc_attr(get_option('ipw_sender_emailid', 'info@dhuruvapublications.com')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ipw_default_hsn"><?php esc_html_e('Default Product HSN / SAC Code', 'india-post-woocommerce'); ?></label></th>
                        <td>
                            <input type="text" name="ipw_default_hsn" id="ipw_default_hsn" value="<?php echo esc_attr(get_option('ipw_default_hsn', '4901')); ?>" maxlength="10" class="regular-text">
                            <p class="description"><?php esc_html_e('Used on shipping labels if a product does not have a custom HSN code set (Default: 4901 for Printed Books / Publications).', 'india-post-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- SECTION 5: CUSTOMER TRACKING -->
        <div class="ipw-settings-card">
            <div class="ipw-card-header-bar">
                <h3>📱 5. Customer Self-Service Tracking</h3>
            </div>
            <div class="ipw-card-body-padded">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Tracking Verification Mode', 'india-post-woocommerce'); ?></th>
                        <td>
                            <span class="status-text-pill status-ready">⚡ <?php esc_html_e('Direct Phone & Consignment Lookup (No OTP Required)', 'india-post-woocommerce'); ?></span>
                            <p class="description">
                                <?php esc_html_e('Customers simply enter their 10-digit mobile number or 13-character AWB to instantly see their booked consignments and live tracking milestones.', 'india-post-woocommerce'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Tracking Shortcode', 'india-post-woocommerce'); ?></th>
                        <td>
                            <code>[india_post_tracking]</code>
                            <p class="description">
                                <?php esc_html_e('Paste this shortcode into any WordPress page (e.g. /track-order/) to display the customer tracking interface.', 'india-post-woocommerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="ipw_save_settings" id="submit" class="button button-primary button-large" value="<?php esc_attr_e('Save India Post Settings', 'india-post-woocommerce'); ?>">
        </p>
    </form>
</div>
