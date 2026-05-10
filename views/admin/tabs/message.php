<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="form-section" id="tab-message" style="display: none;">
    
    <nav class="payaman-sub-nav">
        <a href="#subtab-frontend" class="payaman-sub-link active" data-target="subtab-frontend">
            <?php esc_html_e('Frontend Messages', 'payaman_wishlist'); ?>
        </a>
		|
        <a href="#subtab-email" class="payaman-sub-link" data-target="subtab-email">
            <?php esc_html_e('Email Alerts', 'payaman_wishlist'); ?>
        </a>
    </nav>

    <div id="subtab-frontend" class="payaman-sub-content">
        <table class="form-table">
            <tr class="payaman_wishlist_enabled_row">
                <th scope="row"><label><?php esc_html_e('Add Success Message', 'payaman_wishlist'); ?></label></th>
                <td>
                    <label class="payaman_wishlist-switch">
                        <input type="checkbox" name="enable_add_success_message" value="yes" <?php checked(payaman_wishlist_setting('enable_add_success_message'), 'yes'); ?>>
                        <span class="payaman_wishlist-slider round"></span>
                    </label>
                    <br /><br />
                    <input type="text" name="add_success_message" value="<?php echo esc_attr(payaman_wishlist_setting('add_success')); ?>" class="large-text" placeholder="Product added to wishlist!">
                </td>
            </tr>
            <tr class="payaman_wishlist_enabled_row">
                <th scope="row"><label><?php esc_html_e('Remove Success Message', 'payaman_wishlist'); ?></label></th>
                <td>
                    <label class="payaman_wishlist-switch">
                        <input type="checkbox" name="enable_remove_success_message" value="yes" <?php checked(payaman_wishlist_setting('enable_remove_success_message'), 'yes'); ?>>
                        <span class="payaman_wishlist-slider round"></span>
                    </label>
                    <br /><br />
                    <input type="text" name="remove_success_message" value="<?php echo esc_attr(payaman_wishlist_setting('remove_success')); ?>" class="large-text" placeholder="Product removed from wishlist!">
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e('Required Login Message', 'payaman_wishlist'); ?></label></th>
                <td>
                    <input type="text" name="required_login_message" value="<?php echo esc_attr(payaman_wishlist_setting('required_login_message')); ?>" class="large-text" placeholder="Please login to add products to your wishlist.">
                </td>
            </tr>
        </table>
    </div>

    <div id="subtab-email" class="payaman-sub-content" style="display: none;">
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php _e('Stock Alert Subject', 'payaman_wishlist'); ?></label></th>
                <td>
                    <input type="text" name="email_stock_subject" class="regular-text" value="<?php echo esc_attr(payaman_wishlist_setting('email_stock_subject', 'Good news! {product_name} is back in stock!')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Stock Alert Message', 'payaman_wishlist'); ?></label></th>
                <td>
                    <textarea name="email_stock_body" rows="5" class="large-text"><?php echo esc_textarea(payaman_wishlist_setting('email_stock_body', "Hi {user_name},\n\nThe product '{product_name}' in your wishlist is now back in stock!\n\nView product: {product_url}")); ?></textarea>
                    <p class="description"><?php _e('Tags: {user_name}, {product_name}, {product_url}, {site_name}', 'payaman_wishlist'); ?></p>
                </td>
            </tr>
            <tr class="section-divider"><td colspan="2"><hr></td></tr>
            <tr>
                <th scope="row"><label><?php _e('Price Drop Subject', 'payaman_wishlist'); ?></label></th>
                <td>
                    <input type="text" name="email_price_subject" class="regular-text" value="<?php echo esc_attr(payaman_wishlist_setting('email_price_subject', 'Price drop alert for {product_name}!')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Price Drop Message', 'payaman_wishlist'); ?></label></th>
                <td>
                    <textarea name="email_price_body" rows="5" class="large-text"><?php echo esc_textarea(payaman_wishlist_setting('email_price_body', "Hi {user_name},\n\nGreat news! The price of '{product_name}' in your wishlist has just dropped. Check it out now!\n\nView product: {product_url}")); ?></textarea>
                    <p class="description"><?php _e('Tags: {user_name}, {product_name}, {product_url}, {site_name}', 'payaman_wishlist'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <script>
    document.querySelectorAll('.payaman-sub-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from links
            document.querySelectorAll('.payaman-sub-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Hide all content
            document.querySelectorAll('.payaman-sub-content').forEach(content => content.style.display = 'none');
            
            // Show target content
            const target = this.getAttribute('data-target');
            document.getElementById(target).style.display = 'block';
        });
    });
    </script>
</div>