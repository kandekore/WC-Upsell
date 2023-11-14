<?php
/**
 * Plugin Name: WooCommerce Upsell Popup
 * Description: An upsell popup for WooCommerce checkout.
 * Version: 1.0.1
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

add_action('admin_menu', 'woo_upsell_admin_menu');

function woo_upsell_admin_menu() {
    add_menu_page(
        'WooCommerce Upsell Popup Settings',
        'Upsell Popup Settings',
        'manage_options',
        'woo-upsell-popup-settings',
        'woo_upsell_popup_settings_page'
    );
}

function woo_upsell_popup_settings_page() {
    ?>
    <div class="wrap">
        <h1>WooCommerce Upsell Popup Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('woo-upsell-popup-settings-group');
            do_settings_sections('woo-upsell-popup-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'woo_upsell_popup_settings_init');

function woo_upsell_popup_settings_init() {
    register_setting('woo-upsell-popup-settings-group', 'woo_upsell_category');
    register_setting('woo-upsell-popup-settings-group', 'woo_upsell_order');
	 register_setting('woo-upsell-popup-settings-group', 'woo_upsell_message');

    add_settings_section(
        'woo-upsell-popup-settings-section',
        'Settings',
        null,
        'woo-upsell-popup-settings'
    );

    add_settings_field(
        'woo_upsell_category_field',
        'Product Category',
        'woo_upsell_category_field_callback',
        'woo-upsell-popup-settings',
        'woo-upsell-popup-settings-section'
    );

    add_settings_field(
        'woo_upsell_order_field',
        'Product Order',
        'woo_upsell_order_field_callback',
        'woo-upsell-popup-settings',
        'woo-upsell-popup-settings-section'
    );
	  add_settings_field(
        'woo_upsell_message_field',
        'Upsell Message',
        'woo_upsell_message_field_callback',
        'woo-upsell-popup-settings',
        'woo-upsell-popup-settings-section'
    );
}
function woo_upsell_message_field_callback() {
    $message = get_option('woo_upsell_message', 'Don’t forget your accessories'); // Default message
    ?>
    <input type="text" id="woo_upsell_message" name="woo_upsell_message" value="<?php echo esc_attr($message); ?>">
    <?php
}
function woo_upsell_category_field_callback() {
    $selected_category = get_option('woo_upsell_category');
    $args = array(
        'taxonomy'   => 'product_cat',
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => false,
    );
    $product_categories = get_terms($args);
    ?>
    <select id="woo_upsell_category" name="woo_upsell_category">
        <?php foreach ($product_categories as $category) : ?>
            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                <?php echo esc_html($category->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}


function woo_upsell_order_field_callback() {
    $order = get_option('woo_upsell_order', 'sku');
    ?>
    <select id="woo_upsell_order" name="woo_upsell_order">
        <option value="sku" <?php selected($order, 'sku'); ?>>Sort by SKU</option>
        <option value="name" <?php selected($order, 'name'); ?>>Sort by Name</option>
        <option value="price" <?php selected($order, 'price'); ?>>Sort by Price</option>
        
    </select>
    <?php
}

// Fetch products based on a category via AJAX
function fetch_products_by_category() {
  $category = get_option('woo_upsell_category', 'accessories');  // Default to 'accessories' if not set
$order = get_option('woo_upsell_order', 'sku'); // Default to 'sku' if not set

$query_args = array(
    'post_type' => 'product',
    'product_cat' => $category,
    'numberposts' => -1
);

// Modify query based on selected order
switch ($order) {
    case 'name':
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'ASC';
        break;
    case 'price':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_price';
        $query_args['order'] = 'ASC';
        break;
    case 'sku':
    default:
        $query_args['orderby'] = 'meta_value';
        $query_args['meta_key'] = '_sku';
        $query_args['order'] = 'ASC';
        break;
}

$products = get_posts($query_args);

  $upsell_message = get_option('woo_upsell_message', 'Don’t forget your accessories'); // Fetch the custom message

    $output = '<h3 class="upsell">' . esc_html($upsell_message) . '</h3><div class="products-carousel">';
   foreach ($products as $product) {
    $_product = wc_get_product($product->ID);  // Get the product object

    $product_permalink = get_permalink($product->ID);
    $product_title = $_product->get_title();
    $product_image = $_product->get_image();  // Get the product image (thumbnail)
    $product_price = $_product->get_price_html();  // Get the product price

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
