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
    $( '#toggle-multi-address' ).on( 'click', function () {
        $( '#multi-address-section' ).slideToggle();
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
    if ($('.sma-delivery-date').length) {
        $('.sma-delivery-date').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 1, // Prevent past dates
            beforeShowDay: function(date) {
                // Exclude weekends or specific holidays if needed.
                var day = date.getDay();
                return [(day != 0 && day != 6), ''];
            }
        });
    }

});
