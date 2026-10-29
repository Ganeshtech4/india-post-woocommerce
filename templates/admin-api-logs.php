<?php
/**
 * Admin API Logs & CEPT Verification Template
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$env = get_option('ipw_environment', 'sandbox');
$logs = get_option('ipw_api_debug_logs', array());
if (!is_array($logs)) {
    $logs = array();
}

$server_ip = !empty($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : '145.79.58.237';
$token = get_transient('ipw_api_access_token');
$has_token = !empty($token);
?>
<div class="wrap ipw-portal-container">
    <div class="ipw-subpage-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <img src="<?php echo esc_url(IPW_PLUGIN_URL . 'assets/img/indiapost.png'); ?>" alt="India Post" class="ipw-subpage-logo" style="height:38px !important; width:auto !important; max-width:110px !important; object-fit:contain !important; display:block !important;">
            <div>
                <h2><?php esc_html_e('India Post API Logs & CEPT Verification', 'india-post-woocommerce'); ?></h2>
                <p class="description"><?php esc_html_e('Inspect 100% raw server-to-server request payloads and live CEPT responses for UAT sign-off.', 'india-post-woocommerce'); ?></p>
            </div>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=india-post-dashboard')); ?>" class="button button-secondary">
                &larr; <?php esc_html_e('Back to Portal Dashboard', 'india-post-woocommerce'); ?>
            </a>
        </div>
    </div>

    <!-- Status & Quick Action Bar -->
    <div class="ipw-portal-top-bar" style="margin-bottom:20px;">
        <div class="ipw-status-col">
            <span class="label">API ENVIRONMENT:</span>
            <span class="val badge-env-<?php echo esc_attr($env); ?>"><?php echo esc_html(strtoupper($env)); ?></span>
        </div>
        <div class="ipw-status-col">
            <span class="label">BEARER TOKEN:</span>
            <span class="val <?php echo $has_token ? 'status-active' : 'status-pending'; ?>">
                <?php echo $has_token ? 'Active (Cached)' : 'Not Generated'; ?>
            </span>
        </div>
        <div class="ipw-status-col">
            <span class="label">SERVER IP (WHITELISTED):</span>
            <span class="val font-mono"><?php echo esc_html($server_ip); ?></span>
        </div>
        <div class="ipw-status-action" style="display:flex; gap:10px;">
            <button type="button" class="button button-primary" id="ipw-btn-run-all-tests" style="font-weight:600;">
                🚀 <?php esc_html_e('Run Live UAT API Tests', 'india-post-woocommerce'); ?>
            </button>
            <button type="button" class="button button-secondary" id="ipw-btn-copy-email-report" style="font-weight:600;">
                📋 <?php esc_html_e('Copy CEPT Verification Email', 'india-post-woocommerce'); ?>
            </button>
            <?php if (!empty($logs)) : ?>
            <button type="button" class="button button-link-delete" id="ipw-btn-clear-api-logs" style="color:#d63638; text-decoration:none; padding:4px 8px;">
                <?php esc_html_e('Clear Logs', 'india-post-woocommerce'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live Test Running Status Indicator -->
    <div id="ipw-test-runner-status" style="display:none; margin-bottom:20px;"></div>

    <!-- Generated Email Modal/Drawer -->
    <div id="ipw-email-preview-card" style="display:none; background:#ffffff; border:1px solid #c3c4c7; border-left:4px solid #2271b1; padding:20px; border-radius:8px; margin-bottom:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="margin:0;">📧 Ready-to-Send Email for CEPT Chennai (integrations.cept@indiapost.gov.in)</h3>
            <div>
                <button type="button" class="button button-primary button-small" id="ipw-btn-copy-raw-email-text">
                    📋 Copy Text to Clipboard
                </button>
                <button type="button" class="button button-secondary button-small" onclick="jQuery('#ipw-email-preview-card').slideUp();">
                    Close
                </button>
            </div>
        </div>
        <p class="description" style="margin-bottom:10px;">
            Below is the compiled email containing the exact live payloads and server responses captured from CEPT endpoints:
        </p>
        <textarea id="ipw-email-text-content" readonly style="width:100%; height:320px; font-family:Consolas, monospace; font-size:12px; background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #cbd5e1;"></textarea>
    </div>

    <!-- LOG LIST CARD -->
    <div class="ipw-settings-card">
        <div class="ipw-card-header-bar" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>📡 Recorded API Transactions (<?php echo count($logs); ?>)</h3>
            <span class="description" style="color:#64748b;">Live server-to-server calls to CEPT India Post</span>
        </div>
        <div class="ipw-card-body-padded">
            <?php if (empty($logs)) : ?>
                <div style="text-align:center; padding:40px 20px; color:#64748b;">
                    <p style="font-size:32px; margin-bottom:8px;">🛰️</p>
                    <p style="font-size:16px; font-weight:600;"><?php esc_html_e('No API calls logged yet.', 'india-post-woocommerce'); ?></p>
                    <p class="description"><?php esc_html_e('Click "🚀 Run Live UAT API Tests" above or book a test order to capture live server payloads and responses.', 'india-post-woocommerce'); ?></p>
                </div>
            <?php else : ?>
                <div class="ipw-log-entries" style="display:flex; flex-direction:column; gap:16px;">
                    <?php foreach ($logs as $i => $log) : 
                        $status_code = intval($log['response_code']);
                        $is_success = ($status_code >= 200 && $status_code < 300);
                        $badge_class = $is_success ? 'status-active' : 'status-failed';
                        $method_color = ($log['method'] === 'POST') ? '#0284c7' : '#16a34a';
                    ?>
                        <div class="ipw-log-card" style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:#ffffff;">
                            <div class="ipw-log-header" style="background:#f8fafc; padding:12px 16px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="jQuery('#log-body-<?php echo esc_attr($i); ?>').slideToggle(150);">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span style="background:<?php echo esc_attr($method_color); ?>; color:#fff; font-weight:700; font-size:11px; padding:3px 8px; border-radius:4px; font-family:monospace;">
                                        <?php echo esc_html($log['method']); ?>
                                    </span>
                                    <strong style="font-size:14px; color:#1e293b;"><?php echo esc_html($log['name']); ?></strong>
                                    <code style="font-size:12px; color:#475569; background:#e2e8f0; padding:2px 6px; border-radius:4px;"><?php echo esc_html($log['url']); ?></code>
                                </div>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span class="status-text-pill <?php echo esc_attr($badge_class); ?>">
                                        HTTP <?php echo esc_html($status_code); ?>
                                    </span>
                                    <span style="font-size:12px; color:#94a3b8;"><?php echo esc_html($log['timestamp']); ?></span>
                                    <span style="color:#64748b; font-size:12px;">&#9662;</span>
                                </div>
                            </div>
                            <div class="ipw-log-body" id="log-body-<?php echo esc_attr($i); ?>" style="padding:16px; display:<?php echo $i === 0 ? 'block' : 'none'; ?>;">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                                    <!-- Request -->
                                    <div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                            <strong style="font-size:12px; text-transform:uppercase; color:#475569;">Request Payload</strong>
                                            <button type="button" class="button button-small ipw-copy-snippet-btn" data-target="req-<?php echo esc_attr($i); ?>">Copy</button>
                                        </div>
                                        <pre id="req-<?php echo esc_attr($i); ?>" style="background:#0f172a; color:#f8fafc; padding:12px; border-radius:6px; font-size:11px; max-height:260px; overflow:auto; margin:0;"><?php echo esc_html(!empty($log['payload']) ? $log['payload'] : '(No body payload for ' . $log['method'] . ')'); ?></pre>
                                    </div>
                                    <!-- Response -->
                                    <div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                            <strong style="font-size:12px; text-transform:uppercase; color:#475569;">Server Response (Raw JSON)</strong>
                                            <button type="button" class="button button-small ipw-copy-snippet-btn" data-target="res-<?php echo esc_attr($i); ?>">Copy</button>
                                        </div>
                                        <pre id="res-<?php echo esc_attr($i); ?>" style="background:#0f172a; color:#38bdf8; padding:12px; border-radius:6px; font-size:11px; max-height:260px; overflow:auto; margin:0;"><?php echo esc_html($log['response_body']); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

