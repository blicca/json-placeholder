(function($) {
    "use strict";

    $(document).ready(function($) {

        $('table a').click(function(e) {
            e.preventDefault();
            var user_id = $(this).data('user-id'); 
            $.ajax({
                type: 'GET',
                url: custom_jsonplaceholder_ajax.ajaxurl,
                data: {
                    action: 'get_user_details',
                    user_id: user_id
                },
                success: function(response) {
                    $('#user-details').html(response);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }            
            });
        });

    });


})(jQuery);    