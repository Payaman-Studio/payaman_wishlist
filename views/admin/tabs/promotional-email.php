<?php
if (! defined('ABSPATH')) {
    exit;
}

$campaigns_obj = new Payaman_Wishlist_Campaigns();
$campaigns = $campaigns_obj->get_all();
$promo_nonce = wp_create_nonce('payaman_wishlist_promo_email');
?>
<div class="form-section" id="tab-promotional-email" style="display: none;">

<div id="promo-sub-campaigns">

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2><?php esc_html_e('Email Campaigns', 'payaman_wishlist'); ?></h2>
        <div style="display: flex; gap: 6px; align-items: center;">
            <button type="button" id="payaman_process_due" class="button">
                <?php esc_html_e('Process Scheduled', 'payaman_wishlist'); ?>
            </button>
            <span id="payaman_process_loading" style="display:none;"><span class="spinner is-active"></span></span>
            <button type="button" id="payaman_refresh_table" class="button" title="<?php esc_attr_e('Refresh table', 'payaman_wishlist'); ?>">
                &#x21bb; <?php esc_html_e('Refresh', 'payaman_wishlist'); ?>
            </button>
            <span id="payaman_refresh_loading" style="display:none;"><span class="spinner is-active"></span></span>
            <button type="button" id="payaman_new_campaign" class="button button-primary">
                <?php esc_html_e('New Campaign', 'payaman_wishlist'); ?>
            </button>
        </div>
    </div>

    <div id="payaman_promo_status" class="notice" style="display: none; margin-bottom: 20px;"></div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Campaign', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Products', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Type', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Next Send', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Status', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Sent', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Created', 'payaman_wishlist'); ?></th>
                <th><?php esc_html_e('Actions', 'payaman_wishlist'); ?></th>
            </tr>
        </thead>
        <tbody id="payaman_campaigns_tbody">
            <?php if (! empty($campaigns)) : ?>
                <?php foreach ($campaigns as $c) :
                    $type_label = __('Immediate', 'payaman_wishlist');
                    if ($c['send_type'] === 'scheduled') {
                        $type_label = $c['repeat_interval']
                            ? sprintf(__('Repeat %s', 'payaman_wishlist'), ucfirst($c['repeat_interval']))
                            : __('Scheduled', 'payaman_wishlist');
                    }
                ?>
                    <tr data-campaign-id="<?php echo esc_attr($c['id']); ?>">
                        <td><strong><?php echo esc_html($c['name']); ?></strong></td>
                        <td><?php echo count($c['product_ids']); ?></td>
                        <td><?php echo esc_html($type_label); ?></td>
                        <td>
                                <?php if ($c['status'] === 'scheduled' && $c['scheduled_at']) : ?>
                                    <?php echo esc_html(payaman_wishlist_utc_to_wp_timezone($c['scheduled_at'])); ?>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        <td>
                            <span class="badge" style="background: <?php echo $c['status'] === 'sent' ? '#46b450' : ($c['status'] === 'scheduled' ? '#0073aa' : ($c['status'] === 'paused' ? '#f0ad4e' : '#ccc')); ?>; color: #fff; padding: 2px 10px; border-radius: 10px;">
                                <?php echo esc_html(ucfirst($c['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($c['total_sent']); ?>/<?php echo esc_html($c['total_targeted']); ?></td>
                        <td><?php echo esc_html($c['created_at']); ?></td>
                        <td class="payaman-actions">
                            <button type="button" class="button payaman-btn-icon payaman-edit-campaign" title="<?php esc_attr_e('Edit', 'payaman_wishlist'); ?>" data-id="<?php echo esc_attr($c['id']); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <?php if ($c['status'] === 'scheduled') : ?>
                                <button type="button" class="button payaman-btn-icon payaman-pause-campaign" title="<?php esc_attr_e('Pause', 'payaman_wishlist'); ?>" data-id="<?php echo esc_attr($c['id']); ?>">
                                    <span class="dashicons dashicons-controls-pause"></span>
                                </button>
                            <?php elseif ($c['status'] === 'paused') : ?>
                                <button type="button" class="button payaman-btn-icon payaman-resume-campaign" title="<?php esc_attr_e('Resume', 'payaman_wishlist'); ?>" data-id="<?php echo esc_attr($c['id']); ?>">
                                    <span class="dashicons dashicons-controls-play"></span>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="button payaman-btn-icon payaman-send-campaign" title="<?php esc_attr_e('Send Now', 'payaman_wishlist'); ?>" data-id="<?php echo esc_attr($c['id']); ?>">
                                <span class="dashicons dashicons-email-alt"></span>
                            </button>
                            <button type="button" class="button payaman-btn-icon payaman-delete-campaign" title="<?php esc_attr_e('Delete', 'payaman_wishlist'); ?>" data-id="<?php echo esc_attr($c['id']); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <span class="campaign-loading" style="display:none;"><span class="spinner is-active"></span></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr id="payaman_no_campaigns">
                    <td colspan="8"><?php esc_html_e('No campaigns yet.', 'payaman_wishlist'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="payaman_campaign_modal" class="payaman-modal-overlay" style="display:none;">
    <div class="payaman-modal">
        <div class="payaman-modal-header">
            <h3 id="payaman_modal_title"><?php esc_html_e('New Campaign', 'payaman_wishlist'); ?></h3>
            <button type="button" class="payaman-modal-close">&times;</button>
        </div>
        <div class="payaman-modal-body">
            <input type="hidden" id="edit_campaign_id" value="">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="campaign_name"><?php esc_html_e('Campaign Name', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <input type="text" id="campaign_name" class="large-text" placeholder="e.g. Summer Sale 2026">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="campaign_products"><?php esc_html_e('Products', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <select id="campaign_products" class="wc-product-search" multiple="multiple" style="width: 400px;" data-placeholder="<?php esc_attr_e('Search products...', 'payaman_wishlist'); ?>" data-action="woocommerce_json_search_products_and_variations"></select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Send Type', 'payaman_wishlist'); ?></th>
                    <td>
                        <label style="margin-right: 20px;">
                            <input type="radio" name="campaign_send_type" value="immediate" checked>
                            <?php esc_html_e('Send Immediately', 'payaman_wishlist'); ?>
                        </label>
                        <label>
                            <input type="radio" name="campaign_send_type" value="scheduled">
                            <?php esc_html_e('Schedule', 'payaman_wishlist'); ?>
                        </label>
                    </td>
                </tr>
                <tr id="campaign_schedule_row" style="display:none;">
                    <th scope="row"><label for="campaign_scheduled_at"><?php esc_html_e('Schedule Date & Time', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <input type="datetime-local" id="campaign_scheduled_at" class="regular-text">
                    </td>
                </tr>
                <tr id="campaign_repeat_row" style="display:none;">
                    <th scope="row"><label for="campaign_repeat"><?php esc_html_e('Repeat', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <select id="campaign_repeat">
                            <option value=""><?php esc_html_e('No repeat', 'payaman_wishlist'); ?></option>
                            <option value="daily"><?php esc_html_e('Daily', 'payaman_wishlist'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly', 'payaman_wishlist'); ?></option>
                            <option value="monthly"><?php esc_html_e('Monthly', 'payaman_wishlist'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="campaign_subject"><?php esc_html_e('Email Subject', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <input type="text" id="campaign_subject" class="large-text" value="<?php esc_attr_e('Special offer on your wishlisted items, {user_name}!', 'payaman_wishlist'); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="campaign_body"><?php esc_html_e('Email Body', 'payaman_wishlist'); ?></label></th>
                    <td>
                        <textarea id="campaign_body" rows="10" class="large-text"><?php
                            echo esc_textarea(__("Hi {user_name},\n\nGreat news! We have special offers on {count} product(s) from your wishlist:\n\n{products_list}\n\nDon't miss out — check them out now!\n\nBest regards,\n{site_name}", 'payaman_wishlist'));
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Tags:', 'payaman_wishlist'); ?>
                            <code>{user_name}</code> <code>{site_name}</code> <code>{count}</code> <code>{products_list}</code>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="payaman-modal-footer">
            <button type="button" class="button" id="payaman_modal_cancel"><?php esc_html_e('Cancel', 'payaman_wishlist'); ?></button>
            <button type="button" class="button button-primary" id="payaman_modal_save">
                <?php esc_html_e('Save Campaign', 'payaman_wishlist'); ?>
            </button>
            <span id="payaman_campaign_loading" style="display: none; margin-left: 10px; vertical-align: middle;">
                <span class="spinner is-active"></span>
            </span>
        </div>
    </div>
</div>

<style>
.payaman-modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 100000;
    display: flex; align-items: center; justify-content: center;
}
.payaman-modal {
    background: #fff; border-radius: 4px;
    max-width: 700px; width: 90%; max-height: 90vh;
    display: flex; flex-direction: column;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.payaman-modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 15px 20px; border-bottom: 1px solid #ddd;
}
.payaman-modal-header h3 { margin: 0; }
.payaman-modal-close {
    background: none; border: none; font-size: 24px;
    cursor: pointer; color: #666; padding: 0; line-height: 1;
}
.payaman-modal-close:hover { color: #333; }
.payaman-modal-body {
    padding: 20px; overflow-y: auto; flex: 1;
}
.payaman-modal-body .form-table { margin: 0; }
.payaman-modal-body .form-table th {
    width: 140px; padding: 10px 10px 10px 0;
}
.payaman-modal-body .form-table td { padding: 10px 0; }
.payaman-modal-footer {
    display: flex; align-items: center; justify-content: flex-end;
    padding: 12px 20px; border-top: 1px solid #ddd; gap: 8px;
}
.payaman-actions { white-space: nowrap; }
.payaman-btn-icon {
    min-width: 0 !important; padding: 0 6px !important;
    height: 28px !important; line-height: 26px !important;
}
.payaman-btn-icon .dashicons {
    font-size: 16px; width: 16px; height: 16px;
    vertical-align: middle; margin-top: -2px;
}
.payaman-btn-icon:hover .dashicons { opacity: 0.8; }
</style>

<script>
jQuery(function($) {
    var promoNonce = '<?php echo esc_js($promo_nonce); ?>';
    var $status = $('#payaman_promo_status');

    function showStatus(msg, type) {
        $status.removeClass('notice-success notice-error').addClass('notice-' + type)
            .html('<p>' + msg + '</p>').show();
    }

    function openModal(title) {
        $('#payaman_modal_title').text(title);
        $('#payaman_campaign_modal').fadeIn(150);
    }

    function closeModal() {
        $('#payaman_campaign_modal').fadeOut(100);
    }

    function resetForm() {
        $('#edit_campaign_id').val('');
        $('#campaign_name, #campaign_subject, #campaign_body').val('');
        $('#campaign_products').val(null).trigger('change');
        $('#campaign_scheduled_at').val('');
        $('#campaign_repeat').val('');
        $('input[name="campaign_send_type"][value="immediate"]').prop('checked', true);
        $('#campaign_schedule_row, #campaign_repeat_row').hide();
    }

    $('input[name="campaign_send_type"]').on('change', function() {
        $('#campaign_schedule_row, #campaign_repeat_row').toggle($(this).val() === 'scheduled');
    });

    $('#payaman_new_campaign').on('click', function() {
        resetForm();
        openModal('<?php echo esc_js(__('New Campaign', 'payaman_wishlist')); ?>');
    });

    $('.payaman-modal-close, #payaman_modal_cancel').on('click', closeModal);

    $(document).on('click', '.payaman-edit-campaign', function() {
        var id = $(this).data('id');
        var $btn = $(this);

        $.post(ajaxurl, {
            action: 'payaman_wishlist_get_campaign',
            campaign_id: id,
            nonce: promoNonce
        }, function(res) {
            if (!res.success) return;

            var c = res.data;
            resetForm();
            $('#edit_campaign_id').val(c.id);
            $('#campaign_name').val(c.name);
            $('#campaign_subject').val(c.subject);
            $('#campaign_body').val(c.body);

            var productIds = c.product_ids || [];
            if (productIds.length) {
                var $sel = $('#campaign_products');
                $.each(productIds, function(i, pid) {
                    var option = new Option(c.product_names[i] || '#' + pid, pid, true, true);
                    $sel.append(option).trigger('change');
                });
            }

            if (c.send_type === 'scheduled') {
                $('input[name="campaign_send_type"][value="scheduled"]').prop('checked', true).trigger('change');
                if (c.scheduled_at) {
                    $('#campaign_scheduled_at').val(utcToLocalDatetime(c.scheduled_at));
                }
                if (c.repeat_interval) {
                    $('#campaign_repeat').val(c.repeat_interval);
                }
            }

            openModal('<?php echo esc_js(__('Edit Campaign', 'payaman_wishlist')); ?>');
        });
    });

    function getTzOffset() {
        return new Date().getTimezoneOffset();
    }

    function utcToLocalDatetime(utcStr) {
        if (!utcStr) return '';
        var d = new Date(utcStr.replace(' ', 'T') + 'Z');
        if (isNaN(d.getTime())) return utcStr;
        var pad = function(n) { return n < 10 ? '0' + n : n; };
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    $('#payaman_modal_save').on('click', function() {
        var editId = $('#edit_campaign_id').val();
        var name = $('#campaign_name').val();
        var products = $('#campaign_products').val();
        var subject = $('#campaign_subject').val();
        var body = $('#campaign_body').val();
        var sendType = $('input[name="campaign_send_type"]:checked').val();
        var scheduledAt = $('#campaign_scheduled_at').val() || '';
        var repeat = $('#campaign_repeat').val() || '';
        var tzOffset = getTzOffset();

        if (!name || !products || products.length === 0 || !subject || !body) {
            showStatus('<?php echo esc_js(__('Please fill in all fields.', 'payaman_wishlist')); ?>', 'error');
            return;
        }

        if (sendType === 'scheduled' && !scheduledAt) {
            showStatus('<?php echo esc_js(__('Please select a schedule date and time.', 'payaman_wishlist')); ?>', 'error');
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#payaman_campaign_loading').show();

        var data = {
            action: editId ? 'payaman_wishlist_update_campaign' : 'payaman_wishlist_save_campaign',
            name: name,
            product_ids: products,
            subject: subject,
            body: body,
            send_type: sendType,
            scheduled_at: scheduledAt,
            repeat_interval: repeat,
            tz_offset: tzOffset,
            nonce: promoNonce
        };

        if (editId) {
            data.campaign_id = editId;
        }

        $.post(ajaxurl, data, function(res) {
            $('#payaman_campaign_loading').hide();
            $btn.prop('disabled', false);

            if (res.success) {
                closeModal();
                showStatus(res.data.message, 'success');
                refreshTable();
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error saving campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        }).fail(function() {
            $('#payaman_campaign_loading').hide();
            $btn.prop('disabled', false);
            showStatus('<?php echo esc_js(__('Request failed.', 'payaman_wishlist')); ?>', 'error');
        });
    });

    $(document).on('click', '.payaman-send-campaign', function() {
        if (!confirm('<?php echo esc_js(__('Send this campaign now?', 'payaman_wishlist')); ?>')) {
            return;
        }

        var $btn = $(this);
        var id = $btn.data('id');
        var $row = $btn.closest('tr');

        $btn.prop('disabled', true);
        $row.find('.campaign-loading').show();

        $.post(ajaxurl, {
            action: 'payaman_wishlist_send_campaign',
            campaign_id: id,
            nonce: promoNonce
        }, function(res) {
            $row.find('.campaign-loading').hide();
            $btn.prop('disabled', false);

            if (res.success) {
                showStatus(res.data.message, 'success');
                refreshTable();
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error sending campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        }).fail(function() {
            $row.find('.campaign-loading').hide();
            $btn.prop('disabled', false);
            showStatus('<?php echo esc_js(__('Request failed.', 'payaman_wishlist')); ?>', 'error');
        });
    });

    function refreshTable() {
        var $refreshBtn = $('#payaman_refresh_table');
        var $refreshLoading = $('#payaman_refresh_loading');
        $refreshBtn.prop('disabled', true);
        $refreshLoading.show();

        $.post(ajaxurl, {
            action: 'payaman_wishlist_get_campaigns',
            nonce: promoNonce
        }, function(res) {
            $refreshLoading.hide();
            $refreshBtn.prop('disabled', false);

            if (!res.success) return;

            var $tbody = $('#payaman_campaigns_tbody');
            $tbody.empty();

            if (!res.data || res.data.length === 0) {
                $tbody.html('<tr><td colspan="8"><?php echo esc_js(__('No campaigns yet.', 'payaman_wishlist')); ?></td></tr>');
                return;
            }

            $.each(res.data, function(i, c) {
                var statusColor = c.status === 'sent' ? '#46b450' : (c.status === 'scheduled' ? '#0073aa' : (c.status === 'paused' ? '#f0ad4e' : '#ccc'));
                var actions = '';
                var cap = function(s) { return s.charAt(0).toUpperCase() + s.slice(1); };

                actions += '<button type="button" class="button payaman-btn-icon payaman-edit-campaign" title="<?php echo esc_js(__('Edit', 'payaman_wishlist')); ?>" data-id="' + c.id + '"><span class="dashicons dashicons-edit"></span></button> ';

                if (c.status === 'scheduled') {
                    actions += '<button type="button" class="button payaman-btn-icon payaman-pause-campaign" title="<?php echo esc_js(__('Pause', 'payaman_wishlist')); ?>" data-id="' + c.id + '"><span class="dashicons dashicons-controls-pause"></span></button> ';
                } else if (c.status === 'paused') {
                    actions += '<button type="button" class="button payaman-btn-icon payaman-resume-campaign" title="<?php echo esc_js(__('Resume', 'payaman_wishlist')); ?>" data-id="' + c.id + '"><span class="dashicons dashicons-controls-play"></span></button> ';
                }

                actions += '<button type="button" class="button payaman-btn-icon payaman-send-campaign" title="<?php echo esc_js(__('Send Now', 'payaman_wishlist')); ?>" data-id="' + c.id + '"><span class="dashicons dashicons-email-alt"></span></button> ';

                actions += '<button type="button" class="button payaman-btn-icon payaman-delete-campaign" title="<?php echo esc_js(__('Delete', 'payaman_wishlist')); ?>" data-id="' + c.id + '"><span class="dashicons dashicons-trash"></span></button> ';
                actions += '<span class="campaign-loading" style="display:none;"><span class="spinner is-active"></span></span>';

                $tbody.append(
                    '<tr data-campaign-id="' + c.id + '">' +
                    '<td><strong>' + $('<span>').text(c.name).html() + '</strong></td>' +
                    '<td>' + (c.product_ids ? c.product_ids.length : 0) + '</td>' +
                    '<td>' + c.type_display + '</td>' +
                    '<td>' + c.next_send_display + '</td>' +
                    '<td><span class="badge" style="background:' + statusColor + ';color:#fff;padding:2px 10px;border-radius:10px;">' + cap(c.status) + '</span></td>' +
                    '<td>' + c.total_sent + '/' + c.total_targeted + '</td>' +
                    '<td>' + c.created_at + '</td>' +
                    '<td>' + actions + '</td>' +
                    '</tr>'
                );
            });
        }).fail(function() {
            $refreshLoading.hide();
            $refreshBtn.prop('disabled', false);
        });
    }

    $('#payaman_process_due').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        $('#payaman_process_loading').show();

        $.post(ajaxurl, {
            action: 'payaman_wishlist_process_due',
            nonce: promoNonce
        }, function(res) {
            $('#payaman_process_loading').hide();
            $btn.prop('disabled', false);

            if (res.success) {
                showStatus(res.data.message, 'success');
                refreshTable();
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error.', 'payaman_wishlist')); ?>', 'error');
            }
        }).fail(function() {
            $('#payaman_process_loading').hide();
            $btn.prop('disabled', false);
            showStatus('<?php echo esc_js(__('Request failed.', 'payaman_wishlist')); ?>', 'error');
        });
    });

    $('#payaman_refresh_table').on('click', function() {
        refreshTable();
    });

    $(document).on('click', '.payaman-delete-campaign', function() {
        if (!confirm('<?php echo esc_js(__('Delete this campaign permanently?', 'payaman_wishlist')); ?>')) {
            return;
        }

        var $btn = $(this);
        var id = $btn.data('id');
        var $row = $btn.closest('tr');

        $.post(ajaxurl, {
            action: 'payaman_wishlist_delete_campaign',
            campaign_id: id,
            nonce: promoNonce
        }, function(res) {
            if (res.success) {
                $row.fadeOut(function() { $(this).remove(); });
                if ($('#payaman_campaigns_tbody tr').length === 0) {
                    $('#payaman_campaigns_tbody').html('<tr><td colspan="8"><?php echo esc_js(__('No campaigns yet.', 'payaman_wishlist')); ?></td></tr>');
                }
                showStatus('<?php echo esc_js(__('Campaign deleted.', 'payaman_wishlist')); ?>', 'success');
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error deleting campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        });
    });

    $(document).on('click', '.payaman-pause-campaign', function() {
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'payaman_wishlist_pause_campaign',
            campaign_id: id,
            nonce: promoNonce
        }, function(res) {
            if (res.success) {
                showStatus(res.data.message, 'success');
                refreshTable();
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error.', 'payaman_wishlist')); ?>', 'error');
            }
        });
    });

    $(document).on('click', '.payaman-resume-campaign', function() {
        var id = $(this).data('id');

        $.post(ajaxurl, {
            action: 'payaman_wishlist_resume_campaign',
            campaign_id: id,
            nonce: promoNonce
        }, function(res) {
            if (res.success) {
                showStatus(res.data.message, 'success');
                refreshTable();
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error.', 'payaman_wishlist')); ?>', 'error');
            }
        });
    });
});
</script>
</div>
