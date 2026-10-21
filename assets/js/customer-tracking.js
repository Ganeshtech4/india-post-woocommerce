/**
 * Customer Tracking Frontend Script
 * Direct Mobile & AWB lookup with live tracking timeline rendering (NO OTP Required).
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $tracker = $('#ipw-customer-tracker');
        if (!$tracker.length) {
            return;
        }

        // 1. Tab Switching
        $('.ipw-tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            $('.ipw-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.ipw-tab-content').hide();
            $('#ipw-tab-' + tabId).fadeIn(200);
        });

        // 2. Track Another button
        $('#ipw-btn-track-another').on('click', function() {
            $('#ipw-shipments-results').slideUp(150);
            $('#ipw-step-phone').fadeIn(200);
            $('#ipw_input_mobile').val('').focus();
        });

        // 3. Direct Mobile Tracking Submission (NO OTP)
        $('#ipw-phone-form').on('submit', function(e) {
            e.preventDefault();
            var mobile = $('#ipw_input_mobile').val().replace(/\D/g, '');

            if (mobile.length !== 10 || ['6','7','8','9'].indexOf(mobile[0]) === -1) {
                showNotice('#ipw-phone-error', ipw_tracking_data.i18n.enter_valid_phone || 'Please enter a valid 10-digit Indian mobile number.');
                return;
            }

            hideNotice('#ipw-phone-error');
            setButtonLoading('#ipw-btn-track-phone', true, 'Searching Shipments...');
            $('#ipw-shipments-results').hide();

            $.ajax({
                url: ipw_tracking_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_public_track_by_phone',
                    nonce: ipw_tracking_data.nonce,
                    mobile: mobile
                },
                success: function(res) {
                    setButtonLoading('#ipw-btn-track-phone', false, 'Track Orders & Shipments &rarr;');
                    if (res.success) {
                        $('#ipw-step-phone').hide();
                        renderShipments(res.data.shipments, res.data.mobile_no);
                        $('#ipw-shipments-results').fadeIn(250);
                    } else {
                        showNotice('#ipw-phone-error', res.data.message || 'No shipments found for this mobile number.');
                    }
                },
                error: function() {
                    setButtonLoading('#ipw-btn-track-phone', false, 'Track Orders & Shipments &rarr;');
                    showNotice('#ipw-phone-error', 'Unable to reach tracking server. Please check your connection and try again.');
                }
            });
        });

        // 4. Direct Consignment Tracking Submission
        $('#ipw-direct-awb-form').on('submit', function(e) {
            e.preventDefault();
            var awb = $('#ipw_input_awb').val().trim().toUpperCase();

            if (awb.length !== 13) {
                showNotice('#ipw-awb-error', 'Please enter a valid 13-character Consignment number (e.g. ET214330013IN).');
                return;
            }

            hideNotice('#ipw-awb-error');
            setButtonLoading('#ipw-btn-direct-track', true, ipw_tracking_data.i18n.tracking || 'Fetching Live Tracking Events...');
            $('#ipw-direct-result').hide();

            $.ajax({
                url: ipw_tracking_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'ipw_public_direct_track',
                    nonce: ipw_tracking_data.nonce,
                    awb: awb
                },
                success: function(res) {
                    setButtonLoading('#ipw-btn-direct-track', false, 'Track Consignment &rarr;');
                    if (res.success) {
                        renderDirectTracking(res.data);
                        $('#ipw-direct-result').fadeIn(200);
                    } else {
                        showNotice('#ipw-awb-error', res.data.message);
                    }
                },
                error: function() {
                    setButtonLoading('#ipw-btn-direct-track', false, 'Track Consignment &rarr;');
                    showNotice('#ipw-awb-error', 'Unable to fetch tracking events.');
                }
            });
        });

        // Helper functions
        function showNotice(selector, text) {
            $(selector).text(text).fadeIn(150);
        }

        function hideNotice(selector) {
            $(selector).hide().text('');
        }

        function setButtonLoading(selector, loading, text) {
            var $b = $(selector);
            if (loading) {
                $b.prop('disabled', true);
                $b.find('.btn-text').html(text);
                $b.find('.btn-spinner').show();
            } else {
                $b.prop('disabled', false);
                $b.find('.btn-text').html(text);
                $b.find('.btn-spinner').hide();
            }
        }

        // Render Shipments Cards
        function renderShipments(shipments, mobile) {
            var $container = $('#ipw-shipments-cards-container');
            $container.empty();

            if (!shipments || shipments.length === 0) {
                $('#ipw-results-count-text').text('No shipments linked to +91 ' + mobile);
                $container.html('<div class="ipw-notice ipw-notice-error">' + (ipw_tracking_data.i18n.no_shipments || 'No shipments found.') + '</div>');
                return;
            }

            $('#ipw-results-count-text').text('Found ' + shipments.length + ' consignment(s) for +91 ' + mobile);

            $.each(shipments, function(idx, item) {
                var statusClass = 'status-' + (item.delivery_status || 'in_transit').toLowerCase();
                var statusLabel = formatStatusLabel(item.delivery_status);

                var cardHtml = '<div class="ipw-shipment-card">';
                cardHtml += '<div class="ipw-card-header">';
                cardHtml += '<div class="ipw-awb-title-wrap">';
                cardHtml += '<span class="ipw-awb-code">' + escapeHtml(item.barcode_no) + '</span>';
                cardHtml += '<span class="ipw-status-pill ' + statusClass + '">' + escapeHtml(statusLabel) + '</span>';
                cardHtml += '</div>';
                cardHtml += '<div class="ipw-card-meta">Order #' + escapeHtml(item.order_number) + ' • ' + escapeHtml(item.order_date) + '</div>';
                cardHtml += '</div>';

                cardHtml += '<div class="ipw-card-body">';
                cardHtml += '<div class="ipw-summary-grid">';
                cardHtml += '<div class="ipw-summary-item"><span class="label">Service</span><span class="val">' + escapeHtml(item.article_type) + '</span></div>';
                cardHtml += '<div class="ipw-summary-item"><span class="label">Booked Date</span><span class="val">' + escapeHtml(item.booking_date || 'Recent') + '</span></div>';
                cardHtml += '<div class="ipw-summary-item"><span class="label">Last Tracked</span><span class="val">' + escapeHtml(item.last_tracked) + '</span></div>';
                cardHtml += '</div>';

                // Timeline
                cardHtml += buildTimelineHtml(item.events, item.delivery_status);
                cardHtml += '</div>';
                cardHtml += '</div>';

                $container.append(cardHtml);
            });
        }

        // Render Direct Single Tracking Result
        function renderDirectTracking(data) {
            var $res = $('#ipw-direct-result');
            $res.empty();

            var statusLabel = formatStatusLabel(data.del_status);
            var statusClass = 'status-' + (data.del_status || 'in_transit').toLowerCase();

            var bkg = data.booking_details || {};
            var html = '<div class="ipw-shipment-card" style="margin-top:20px;">';
            html += '<div class="ipw-card-header">';
            html += '<div class="ipw-awb-title-wrap">';
            html += '<span class="ipw-awb-code">' + escapeHtml(data.consignment) + '</span>';
            html += '<span class="ipw-status-pill ' + statusClass + '">' + escapeHtml(statusLabel) + '</span>';
            html += '</div>';
            if (bkg.delivery_location) {
                html += '<div class="ipw-card-meta">Destination: ' + escapeHtml(bkg.delivery_location) + ' (' + escapeHtml(bkg.destination_pincode || '') + ')</div>';
            }
            html += '</div>';

            html += '<div class="ipw-card-body">';
            if (bkg.booked_at) {
                var rawSvc = bkg.article_type || '';
                var svcDisplay = (rawSvc === 'BUSINESS_PARCEL' || rawSvc.indexOf('BP') === 0 || (!rawSvc && (data.consignment || '').indexOf('BP') === 0)) ? 'Business Parcel' : (rawSvc === 'SP_INLAND_DOC' ? 'Speed Post' : (rawSvc || 'Business Parcel'));

                html += '<div class="ipw-summary-grid">';
                html += '<div class="ipw-summary-item"><span class="label">Booked At</span><span class="val">' + escapeHtml(bkg.booked_at) + '</span></div>';
                html += '<div class="ipw-summary-item"><span class="label">Destination</span><span class="val">' + escapeHtml(bkg.delivery_location || bkg.destination_pincode || 'India') + '</span></div>';
                html += '<div class="ipw-summary-item"><span class="label">Service</span><span class="val">' + escapeHtml(svcDisplay) + '</span></div>';
                html += '</div>';
            }

            html += buildTimelineHtml(data.tracking_details, data.del_status);
            html += '</div></div>';

            $res.html(html);
        }

        function formatStatusLabel(status) {
            var s = (status || '').toLowerCase().trim();
            if (s === 'booked') {
                return 'Booked (Awaiting Dispatch)';
            }
            if (s === 'in_transit') {
                return 'In Transit';
            }
            if (s === 'out_for_delivery') {
                return 'Out For Delivery';
            }
            if (s === 'delivered') {
                return 'Delivered';
            }
            return (status || 'In Transit').replace(/_/g, ' ');
        }

        function formatTimelineDate(dateStr, timeStr) {
            var dateLabel = '';
            var timeLabel = '';

            if (dateStr) {
                var str = String(dateStr).trim();
                var dmyMatch = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
                var ymdMatch = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/);
                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                if (dmyMatch) {
                    var day = parseInt(dmyMatch[1], 10);
                    var month = parseInt(dmyMatch[2], 10) - 1;
                    if (month >= 0 && month < 12) {
                        dateLabel = monthNames[month] + ' ' + day;
                    }
                } else if (ymdMatch) {
                    var day = parseInt(ymdMatch[3], 10);
                    var month = parseInt(ymdMatch[2], 10) - 1;
                    if (month >= 0 && month < 12) {
                        dateLabel = monthNames[month] + ' ' + day;
                    }
                }

                if (!dateLabel) {
                    var parsed = new Date(str);
                    if (!isNaN(parsed.getTime())) {
                        dateLabel = monthNames[parsed.getMonth()] + ' ' + parsed.getDate();
                        if (!timeStr) {
                            var h = String(parsed.getHours());
                            if (h.length < 2) h = '0' + h;
                            var m = String(parsed.getMinutes());
                            if (m.length < 2) m = '0' + m;
                            timeLabel = h + ':' + m;
                        }
                    }
                }

                if (!dateLabel) {
                    dateLabel = str.substring(0, 10);
                }
            }

            if (timeStr) {
                var tParts = String(timeStr).trim().split(':');
                if (tParts.length >= 2) {
                    timeLabel = tParts[0] + ':' + tParts[1];
                } else {
                    timeLabel = timeStr;
                }
            }

            return {
                date: dateLabel || 'Today',
                time: timeLabel || '--:--'
            };
        }

        function buildTimelineHtml(events, deliveryStatus) {
            if (!events || events.length === 0) {
                return '<div class="ipw-timeline-empty" style="padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-top:12px;"><p style="color:#475569; font-size:12px; margin:0; line-height:1.4;">📦 <strong>Consignment Booked:</strong> Parcel label created with India Post. The shipment is packed and waiting for courier dispatch. Live transit events will update automatically once scanned at the postal mail hub.</p></div>';
            }

            // Reverse to chronological order (Booked at top -> In Transit -> Out For Delivery -> Delivered)
            var chrono = events.slice().reverse();
            var delStatus = (deliveryStatus || '').toLowerCase();
            var isDelivered = (delStatus === 'delivered');

            // Detect if any event is already marked delivered
            $.each(chrono, function(i, ev) {
                var evName = (ev.event || '').toLowerCase();
                if (evName.indexOf('delivered') !== -1 && evName.indexOf('out') === -1) {
                    isDelivered = true;
                }
            });

            // If not yet delivered, append the final destination milestone as Pending
            if (!isDelivered) {
                chrono.push({
                    event: 'Item Delivered',
                    office: 'Doorstep handover to recipient',
                    isPending: true
                });
            }

            var html = '<div class="ipw-tl-card-section">';
            html += '<div class="ipw-tl-header-row">';
            html += '<h4 class="ipw-tl-heading">Tracking Journey</h4>';
            html += '<span class="ipw-tl-count">' + events.length + ' milestones</span>';
            html += '</div>';

            html += '<div class="ipw-timeline-scroll">';
            html += '<div class="ipw-timeline-list">';

            var totalSteps = chrono.length;

            $.each(chrono, function(idx, ev) {
                var isPending = (ev.isPending === true);
                var isInProcess = false;
                var isCompleted = false;

                if (isDelivered) {
                    // When fully delivered, all steps are completed
                    isCompleted = true;
                } else if (isPending) {
                    // Pending upcoming step
                    isPending = true;
                } else if (idx === totalSteps - 2) {
                    // The latest active event in progress
                    isInProcess = true;
                } else {
                    // Earlier steps are completed
                    isCompleted = true;
                }

                var isLast = (idx === totalSteps - 1);
                var stepClass = isPending ? 'is-pending' : (isInProcess ? 'is-in-process' : 'is-completed');

                var dt = isPending ? { date: 'Pending', time: '--:--' } : formatTimelineDate(ev.date, ev.time);
                var locationText = ev.office || '';
                if (ev.officeid) {
                    locationText += ' (' + ev.officeid + ')';
                }

                html += '<div class="ipw-tl-item ' + stepClass + ' ' + (isLast ? 'is-last' : '') + '">';
                
                // Left Column: Date & Time
                html += '<div class="ipw-tl-time-col">';
                html += '<span class="ipw-tl-date">' + escapeHtml(dt.date) + '</span>';
                html += '<span class="ipw-tl-time">' + escapeHtml(dt.time) + '</span>';
                html += '</div>';

                // Center Column: Track Dot & Line
                html += '<div class="ipw-tl-track-col">';
                html += '<div class="ipw-tl-dot"></div>';
                if (!isLast) {
                    html += '<div class="ipw-tl-line"></div>';
                }
                html += '</div>';

                // Right Column: Title & Location / Remarks
                html += '<div class="ipw-tl-content-col">';
                html += '<div class="ipw-tl-event">';
                html += '<span class="ipw-tl-event-text">' + escapeHtml(ev.event) + '</span>';
                if (isInProcess) {
                    html += '<span class="ipw-tl-active-badge">In Progress</span>';
                } else if (isCompleted) {
                    html += '<span class="ipw-tl-done-badge">Completed</span>';
                } else if (isPending) {
                    html += '<span class="ipw-tl-pending-badge">Upcoming</span>';
                }
                html += '</div>';
                if (locationText) {
                    html += '<div class="ipw-tl-location">' + escapeHtml(locationText) + '</div>';
                }
                if (ev.remarks) {
                    html += '<div class="ipw-tl-remarks">' + escapeHtml(ev.remarks) + '</div>';
                }
                html += '</div>';

                html += '</div>';
            });

            html += '</div>';
            html += '</div>';
            html += '</div>';

            return html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }
    });

})(jQuery);
