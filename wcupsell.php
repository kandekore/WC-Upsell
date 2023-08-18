<?php
/**
 * Plugin Name: WooCommerce Upsell Popup
 * Description: An upsell popup for WooCommerce checkout.
 * Version: 1.0.0
 * Author: D Kandekore
 */

// Enqueue the required scripts and styles
function woo_upsell_enqueue_scripts() {
    if (is_checkout()) {
        wp_enqueue_script('woo-upsell-popup-js', plugin_dir_url(__FILE__) . 'assets/js/checkout.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('woo-upsell-popup-css', plugin_dir_url(__FILE__) . 'assets/css/checkout.css', array(), '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'woo_upsell_enqueue_scripts');

// Settings Page
function upsell_add_settings_page() {
    add_options_page(
        'Upsell Settings',
        'Upsell Settings',
        'manage_options',
        'upsell-settings',
        'upsell_render_settings_page'
    );
}
add_action('admin_menu', 'upsell_add_settings_page');

function upsell_render_settings_page() {
    ?>
    <div class="wrap">
        <h2>Upsell Settings</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('upsell_settings_group');
            do_settings_sections('upsell-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register Settings

function upsell_register_settings() {
    register_setting('upsell_settings_group', 'upsell_custom_message');
    register_setting('upsell_settings_group', 'upsell_product_category');

    add_settings_section(
        'upsell_settings_section',
        'Upsell Options',
        'upsell_settings_section_callback',
        'upsell-settings'
    );

    add_settings_field(
        'upsell_custom_message',
        'Custom Message',
        'upsell_custom_message_callback',
        'upsell-settings',
        'upsell_settings_section'
    );

    add_settings_field(
        'upsell_product_category',
        'Product Category',
        'upsell_product_category_callback',
        'upsell-settings',
        'upsell_settings_section'
    );
}
add_action('admin_init', 'upsell_register_settings');

function upsell_settings_section_callback() {
    echo '<p>Set your custom upsell message and choose a product category.</p>';
}

function upsell_custom_message_callback() {
    $message = get_option('upsell_custom_message');
    echo "<input type='text' name='upsell_custom_message' value='{$message}' size='50'>";
}

function upsell_product_category_callback() {
    $selected_category = get_option('upsell_product_category');
    $categories = get_terms('product_cat');
    echo '<select name="upsell_product_category">';
    foreach ($categories as $category) {
        echo '<option value="' . $category->slug . '"' . selected($selected_category, $category->slug, false) . '>' . $category->name . '</option>';
    }
    echo '</select>';
}

// Fetch products based on a category via AJAX
function fetch_products_by_category() {
    $category = get_option('upsell_product_category', 'accessories');  
    $custom_message = get_option('upsell_custom_message', 'Don’t forget your accessories'); 

    $products = get_posts(array(
    'post_type' => 'product',
    'product_cat' => $category,
    'numberposts' => -1
));
    $output = '<h3 class="upsell">' . esc_html($custom_message) . '</h3><div class="products-carousel">';
   foreach ($products as $product) {
    $_product = wc_get_product($product->ID);  

    $product_permalink = get_permalink($product->ID);
    $product_title = $_product->get_title();
    $product_image = $_product->get_image();  
    // $product_price = $_product->get_price_html();  

    $output .= '<div class="product-item">';
    $output .= '<a href="' . esc_url($product_permalink) . '">';
    $output .= $product_image;
    $output .= '<h2 class="product-title">' . esc_html($product_title) . '</h2>';
   // $output .= '<span class="product-price">' . $product_price . '</span>';
    $output .= '</a>';
    $output .= '</div>';
}

    $output .= '</div>';
    wp_send_json_success($output);
}
add_action('wp_ajax_fetch_products_by_category', 'fetch_products_by_category');
add_action('wp_ajax_nopriv_fetch_products_by_category', 'fetch_products_by_category');

// Add the upsell popup to the WooCommerce checkout
function add_upsell_popup_to_checkout() {
    ?>
    <div id="upsell-popup">
        <div id="popup-content"></div>
        <button id="close-popup">Close</button>
    </div>
    <?php
}
add_action('woocommerce_after_checkout_form', 'add_upsell_popup_to_checkout');
?>
