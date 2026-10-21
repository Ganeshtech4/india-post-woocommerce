<?php
/**
 * Fired during plugin activation and deactivation.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPW_Activator {

    /**
     * Plugin activation logic.
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Shipments Table
        $table_shipments = $wpdb->prefix . 'ipw_shipments';
        $sql_shipments = "CREATE TABLE IF NOT EXISTS $table_shipments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            barcode_no varchar(50) NOT NULL,
            article_type varchar(50) NOT NULL DEFAULT 'SP_INLAND_DOC',
            physical_weight int(11) NOT NULL DEFAULT 100,
            chargeable_weight int(11) DEFAULT NULL,
            tariff decimal(10,2) DEFAULT '0.00',
            currency varchar(10) DEFAULT 'INR',
            booking_status varchar(50) DEFAULT 'booked',
            delivery_status varchar(100) DEFAULT 'in_transit',
            pickup_or_dropoff varchar(20) DEFAULT 'DROPOFF',
            office_id varchar(50) DEFAULT '',
            batch_id varchar(100) DEFAULT '',
            mail_booking_dom_id varchar(100) DEFAULT '',
            label_data longtext DEFAULT NULL,
            tracking_cache longtext DEFAULT NULL,
            last_tracked_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            UNIQUE KEY barcode_no (barcode_no),
            KEY delivery_status (delivery_status)
        ) $charset_collate;";
        dbDelta($sql_shipments);

        // 2. Customer OTP Tokens Table
        $table_otp = $wpdb->prefix . 'ipw_otp_tokens';
        $sql_otp = "CREATE TABLE IF NOT EXISTS $table_otp (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mobile_no varchar(15) NOT NULL,
            otp_code varchar(10) NOT NULL,
            token varchar(64) NOT NULL,
            verified tinyint(1) NOT NULL DEFAULT 0,
            attempts int(11) NOT NULL DEFAULT 0,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY mobile_no (mobile_no),
            KEY token (token)
        ) $charset_collate;";
        dbDelta($sql_otp);

        // Set default plugin options if not already set
        self::set_default_options();
    }

    /**
     * Default options matching India Post technical specs.
     */
    public static function set_default_options() {
        $defaults = array(
            'environment'               => 'sandbox', // sandbox or production
            'sandbox_base_url'          => 'https://test.cept.gov.in/beextcustomer',
            'prod_base_url'             => 'https://api.cept.gov.in/beextcustomer',
            'sandbox_username'          => '9999999999',
            'sandbox_password'          => 'Dop@1234',
            'prod_username'             => '',
            'prod_password'             => '',
            'customer_id'               => '3000064781',
            'contract_id_sp'            => '41585456', // Speed Post
            'contract_id_bp'            => '41367422', // Business Parcel
            'contract_id_24sp_doc'      => '41469430',
            'contract_id_24spp_par'     => '41918281',
            'contract_id_48sp_doc'      => '41471113',
            'default_office_id'         => '21260024',
            'pickup_or_dropoff'         => 'DROPOFF',
            'awb_prefix'                => 'ET',
            'awb_counter_start'         => '21433001',
            'awb_counter_end'           => '21434000',
            'awb_current_counter'       => '21433001',
            'awb_suffix'                => 'IN',
            // Default Sender Details (Dhruva Publications)
            'sender_name'               => 'DHRUVA PUBLICATIONS',
            'sender_company'            => 'Dhruva Publications',
            'sender_add_line_1'         => 'Main Office Road',
            'sender_add_line_2'         => 'Commercial Hub',
            'sender_city'               => 'Bengaluru',
            'sender_state'              => 'Karnataka',
            'sender_pincode'            => '560001',
            'sender_mobile_no'          => '9876543210',
            'sender_emailid'            => 'info@dhuruvapublications.com',
            // Tracking & OTP settings
            'otp_mode'                  => 'simulation', // 'simulation' (shows OTP on screen or auto-verifies for testing) or 'sms_gateway'
            'otp_expiry_minutes'        => 10,
            'auto_track_cron'           => 'yes',
            'webhook_secret'            => wp_generate_password(24, false),
        );

        foreach ($defaults as $key => $val) {
            $opt_name = 'ipw_' . $key;
            if (get_option($opt_name) === false) {
                update_option($opt_name, $val);
            }
        }
    }

    /**
     * Deactivation logic.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('ipw_hourly_tracking_sync');
    }
}
