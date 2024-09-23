(function($) {
    $(document).ready(function() {
        function checkPaymentStatus() {
            $.ajax({
                url: fibPaymentsData.ajaxurl, // Use the localized variable
                data: {
                    action: "check_payment_status",
                    payment_id: $("#payment-id").val(),
                    order_id: $("#order-id").val(),
                    nonce: $("#nonce").val()
                },
                success: function(response) {
                    try {
                        if (response.success && response.data.status === "PAID") {
                            window.location.href = response.data.redirect_url;
                        } else if (!response.success) {
                            console.error(response.errors);
                        }
                    } catch (e) {
                        console.error("Error checking payment status.");
                    }
                },
                error: function() {
                    console.error("Error something went wrong.");
                }
            });
        }
        setInterval(checkPaymentStatus, 5000);

        $("#regenerate-qr-code").on("click", function() {
            $.ajax({
                url: fibPaymentsData.ajaxurl, // Use the localized variable
                data: {
                    action: "regenerate_qr_code",
                    order_id: $("#order-id").val(),
                    nonce: $("#nonce").val() // Include the nonce here if needed

                },
                success: function(response) {
                    if (response.success) {
                        $("#qr-code-img").attr("src", response.data.qr_code_url);
                    } else {
                        console.error("Failed to regenerate QR code.");
                    }
                },
                error: function() {
                    console.error("Error something went wrong.");
                }
            });
        });
    });
})(jQuery);
