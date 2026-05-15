<?php
if (! defined('ABSPATH')) {
    exit;
}

$campaigns_obj = new Payaman_Wishlist_Campaigns();
$campaigns = $campaigns_obj->get_all();
$promo_nonce = wp_create_nonce('payaman_wishlist_promo_email');
?>
<div class="form-section" id="tab-promotional-email" style="display: none;">

    <nav class="payaman-sub-nav">
        <a href="#promo-sub-campaigns" class="payaman-sub-link active" data-target="promo-sub-campaigns">
            <?php esc_html_e('All Campaigns', 'payaman_wishlist'); ?>
        </a> |
        <a href="#promo-sub-create" class="payaman-sub-link" data-target="promo-sub-create">
            <?php esc_html_e('Create Campaign', 'payaman_wishlist'); ?>
        </a>
    </nav>

    <div id="promo-sub-campaigns" class="payaman-sub-content">
        <h2><?php esc_html_e('Email Campaigns', 'payaman_wishlist'); ?></h2>

        <div id="payaman_promo_status" class="notice" style="display: none; margin-bottom: 20px;"></div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Campaign', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Products', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Status', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Targeted', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Sent', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Created', 'payaman_wishlist'); ?></th>
                    <th><?php esc_html_e('Actions', 'payaman_wishlist'); ?></th>
                </tr>
            </thead>
            <tbody id="payaman_campaigns_tbody">
                <?php if (! empty($campaigns)) : ?>
                    <?php foreach ($campaigns as $c) : ?>
                        <tr data-campaign-id="<?php echo esc_attr($c['id']); ?>">
                            <td><strong><?php echo esc_html($c['name']); ?></strong></td>
                            <td><?php echo count($c['product_ids']); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo $c['status'] === 'sent' ? '#46b450' : '#ccc'; ?>; color: #fff; padding: 2px 10px; border-radius: 10px;">
                                    <?php echo esc_html(ucfirst($c['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($c['total_targeted']); ?></td>
                            <td><?php echo esc_html($c['total_sent']); ?></td>
                            <td><?php echo esc_html($c['created_at']); ?></td>
                            <td>
                                <button type="button" class="button button-small payaman-send-campaign" data-id="<?php echo esc_attr($c['id']); ?>">
                                    <?php esc_html_e('Send Now', 'payaman_wishlist'); ?>
                                </button>
                                <button type="button" class="button button-small payaman-delete-campaign" data-id="<?php echo esc_attr($c['id']); ?>">
                                    <?php esc_html_e('Delete', 'payaman_wishlist'); ?>
                                </button>
                                <span class="campaign-loading" style="display:none;"><span class="spinner is-active"></span></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="payaman_no_campaigns">
                        <td colspan="7"><?php esc_html_e('No campaigns yet. Create one to get started.', 'payaman_wishlist'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="promo-sub-create" class="payaman-sub-content" style="display: none;">
        <h2><?php esc_html_e('Create New Campaign', 'payaman_wishlist'); ?></h2>

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
                    <p class="description"><?php esc_html_e('Select all products for this campaign. Emails will be sent to users who have any of these in their wishlist.', 'payaman_wishlist'); ?></p>
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
                        <?php esc_html_e('Available tags:', 'payaman_wishlist'); ?>
                        <code>{user_name}</code> <code>{site_name}</code> <code>{count}</code> <code>{products_list}</code>
                    </p>
                </td>
            </tr>
        </table>

        <p>
            <button type="button" id="payaman_save_campaign" class="button button-primary">
                <?php esc_html_e('Save Campaign', 'payaman_wishlist'); ?>
            </button>
            <span id="payaman_campaign_loading" style="display: none; margin-left: 10px; vertical-align: middle;">
                <span class="spinner is-active"></span>
            </span>
        </p>
    </div>

</div>

<script>
jQuery(function($) {
    var promoNonce = '<?php echo esc_js($promo_nonce); ?>';
    var $status = $('#payaman_promo_status');

    function showStatus(msg, type) {
        $status.removeClass('notice-success notice-error').addClass('notice-' + type)
            .html('<p>' + msg + '</p>').show();
    }

    // Subtabs
    $('.payaman-sub-link').on('click', function(e) {
        e.preventDefault();
        $('.payaman-sub-link').removeClass('active');
        $(this).addClass('active');
        $('.payaman-sub-content').hide();
        $('#' + $(this).data('target')).show();
    });

    // Save campaign
    $('#payaman_save_campaign').on('click', function() {
        var name = $('#campaign_name').val();
        var products = $('#campaign_products').val();
        var subject = $('#campaign_subject').val();
        var body = $('#campaign_body').val();

        if (!name || !products || products.length === 0 || !subject || !body) {
            showStatus('<?php echo esc_js(__('Please fill in all fields.', 'payaman_wishlist')); ?>', 'error');
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#payaman_campaign_loading').show();

        $.post(ajaxurl, {
            action: 'payaman_wishlist_save_campaign',
            name: name,
            product_ids: products,
            subject: subject,
            body: body,
            nonce: promoNonce
        }, function(res) {
            $('#payaman_campaign_loading').hide();
            $btn.prop('disabled', false);

            if (res.success) {
                showStatus('<?php echo esc_js(__('Campaign saved!', 'payaman_wishlist')); ?>', 'success');
                $('#campaign_name, #campaign_subject, #campaign_body').val('');
                $('#campaign_products').val(null).trigger('change');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error saving campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        }).fail(function() {
            $('#payaman_campaign_loading').hide();
            $btn.prop('disabled', false);
            showStatus('<?php echo esc_js(__('Request failed.', 'payaman_wishlist')); ?>', 'error');
        });
    });

    // Send campaign
    $(document).on('click', '.payaman-send-campaign', function() {
        if (!confirm('<?php echo esc_js(__('Send this campaign now? This cannot be undone.', 'payaman_wishlist')); ?>')) {
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
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error sending campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        }).fail(function() {
            $row.find('.campaign-loading').hide();
            $btn.prop('disabled', false);
            showStatus('<?php echo esc_js(__('Request failed.', 'payaman_wishlist')); ?>', 'error');
        });
    });

    // Delete campaign
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
                    $('#payaman_campaigns_tbody').html('<tr><td colspan="7"><?php echo esc_js(__('No campaigns yet.', 'payaman_wishlist')); ?></td></tr>');
                }
                showStatus('<?php echo esc_js(__('Campaign deleted.', 'payaman_wishlist')); ?>', 'success');
            } else {
                showStatus(res.data ? res.data.message : '<?php echo esc_js(__('Error deleting campaign.', 'payaman_wishlist')); ?>', 'error');
            }
        });
    });
});
</script>
