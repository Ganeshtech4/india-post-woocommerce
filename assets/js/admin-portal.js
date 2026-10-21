/**
 * India Post Admin Portal JavaScript
 * Pure inline feedback, zero browser alert popups, resilient diagnostics.
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Sidebar PIN Code link smooth scroll
        $('#ipw-sidebar-pincode-link').on('click', function(e) {
            e.preventDefault();
            var $target = $('#pincode-tool');
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 80
                }, 400);
                $('#ipw-quick-pin-input').focus();
            }
        });

        // 1. Test Connection Button (Inline feedback, NO browser alert)
        $('#ipw-btn-test-connection').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $feedback = $('#ipw-connection-test-result');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Testing API Connection...');
            $feedback.slideUp(100);

            $.ajax({
                url: ipw_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_test_api_connection',
                    nonce: ipw_admin_data.nonce
                },
                success: function(res) {
                    $btn.prop('disabled', false).text(originalText);
                    if (res.success) {
                        var html = '<div class="ipw-inline-alert ipw-alert-success">';
                        html += '<div class="alert-icon">✅</div>';
                        html += '<div class="alert-body">';
                        html += '<strong>' + escapeHtml(res.data.message) + '</strong>';
                        html += '<div class="alert-meta">';
                        html += '<span><strong>Latency:</strong> ' + res.data.latency + 'ms</span>';
                        html += '<span><strong>Environment:</strong> ' + res.data.env.toUpperCase() + '</span>';
                        html += '<span><strong>Token:</strong> <code>' + escapeHtml(res.data.token) + '</code></span>';
                        html += '</div></div>';
                        html += '<button type="button" class="alert-close" onclick="jQuery(\'#ipw-connection-test-result\').slideUp();">&times;</button>';
                        html += '</div>';
                        $feedback.html(html).slideDown(200);

                        // Update banner token status in place
                        $('.ipw-status-col .val.status-pending').removeClass('status-pending').addClass('status-active').text('Active (Cached)');
                    } else {
                        var errHtml = '<div class="ipw-inline-alert ipw-alert-error">';
                        errHtml += '<div class="alert-icon">❌</div>';
                        errHtml += '<div class="alert-body">';
                        errHtml += '<strong>Connection Failed:</strong> ' + escapeHtml(res.data.message || 'Unknown network error.');
                        errHtml += '<div class="alert-meta"><small>Please verify that your outbound server IP is whitelisted by CEPT India Post.</small></div>';
                        errHtml += '</div>';
                        errHtml += '<button type="button" class="alert-close" onclick="jQuery(\'#ipw-connection-test-result\').slideUp();">&times;</button>';
                        errHtml += '</div>';
                        $feedback.html(errHtml).slideDown(200);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    var errHtml = '<div class="ipw-inline-alert ipw-alert-error">';
                    errHtml += '<div class="alert-icon">⚠️</div>';
                    errHtml += '<div class="alert-body"><strong>Request Failed:</strong> Server network error. Check error logs.</div>';
                    errHtml += '</div>';
                    $feedback.html(errHtml).slideDown(200);
                }
            });
        });

        // 2. PIN Code Search Tool (Inline results & diagnostic feedback)
        $('#ipw-btn-lookup-pin').on('click', function(e) {
            e.preventDefault();
            var pin = $('#ipw-quick-pin-input').val().trim();
            var $results = $('#ipw-pincode-results');

            if (pin.length !== 6) {
                $results.html('<div class="ipw-inline-alert ipw-alert-warning"><div class="alert-icon">⚠️</div><div class="alert-body">Please enter a valid 6-digit Indian PIN code.</div></div>').slideDown(150);
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Validating with India Post...');
            $results.slideUp(100);

            $.ajax({
                url: ipw_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_admin_pincode_search',
                    nonce: ipw_admin_data.nonce,
                    pincode: pin
                },
                success: function(res) {
                    $btn.prop('disabled', false).text('Validate & Fetch Post Offices');
                    if (res.success && res.data.offices) {
                        var html = '';

                        // If sandbox fallback was triggered
                        if (res.data.notice) {
                            html += '<div class="ipw-inline-alert ipw-alert-info" style="margin-bottom:12px;">';
                            html += '<div class="alert-icon">ℹ️</div>';
                            html += '<div class="alert-body"><strong>' + escapeHtml(res.data.notice) + '</strong></div>';
                            html += '</div>';
                        }

                        html += '<div class="ipw-pincode-table-wrap">';
                        html += '<table class="wp-list-table widefat fixed striped ipw-admin-table">';
                        html += '<thead><tr><th>Office Name</th><th>Office ID</th><th>Type</th><th>City / Taluk</th><th>State</th><th>Delivery Hub</th></tr></thead><tbody>';

                        $.each(res.data.offices, function(idx, off) {
                            html += '<tr>';
                            html += '<td><strong>' + escapeHtml(off.office_name || 'N/A') + '</strong></td>';
                            html += '<td><code class="font-mono">' + escapeHtml(off.office_id || 'N/A') + '</code></td>';
                            html += '<td><span class="ipw-type-pill">' + escapeHtml(off.office_type_code || 'SPO') + '</span></td>';
                            html += '<td>' + escapeHtml(off.city_name || off.taluk_name || 'N/A') + '</td>';
                            html += '<td>' + escapeHtml(off.state_name || 'N/A') + '</td>';
                            html += '<td>' + (off.delivery_office_flag ? '<span class="status-ready" style="padding:2px 8px; border-radius:999px; font-weight:bold; font-size:11px;">✓ Delivery Office</span>' : '<span style="color:#94a3b8; font-size:11px;">Non-Delivery</span>') + '</td>';
                            html += '</tr>';
                        });

                        html += '</tbody></table></div>';
                        $results.html(html).slideDown(200);
                    } else {
                        var errHtml = '<div class="ipw-inline-alert ipw-alert-error">';
                        errHtml += '<div class="alert-icon">❌</div>';
                        errHtml += '<div class="alert-body">';
                        errHtml += '<strong>Lookup Error:</strong> ' + escapeHtml(res.data.message || 'No post offices found for this PIN code.');
                        errHtml += '</div></div>';
                        $results.html(errHtml).slideDown(200);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Validate & Fetch Post Offices');
                    $results.html('<div class="ipw-inline-alert ipw-alert-error"><div class="alert-icon">⚠️</div><div class="alert-body">Failed to contact India Post Pincode Search endpoint. Check server connectivity.</div></div>').slideDown(200);
                }
            });
        });

        // 3. Bulk Sync All Tracking (Inline feedback)
        $('#ipw-btn-bulk-sync').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('⏳ Syncing In-Transit...');

            $.ajax({
                url: ipw_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_sync_all_tracking',
                    nonce: ipw_admin_data.nonce
                },
                success: function(res) {
                    $btn.prop('disabled', false).text(originalText);
                    if (res.success) {
                        showGlobalToast('✅ ' + res.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        showGlobalToast('❌ ' + (res.data.message || 'Sync failed.'), 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    showGlobalToast('❌ Server error during tracking synchronization.', 'error');
                }
            });
        });

        // 4. Order Metabox: Book Shipment
        $('.ipw-btn-book').on('click', function(e) {
            e.preventDefault();
            var $wrap = $(this).closest('.ipw-metabox-wrapper');
            var orderId = $(this).data('order-id');

            var articleType   = $wrap.find('#ipw_article_type').val();
            var weight        = $wrap.find('#ipw_weight').val();
            var pickupDropoff = $wrap.find('#ipw_pickup_dropoff').val();
            var dimL          = $wrap.find('#ipw_dim_l').val();
            var dimW          = $wrap.find('#ipw_dim_w').val();
            var dimH          = $wrap.find('#ipw_dim_h').val();
            var customAwb     = $wrap.find('#ipw_custom_awb').val();

            $wrap.find('.ipw-ajax-spinner').show();
            $wrap.find('.ipw-btn-book').prop('disabled', true);
            $wrap.find('.ipw-ajax-msg').hide().empty();

            var nonce = $wrap.find('#ipw_nonce').val() || $wrap.find('input[name="ipw_nonce"]').val() || $('#ipw_nonce').val() || (typeof ipw_admin_data !== 'undefined' ? ipw_admin_data.nonce : '');

            $.ajax({
                url: ipw_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_book_order_shipment',
                    nonce: nonce,
                    order_id: orderId,
                    article_type: articleType,
                    weight: weight,
                    pickup_dropoff: pickupDropoff,
                    dim_l: dimL,
                    dim_w: dimW,
                    dim_h: dimH,
                    custom_awb: customAwb
                },
                success: function(res) {
                    $wrap.find('.ipw-ajax-spinner').hide();
                    $wrap.find('.ipw-btn-book').prop('disabled', false);
                    if (res.success) {
                        $wrap.find('.ipw-ajax-msg').html('<div class="ipw-inline-alert ipw-alert-success"><div class="alert-icon">✅</div><div class="alert-body">' + escapeHtml(res.data.message) + '</div></div>').show();
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        $wrap.find('.ipw-ajax-msg').html('<div class="ipw-inline-alert ipw-alert-error"><div class="alert-icon">❌</div><div class="alert-body">' + escapeHtml(res.data.message) + '</div></div>').show();
                    }
                },
                error: function(xhr) {
                    $wrap.find('.ipw-ajax-spinner').hide();
                    $wrap.find('.ipw-btn-book').prop('disabled', false);
                    var msg = 'Server error during booking request.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        msg = xhr.responseJSON.data.message;
                    } else if (xhr.status === 403) {
                        msg = 'Session security check failed. Please refresh the order page.';
                    } else if (xhr.responseText) {
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed.data && parsed.data.message) {
                                msg = parsed.data.message;
                            }
                        } catch(e) {}
                    }
                    $wrap.find('.ipw-ajax-msg').html('<div class="ipw-inline-alert ipw-alert-error"><div class="alert-icon">⚠️</div><div class="alert-body">' + escapeHtml(msg) + '</div></div>').show();
                }
            });
        });

        // 5. Order Metabox & Table: Refresh Tracking
        $(document).on('click', '.ipw-btn-refresh-tracking, .ipw-btn-refresh-row', function(e) {
            e.preventDefault();
            var orderId = $(this).data('order-id');
            var $btn = $(this);
            var orig = $btn.text();
            $btn.prop('disabled', true).text('⏳ Syncing...');

            var nonce = $('#ipw_nonce').val() || ipw_admin_data.nonce;

            $.ajax({
                url: ipw_admin_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_refresh_order_tracking',
                    nonce: nonce,
                    order_id: orderId
                },
                success: function(res) {
                    $btn.prop('disabled', false).text(orig);
                    if (res.success) {
                        showGlobalToast('✅ Status: ' + res.data.status.toUpperCase(), 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showGlobalToast('❌ ' + (res.data.message || 'Tracking refresh failed'), 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(orig);
                    showGlobalToast('❌ Server error during tracking check.', 'error');
                }
            });
        });

        // Global Copy Button Click handler
        $(document).on('click', '.ipw-copy-ip-btn, .ipw-btn-icon-copy, .ipw-btn-copy', function(e) {
            e.preventDefault();
            var $b = $(this);
            var textToCopy = $b.data('copy-val');

            if (!textToCopy) {
                var $input = $b.siblings('input');
                if ($input.length) {
                    textToCopy = $input.val();
                } else if ($b.attr('id') === 'ipw-server-ip-copy') {
                    textToCopy = $('#ipw-server-ip').text().trim();
                }
            }

            if (textToCopy) {
                navigator.clipboard.writeText(textToCopy);
                var orig = $b.text();
                $b.text('✓ Copied!');
                setTimeout(function() {
                    $b.text(orig);
                }, 1400);
            }
        });

        // 5. Multi-Label Bulk Selection (Shipments Screen)
        function updateShipmentsBulkBar() {
            var $checked = $('.ipw-shipment-select-cb:checked');
            var count = $checked.length;
            var total = $('.ipw-shipment-select-cb').length;

            if (count > 0) {
                $('#ipw-shipments-selected-count').text(count + ' shipment' + (count > 1 ? 's' : '') + ' selected');
                $('#ipw-bulk-shipments-bar').slideDown(150).css('display', 'flex');
            } else {
                $('#ipw-bulk-shipments-bar').slideUp(150);
            }

            $('#ipw-cb-select-all-shipments').prop('checked', count > 0 && count === total);
        }

        $('#ipw-cb-select-all-shipments').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.ipw-shipment-select-cb').prop('checked', isChecked);
            updateShipmentsBulkBar();
        });

        $(document).on('change', '.ipw-shipment-select-cb', function() {
            updateShipmentsBulkBar();
        });

        $('#ipw-btn-select-all-shipments').on('click', function() {
            $('.ipw-shipment-select-cb').prop('checked', true);
            updateShipmentsBulkBar();
        });

        $('#ipw-btn-deselect-all-shipments').on('click', function() {
            $('.ipw-shipment-select-cb').prop('checked', false);
            updateShipmentsBulkBar();
        });

        $('#ipw-btn-print-sheet').on('click', function(e) {
            e.preventDefault();
            var orderIds = [];
            $('.ipw-shipment-select-cb:checked').each(function() {
                orderIds.push($(this).val());
            });

            if (!orderIds.length) {
                showGlobalToast('⚠️ Please select at least one shipment to print.', 'error');
                return;
            }

            var url = ipw_admin_data.ajax_url.replace('admin-ajax.php', 'admin.php') + '?action=ipw_print_bulk_labels&order_ids=' + encodeURIComponent(orderIds.join(',')) + '&format=sheet';
            window.open(url, '_blank');
        });

        $('#ipw-btn-print-thermal').on('click', function(e) {
            e.preventDefault();
            var orderIds = [];
            $('.ipw-shipment-select-cb:checked').each(function() {
                orderIds.push($(this).val());
            });

            if (!orderIds.length) {
                showGlobalToast('⚠️ Please select at least one shipment to print.', 'error');
                return;
            }

            var url = ipw_admin_data.ajax_url.replace('admin-ajax.php', 'admin.php') + '?action=ipw_print_bulk_labels&order_ids=' + encodeURIComponent(orderIds.join(',')) + '&format=thermal';
            window.open(url, '_blank');
        });

        // 6. Multi-Label Selection (Dashboard Screen)
        function updateDashboardBulkBar() {
            var $checked = $('.ipw-dash-row-cb:checked');
            var count = $checked.length;
            var total = $('.ipw-dash-row-cb').length;

            if (count > 0) {
                $('#ipw-dash-selected-count').text(count + ' selected');
                $('#ipw-dash-bulk-wrap').fadeIn(150).css('display', 'inline-flex');
            } else {
                $('#ipw-dash-bulk-wrap').fadeOut(150);
            }

            $('#ipw-cb-dash-select-all').prop('checked', count > 0 && count === total);
        }

        $('#ipw-cb-dash-select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.ipw-dash-row-cb').prop('checked', isChecked);
            updateDashboardBulkBar();
        });

        $(document).on('change', '.ipw-dash-row-cb', function() {
            updateDashboardBulkBar();
        });

        $('#ipw-dash-btn-print-sheet').on('click', function(e) {
            e.preventDefault();
            var orderIds = [];
            $('.ipw-dash-row-cb:checked').each(function() {
                orderIds.push($(this).val());
            });

            if (!orderIds.length) {
                showGlobalToast('⚠️ Please select at least one shipment to print.', 'error');
                return;
            }

            var url = ipw_admin_data.ajax_url.replace('admin-ajax.php', 'admin.php') + '?action=ipw_print_bulk_labels&order_ids=' + encodeURIComponent(orderIds.join(',')) + '&format=sheet';
            window.open(url, '_blank');
        });

    });

    // Inline Toast System (Replaces browser alert popups)
    window.showGlobalToast = function(message, type) {
        var $toast = $('#ipw-global-toast');
        if (!$toast.length) {
            $('body').append('<div id="ipw-global-toast" class="ipw-toast-container"></div>');
            $toast = $('#ipw-global-toast');
        }

        var toastId = 'toast_' + Date.now();
        var toastClass = type === 'error' ? 'toast-error' : 'toast-success';
        var html = '<div id="' + toastId + '" class="ipw-toast-item ' + toastClass + '">' + escapeHtml(message) + '</div>';

        $toast.append(html);
        var $el = $('#' + toastId);
        $el.fadeIn(200);

        setTimeout(function() {
            $el.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3500);
    };

    // Cancellation Handler without browser alert
    window.ipwCancelBooking = function(orderId) {
        var nonce = jQuery('#ipw_nonce').val() || ipw_admin_data.nonce;
        jQuery.ajax({
            url: ipw_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'ipw_cancel_order_shipment',
                nonce: nonce,
                order_id: orderId
            },
            success: function(res) {
                if (res.success) {
                    showGlobalToast('✅ Booking reset successfully.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showGlobalToast('❌ ' + (res.data.message || 'Failed to cancel booking.'), 'error');
                }
            }
        });
    };

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

})(jQuery);
