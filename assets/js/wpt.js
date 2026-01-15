jQuery(document).ready(function($) {

    // Fetch products via AJAX (for search, filter, pagination)
    function fetchProducts(page) {
        var query    = $('#wpt-search').val();
        var category = $('#wpt-category-filter').val();
        var sort = $('#wpt-sort-select').val();
        page = page || 1;
		
		// Create the loading indicator
        var loadingIndicator = $('<tr id="wpt-table-loading"><td colspan="10"><div class="wpt-cv-spinner"><span class="wpt-spinner"></span></div></td></tr>');
        $('#wpt-table-body').html(loadingIndicator);
		
        $.ajax({
            url: wpt_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action:   'wpt_product_search',
                nonce:    wpt_ajax_params.nonce,
                query:    query,
                category: category,
                sort:     sort,
                page:     page
            },
            success: function(response) {
				// Remove the spinner once the response is received
                $('#wpt-table-loading').remove();
				
                if ( response.success ) {
                    $('#wpt-table-body').html(response.data.html);
                    $('#wpt-pagination').html(response.data.pagination);
                } else {
                    $('#wpt-table-body').html('<tr><td colspan="10">No products found.</td></tr>');
                    $('#wpt-pagination').empty();
                }
            }
        });
    }

    // Initial fetch on page load.
    fetchProducts(1);

    // Search and category filter events.
    $('#wpt-search').on('keyup', function() {
        fetchProducts(1);
    });
    $('#wpt-category-filter, #wpt-sort-select').on('change', function() {
        fetchProducts(1);
    });
    $('#wpt-pagination').on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        var pageMatch = link.match(/paged=(\d+)/) || link.match(/page\/(\d+)/);
        var page = pageMatch ? parseInt(pageMatch[1], 10) : 1;
        fetchProducts(page);
        
        $('html, body').animate({
            scrollTop: $("#wpt-table-loading").offset().top - 200
        }, 200);
    });

    // Variation selection: update price and image based on selected option.
    $('.wholesale-product-table').on('change', '.wpt-variation-select', function() {
        var $select = $(this);
        var $row = $select.closest('tr');
        var $selectedOption = $select.find('option:selected');
        var priceHtml = $selectedOption.data('price-html') || '';
        var imageSrc  = $selectedOption.data('image') || '';

        // Update the price cell.
        $row.find('.wpt-price-cell').html(priceHtml);

        // Update the image cell if available.
        if ( imageSrc ) {
            $row.find('.wpt-image-cell').html('<img src="' + imageSrc + '" />');
        }
    });

    // Add to Cart click event: always read current variation selection.
    $('.wholesale-product-table').on('click', '.wpt-add-to-cart', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('tr');
        var parentId = $button.data('product-id') || $button.attr('data-product-id');
        var quantity = $row.find('.wpt-quantity').val() || 1;
        var variationId = 0;
        var variationAttrs = {};

        var $variationSelect = $row.find('.wpt-variation-select');
        if ( $variationSelect.length ) {
            variationId = $variationSelect.find('option:selected').val();
            if ( ! variationId ) {
                alert('Please select a variation before adding to cart.');
                return;
            }
            variationAttrs = $variationSelect.find('option:selected').data('attributes') || {};
        }

        $.ajax({
            url: wpt_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wpt_add_to_cart',
                nonce: wpt_ajax_params.nonce,
                product_id: parentId,
                variation_id: variationId,
                variation_attrs: JSON.stringify(variationAttrs),
                quantity: quantity
            },
            success: function(response) {
                if ( response.success ) {
                    // Remove any old View Cart link.
                    $button.siblings('.wpt-view-cart').remove();
                    $button.after('<a href="'+ wpt_ajax_params.cart_url +'" class="wpt-view-cart">View Cart</a>');
                    // Reset the variation select (optional).
                    if ( $variationSelect.length ) {
                        $variationSelect.prop('selectedIndex', 0);
                        // Optionally reset price and image to original values.
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
});