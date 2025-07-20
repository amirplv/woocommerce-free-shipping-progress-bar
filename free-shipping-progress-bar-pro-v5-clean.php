<?php
/**
 * Plugin Name: Free Shipping Progress Bar Pro
 * Description: نوار پیشرفت برای ارسال رایگان با قابلیت تنظیم پیام، رنگ، مکان نمایش و پشتیبانی از شورتکد.
 * Version: 1.5.0
 * Author: plv
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Add settings link in plugins list
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="options-general.php?page=fspb-settings">تنظیمات</a>';
    array_unshift($links, $settings_link);
    return $links;
});

add_action('admin_menu', function() {
    add_options_page('Free Shipping Bar Settings', 'Free Shipping Bar', 'manage_options', 'fspb-settings', 'fspb_settings_page');
});
add_action('admin_init', function() {
    register_setting('fspb-settings-group', 'fspb_threshold');
    register_setting('fspb-settings-group', 'fspb_message');
    register_setting('fspb-settings-group', 'fspb_success_message');
    register_setting('fspb-settings-group', 'fspb_color');
    register_setting('fspb-settings-group', 'fspb_locations');
});

function fspb_settings_page() {
    ?>
    <div class="wrap">
        <h1>Free Shipping Bar Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fspb-settings-group'); ?>
            <?php do_settings_sections('fspb-settings-group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Free Shipping Threshold (تومان)</th>
                    <td><input type="number" name="fspb_threshold" value="<?php echo esc_attr(get_option('fspb_threshold', 600000)); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Progress Bar Color</th>
                    <td><input type="color" name="fspb_color" value="<?php echo esc_attr(get_option('fspb_color', '#61ce70')); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Display Message</th>
                    <td>
                        <input type="text" name="fspb_message" style="width: 400px" value="<?php echo esc_attr(get_option('fspb_message', 'اگر تنها {remaining} تومان دیگر خرید کنید، ارسال شما رایگان می‌شود.')); ?>" />
                        <p><small>Use <code>{remaining}</code> for the remaining amount.</small></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Success Message</th>
                    <td>
                        <input type="text" name="fspb_success_message" style="width: 400px" value="<?php echo esc_attr(get_option('fspb_success_message', 'تبریک! به حد نصاب ارسال رایگان رسیدید!')); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Show Bar On</th>
                    <td>
                        <?php $locs = get_option('fspb_locations', []); ?>
                        <label><input type="checkbox" name="fspb_locations[]" value="cart" <?php checked(in_array('cart', $locs)); ?> /> Cart</label><br>
                        <label><input type="checkbox" name="fspb_locations[]" value="checkout" <?php checked(in_array('checkout', $locs)); ?> /> Checkout</label><br>
                        <label><input type="checkbox" name="fspb_locations[]" value="mini_cart" <?php checked(in_array('mini_cart', $locs)); ?> /> Mini Cart</label><br>
                        <label><input type="checkbox" name="fspb_locations[]" value="product" <?php checked(in_array('product', $locs)); ?> /> Product Page</label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Shortcode</th>
                    <td>
                        <code>[fspb_progress_bar]</code><br>
                        <small>برای نمایش دستی در المنتور یا بخش‌های دیگر سایت، از این شورتکد استفاده کنید.</small>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function fspb_render_bar() {
    $threshold = get_option('fspb_threshold', 600000);
    $color = get_option('fspb_color', '#61ce70');
    $msg = get_option('fspb_message', 'اگر تنها {remaining} تومان دیگر خرید کنید، ارسال شما رایگان می‌شود.');
    $msg_success = get_option('fspb_success_message', 'تبریک! به حد نصاب ارسال رایگان رسیدید!');

    if ( function_exists( 'WC' ) && WC()->cart ) {
        $cart_total = WC()->cart->get_displayed_subtotal();
        $remaining = max(0, $threshold - $cart_total);
        $progress = min(100, ($cart_total / $threshold) * 100);
        $formatted_remaining = number_format($remaining, 0, '.', ',');

        $display_msg = $remaining > 0
            ? str_replace('{remaining}', $formatted_remaining, $msg)
            : $msg_success;

        echo '<div class="fspb-container" style="margin:10px 0;">
            <div class="free-shipping-bar" style="padding: 10px; margin: 10px 0; background: #f3f3f3; border: 1px solid #ccc;">
                <div style="margin-bottom: 5px;">' . esc_html($display_msg) . '</div>
                <div style="background: #ddd; height: 10px; border-radius: 10px; overflow: hidden;">
                    <div style="width: ' . esc_attr($progress) . '%; height: 100%; background: ' . esc_attr($color) . ';"></div>
                </div>
            </div>
        </div>';
    }
}

add_shortcode('fspb_progress_bar', 'fspb_render_bar');
add_action('woocommerce_before_cart', function() {
    if (in_array('cart', (array)get_option('fspb_locations', []))) fspb_render_bar();
});
add_action('woocommerce_before_checkout_form', function() {
    if (in_array('checkout', (array)get_option('fspb_locations', []))) fspb_render_bar();
});
add_action('woocommerce_widget_shopping_cart_before_buttons', function() {
    if (in_array('mini_cart', (array)get_option('fspb_locations', []))) fspb_render_bar();
});
add_action('woocommerce_before_single_product', function() {
    if (in_array('product', (array)get_option('fspb_locations', []))) fspb_render_bar();
});
