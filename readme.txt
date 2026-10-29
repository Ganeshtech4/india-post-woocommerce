=== India Post Shipping & Tracking for WooCommerce ===
Contributors: Dhruva Publications
Tags: woocommerce, india post, speed post, shipping, tracking, barcode, thermal label
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 9.3
Stable tag: 1.3.0
License: GPLv2 or later

Enterprise-grade India Post Business API integration for WooCommerce with shipment booking, AWB series allocation (UPU S10), thermal/A6 labels, and customer OTP tracking.

== Description ==

India Post Shipping & Tracking for WooCommerce provides an end-to-end technical integration with the Department of Posts (CEPT) Business APIs. It transforms your WooCommerce store into an automated dispatch center, allowing you to book Speed Post & Business Parcels directly from your order management screen, generate compliant printable shipping labels, and offer your customers a self-service tracking portal verified via mobile OTP.

### Key Capabilities:
1. **Shipment Booking via India Post Business API**:
   - Single & bulk order booking directly from WooCommerce orders (`POST /process-articles/:customerId`).
   - Supports Speed Post Document (`SP_INLAND_DOC`), Speed Post Parcel (`SP_INLAND_PARCEL`), Business Parcel (`BUSINESS_PARCEL`), and NDD services (`24_SPEEDPOST_DOC`, `24_SPP_PARSPL`, `48_SPEEDPOST_DOC`).
   - Automated weight calculations and tariff computation.

2. **Consignment Barcode (UPU S10) & Label Printing**:
   - Dynamic AWB barcode series allocation using official Universal Postal Union (UPU S10) modulo-11 check digit algorithm.
   - Built-in High-Resolution Vector Code-128 SVG Barcode rendering with zero external dependencies.
   - 4x6 / A6 Thermal Shipping Label generation with customizable sender (Dhruva Publications) and recipient addresses, routing codes, and COD amounts.
   - Direct download and streaming of official India Post Label API PDFs (`POST /v1/label/create/domestic`).

3. **Customer Self-Service Tracking by Mobile & OTP**:
   - Shortcode: `[india_post_tracking]`.
   - Customer enters their 10-digit mobile number, verifies with a 6-digit OTP, and sees all active and past shipments associated with their orders.
   - Interactive, vertical checkpoint tracking timeline showing every transit event, post office facility, timestamp, and delivery status.
   - Direct 13-character AWB lookup tab.

4. **Self-Service Portal Dashboard Layout**:
   - Faithfully modeled after the Department of Posts Customer Selfservice Portal layout with India Post crimson red navigation sidebar, status banners, and integration helper cards.
   - Whitelist Server IP Address helper for CEPT India Post onboarding.
   - Instant PIN Code & Post Office Lookup tool with live API connectivity test.
   - Real-time inbound webhook listener (`/wp-json/india-post/v1/webhook`).

== Installation ==

1. Upload the `india-post-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **India Post > Settings & API** to configure your Customer ID, Contract IDs, and Sandbox or Production credentials.
4. Add the shortcode `[india_post_tracking]` to any WordPress page (e.g., `/track-order/`) for customer self-service tracking.

== Frequently Asked Questions ==

= Where do I get my India Post API credentials? =
India Post provides credentials via the CEPT Technical Integration Portal (test.cept.gov.in) after registration and signing the bulk customer agreement.

= Does this plugin work without live CEPT IP whitelisting? =
Yes. The plugin includes a built-in Sandbox Fallback Simulator so you can test end-to-end booking, label generation, order status updates, and customer OTP tracking even before your server IP is whitelisted by CEPT.

= What barcode standard is used? =
The plugin follows the 13-character Universal Postal Union (UPU S10) standard used by India Post (e.g. `ET214330013IN`), calculating the 9th check digit with the official weighted modulo 11 algorithm.

== Changelog ==

= 1.0.0 =
* Initial release with full CEPT Business API specifications, AWB generator, A6/thermal labels, customer mobile OTP tracking, and Department of Posts portal dashboard.
