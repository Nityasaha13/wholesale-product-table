jQuery(document).ready(function($) {


    $("input[type='radio'][name='wpt_wholesale_products']").change(function() {
        var selectedValue = $(this).val();
        wpt_get_product_category_drop(selectedValue);
    });
    
    function wpt_get_product_category_drop(selectedValue) {
        if (selectedValue === 'all') {
            $(".wpt-category-dropdown").hide();
        } else if (selectedValue === 'category') {
            $(".wpt-category-dropdown").show();
        }
    }

    // Initialize the dropdown based on the selected radio button on page load
    var initialSelectedValue = $("input[type='radio'][name='wpt_wholesale_products']:checked").val();
    wpt_get_product_category_drop(initialSelectedValue);

});