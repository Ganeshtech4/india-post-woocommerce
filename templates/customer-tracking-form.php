<?php
/**
 * Customer Tracking Page Template
 * Rendered by [india_post_tracking] shortcode
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ipw-tracking-container" id="ipw-customer-tracker">
    <!-- Header -->
    <div class="ipw-tracker-header">
        <div class="ipw-customer-logo-wrap" style="margin-bottom:12px;">
            <img src="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/img/indiapost.png'); ?>" alt="India Post" class="ipw-customer-logo" style="height:40px; width:auto; filter:brightness(0) invert(1);">
        </div>
        <div class="ipw-tracker-badge">
            <span class="ipw-badge-dot"></span>
            <?php esc_html_e('Official India Post Tracking', 'india-post-woocommerce'); ?>
        </div>
        <h2 class="ipw-tracker-title"><?php esc_html_e('Track Your Consignment', 'india-post-woocommerce'); ?></h2>
        <p class="ipw-tracker-subtitle">
            <?php esc_html_e('Enter your mobile number to view all your order shipments, or track directly by Consignment Number.', 'india-post-woocommerce'); ?>
        </p>

        <!-- Mode Tabs -->
        <div class="ipw-tracker-tabs">
            <button type="button" class="ipw-tab-btn active" data-tab="mobile">
                📱 <?php esc_html_e('Track by Mobile Number', 'india-post-woocommerce'); ?>
            </button>
            <button type="button" class="ipw-tab-btn" data-tab="awb">
                🔍 <?php esc_html_e('Track by Consignment #', 'india-post-woocommerce'); ?>
            </button>
        </div>
    </div>

    <!-- TAB 1: DIRECT MOBILE TRACKING (NO OTP) -->
    <div class="ipw-tab-content active" id="ipw-tab-mobile">
        <!-- Mobile Form -->
        <div class="ipw-step-box" id="ipw-step-phone">
            <form id="ipw-phone-form" class="ipw-form-grid">
                <div class="ipw-input-group">
                    <label for="ipw_input_mobile"><?php esc_html_e('Mobile Number', 'india-post-woocommerce'); ?></label>
                    <div class="ipw-phone-input-wrap">
                        <span class="ipw-country-code" style="white-space:nowrap !important; display:inline-flex !important; align-items:center !important; flex-shrink:0 !important; font-weight:700;">+91</span>
                        <input type="tel" id="ipw_input_mobile" name="mobile" placeholder="Enter 10-digit mobile number" maxlength="10" required autocomplete="tel">
                    </div>
                    <small class="ipw-hint"><?php esc_html_e('Enter the 10-digit mobile number used when placing your order.', 'india-post-woocommerce'); ?></small>
                </div>
                <button type="submit" class="ipw-submit-btn" id="ipw-btn-track-phone">
                    <span class="btn-text"><?php esc_html_e('Track Orders & Shipments', 'india-post-woocommerce'); ?> &rarr;</span>
                    <span class="btn-spinner" style="display:none;">⏳</span>
                </button>
            </form>
            <div class="ipw-notice ipw-notice-error" id="ipw-phone-error" style="display:none;"></div>
        </div>

        <!-- Verified Shipments Results -->
        <div class="ipw-results-box" id="ipw-shipments-results" style="display:none; margin-top:20px;">
            <div class="ipw-results-header">
                <div>
                    <h3 class="ipw-results-title"><?php esc_html_e('Your Consignments', 'india-post-woocommerce'); ?></h3>
                    <p class="ipw-results-sub" id="ipw-results-count-text"></p>
                </div>
                <button type="button" class="ipw-btn-logout" id="ipw-btn-track-another">
                    <?php esc_html_e('Track Another Number', 'india-post-woocommerce'); ?>
                </button>
            </div>

            <div class="ipw-shipments-list" id="ipw-shipments-cards-container">
                <!-- Dynamically populated via JS -->
            </div>
        </div>
    </div>

    <!-- TAB 2: DIRECT AWB TRACKING -->
    <div class="ipw-tab-content" id="ipw-tab-awb" style="display:none;">
        <div class="ipw-step-box">
            <form id="ipw-direct-awb-form" class="ipw-form-grid">
                <div class="ipw-input-group">
                    <label for="ipw_input_awb"><?php esc_html_e('India Post Consignment Number', 'india-post-woocommerce'); ?></label>
                    <input type="text" id="ipw_input_awb" placeholder="E.g. ET214330013IN / RK775227016IN" maxlength="13" class="ipw-awb-input" required>
                    <small class="ipw-hint"><?php esc_html_e('Enter the 13-character barcode from your shipping confirmation email or SMS.', 'india-post-woocommerce'); ?></small>
                </div>
                <button type="submit" class="ipw-submit-btn" id="ipw-btn-direct-track">
                    <span class="btn-text"><?php esc_html_e('Track Consignment', 'india-post-woocommerce'); ?> &rarr;</span>
                    <span class="btn-spinner" style="display:none;">⏳</span>
                </button>
            </form>
            <div class="ipw-notice ipw-notice-error" id="ipw-awb-error" style="display:none;"></div>

            <!-- Single Tracking Result -->
            <div class="ipw-direct-result" id="ipw-direct-result" style="display:none;">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>
</div>
