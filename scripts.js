(function($) {
    "use strict";

    $(document).ready(function() {
        var $table = $('table');
        var $details = $('#user-details');

        $table.on('click', '.user-link', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');

            if (!userId) {
                return;
            }

            $details.text('Loading...');

            $.post(
                custom_jsonplaceholder_ajax.ajaxurl,
                {
                    action: 'get_user_details',
                    nonce: custom_jsonplaceholder_ajax.nonce,
                    user_id: userId
                }
            )
                .done(function(response) {
                    if (!response || !response.success || !response.data) {
                        $details.text('Kullanıcı detayları alınamadı.');
                        return;
                    }

                    var data = response.data;
                    var html = '<div>';
                    html += '<p><strong>ID:</strong> ' + escapeHtml(data.id) + '</p>';
                    html += '<p><strong>Name:</strong> ' + escapeHtml(data.name) + '</p>';
                    html += '<p><strong>Username:</strong> ' + escapeHtml(data.username) + '</p>';
                    html += '<p><strong>Email:</strong> ' + escapeHtml(data.email) + '</p>';
                    html += '<p><strong>Phone:</strong> ' + escapeHtml(data.phone) + '</p>';

                    if (data.address && Object.keys(data.address).length) {
                        html += '<p><strong>Address:</strong></p><ul>';
                        $.each(data.address, function(key, value) {
                            html += '<li><strong>' + escapeHtml(key) + ':</strong> ' + escapeHtml(value) + '</li>';
                        });
                        html += '</ul>';
                    }

                    html += '<p><strong>Website:</strong> ' + escapeHtml(data.website) + '</p>';
                    html += '<p><strong>Company:</strong> ' + escapeHtml(data.company) + '</p>';
                    html += '</div>';

                    $details.html(html);
                })
                .fail(function() {
                    $details.text('Kullanıcı detayları alınamadı.');
                });
        });

        function escapeHtml(string) {
            if (string === undefined || string === null) {
                return '';
            }
            return $('<div>').text(string).html();
        }
    });
})(jQuery);