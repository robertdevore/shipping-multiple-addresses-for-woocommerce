jQuery( document ).ready( function ( $ ) {
    // Add new address via AJAX.
    $('#sma-add-address').on('submit', function (e) {
        e.preventDefault();

        var formData = {
            action: 'sma_save_address',
            sma_name: $('#sma_name').val(),
            sma_address_1: $('#sma_address_1').val(),
            sma_city: $('#sma_city').val(),
            sma_state: $('#sma_state').val(),
            sma_postcode: $('#sma_postcode').val(),
            security: sma_ajax_object.security,
        };

        $.post(sma_ajax_object.ajax_url, formData, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // Refresh page to show saved addresses
            } else {
                alert(response.data.message);
            }
        });
    });

    // Toggle multiple address fields in checkout.
    $('#toggle-multi-address').on('click', function () {
        $('#multi-address-section').slideToggle();
    });

    // Duplicate the cart on button click.
    $('#sma-duplicate-cart').on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).text('Duplicating...');

        var data = {
            action: 'sma_duplicate_cart',
            security: sma_ajax_object.security
        };

        $.post(sma_ajax_object.ajax_url, data, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        }).always(function() {
            $('#sma-duplicate-cart').prop('disabled', false).text('Duplicate Cart');
        });
    });

    // Delivery Date picker.
    $('#sma_delivery_date').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 1,
    });

    // Checkout show address UI on click.
    $('#sma-show-address-ui').on('click', function () {
        $('#sma-multiple-addresses-section').slideToggle();
    });

    // Collect assigned addresses and submit them during checkout.
    $('form.checkout').on('submit', function (e) {
        var assignedAddresses = {};
        var hasError = false;
    
        // Collect assigned addresses from dropdowns
        $('#sma-multiple-addresses-section select').each(function () {
            var productKey = $(this).data('cart-key');
            var addressKey = $(this).val();
    
            console.log('Product Key:', productKey, 'Address Key:', addressKey);
    
            // Validate that an address is selected
            if (!addressKey || addressKey === "") {
                alert('Please select a valid address for all products.');
                hasError = true;
                return false; // Exit each loop
            }
    
            assignedAddresses[productKey] = addressKey;
        });
    
        if (hasError) {
            e.preventDefault(); // Stop form submission
            return false;
        }
    
        console.log('Assigned addresses:', assignedAddresses);
    
        // Ensure the hidden input is removed first to prevent duplicates
        $('input[name="sma_addresses"]').remove();
    
        // Add assigned addresses to the form as a hidden input
        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'sma_addresses')
            .val(JSON.stringify(assignedAddresses))
            .appendTo(this);
    });
});
