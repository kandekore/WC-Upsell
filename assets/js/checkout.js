jQuery(document).ready(function($) {

    // Function to fetch products based on shortcode/category
    function fetchProducts() {
        $.ajax({
            url: woocommerce_params.ajax_url,
            data: {
                'action': 'fetch_products_by_category',
            },
            success: function(response) {
                if (response.success) {
                    $('#popup-content').html(response.data);
                }
            }
        });
    }

    $(document.body).on('updated_checkout', function() {
        fetchProducts();
        $('#upsell-popup').show();
    });

    $('#close-popup').on('click', function() {
        $('#upsell-popup').hide();
    });

});
