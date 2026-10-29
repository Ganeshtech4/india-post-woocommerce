<?php
/**
 * Barcode Helper for India Post UPU S10 format and barcode generation.
 *
 * @package IndiaPostWooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('IPW_Barcode_Helper')) {
class IPW_Barcode_Helper {

    /**
     * Compute the UPU S10 Check Digit for an 8-digit serial number.
     * Weights: 8, 6, 4, 2, 3, 5, 9, 7
     *
     * @param string|int $serial 8-digit serial number.
     * @return int Check digit (0-9).
     */
    public static function calculate_check_digit($serial) {
        $serial = str_pad(preg_replace('/\D/', '', $serial), 8, '0', STR_PAD_LEFT);
        $weights = array(8, 6, 4, 2, 3, 5, 9, 7);
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += intval($serial[$i]) * $weights[$i];
        }

        $remainder = $sum % 11;

        if ($remainder === 0) {
            return 5;
        } elseif ($remainder === 1) {
            return 0;
        } else {
            return 11 - $remainder;
        }
    }

    /**
     * Generate the complete 13-character AWB barcode.
     *
     * @param string $prefix Service prefix (e.g., 'ET', 'EB', 'EM', 'RK')
     * @param string|int $serial 8-digit serial
     * @param string $suffix Country code (default 'IN')
     * @return string
     */
    public static function build_awb($prefix, $serial, $suffix = 'IN') {
        $clean_serial = str_pad(preg_replace('/\D/', '', $serial), 8, '0', STR_PAD_LEFT);
        $check_digit = self::calculate_check_digit($clean_serial);
        return strtoupper(trim($prefix)) . $clean_serial . $check_digit . strtoupper(trim($suffix));
    }

    /**
     * Validate an AWB barcode against UPU S10 standards.
     *
     * @param string $barcode
     * @return bool
     */
    public static function validate_awb($barcode) {
        $barcode = trim(strtoupper($barcode));
        if (strlen($barcode) !== 13) {
            return false;
        }

        // Pattern: 2 uppercase letters + 9 digits + 2 uppercase letters
        if (!preg_match('/^[A-Z]{2}(\d{8})(\d)[A-Z]{2}$/', $barcode, $matches)) {
            return false;
        }

        $serial = $matches[1];
        $expected_check = intval($matches[2]);
        $actual_check = self::calculate_check_digit($serial);

        return $expected_check === $actual_check;
    }

    /**
     * Allocate the next available AWB from the configured series.
     *
     * @return string|WP_Error AWB string or error if range exhausted.
     */
    public static function get_next_available_awb($article_type = '') {
        $env = get_option('ipw_environment', 'sandbox');
        $prefix = get_option('ipw_awb_prefix', '');
        if ($env === 'sandbox') {
            // Official CEPT UAT-8 specifies test series: ET21433001XIN to ET21434000XIN
            $prefix = 'ET';
        } elseif (empty($prefix)) {
            $is_bp = (empty($article_type) && get_option('ipw_default_service', 'BUSINESS_PARCEL') === 'BUSINESS_PARCEL') || 
                     (!empty($article_type) && ($article_type === 'BUSINESS_PARCEL' || strpos($article_type, 'BP') !== false));
            $prefix = $is_bp ? 'BP' : 'ET';
        } elseif ($prefix === 'ET' && ($article_type === 'BUSINESS_PARCEL' || strpos($article_type, 'BP') !== false)) {
            // When booking Business Parcel and prefix is default ET, use BP for proper UPU parcel coding
            $prefix = 'BP';
        }
        $start       = intval(get_option('ipw_awb_counter_start', 21433001));
        $end         = intval(get_option('ipw_awb_counter_end', 21434000));
        $current     = intval(get_option('ipw_awb_current_counter', 21433001));
        $suffix      = get_option('ipw_awb_suffix', 'IN');

        if ($current < $start) {
            $current = $start;
        }

        if ($current > $end) {
            return new WP_Error(
                'ipw_awb_exhausted',
                sprintf(__('Allocated India Post AWB range exhausted (%d - %d). Please configure a new series in Settings.', 'india-post-woocommerce'), $start, $end)
            );
        }

        // Build AWB with valid check digit
        $awb = self::build_awb($prefix, $current, $suffix);

        // Advance counter
        update_option('ipw_awb_current_counter', $current + 1);

        return $awb;
    }

    /**
     * Generate an inline SVG for Code 128 (Subset B) barcode.
     * Zero external dependencies, pure vector scalable barcode for thermal & paper printing.
     *
     * @param string $text Barcode text.
     * @param int $height Height of barcode bars in px.
     * @param int $scale Bar width multiplier.
     * @return string SVG HTML.
     */
    public static function generate_code128_svg($text, $height = 50, $scale = 2) {
        $text = trim($text);
        if (empty($text)) {
            return '';
        }

        // Code 128 patterns (0-106)
        $patterns = array(
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
        );

        $codes = array(104); // Start Code B
        $sum = 104;

        for ($i = 0; $i < strlen($text); $i++) {
            $char = ord($text[$i]);
            $code = $char - 32;
            if ($code < 0 || $code > 95) {
                $code = 0;
            }
            $codes[] = $code;
            $sum += $code * ($i + 1);
        }

        $check = $sum % 103;
        $codes[] = $check;
        $codes[] = 106; // Stop code

        $svg_bars = '';
        $x = 10;

        foreach ($codes as $code) {
            $pattern = $patterns[$code];
            $is_bar = true;
            for ($p = 0; $p < strlen($pattern); $p++) {
                $width = intval($pattern[$p]) * $scale;
                if ($is_bar) {
                    $svg_bars .= sprintf('<rect x="%d" y="0" width="%d" height="%d" fill="#000000" />', $x, $width, $height);
                }
                $x += $width;
                $is_bar = !$is_bar;
            }
        }

        $total_width = $x + 10;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" class="ipw-barcode-svg">%s<text x="%d" y="%d" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="bold" fill="#000">%s</text></svg>',
            $total_width,
            $height + 18,
            $total_width,
            $height + 18,
            $svg_bars,
            intval($total_width / 2),
            $height + 14,
            esc_html($text)
        );
    }
}
}
