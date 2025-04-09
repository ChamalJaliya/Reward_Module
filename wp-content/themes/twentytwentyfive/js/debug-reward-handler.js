jQuery(document).ready(function($) {
    // Debug initialization
    console.log('Debug Reward Handler initialized');
    console.log('Debug Data:', debugRewardData);

    $(document).on('click', '.claim-reward-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.prop('disabled', true).addClass('loading');

        console.log('Initiating AJAX request with nonce:', debugRewardData.nonce);

        $.ajax({
            url: debugRewardData.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'claim_daily_reward',
                nonce: debugRewardData.nonce,
                user_id: debugRewardData.user_id,
                debug_request: true
            },
            success: function(response) {
                console.log('AJAX success:', response);
                if (response.success) {
                    alert(response.data.message);
                    if (debugRewardData.debug) {
                        console.log('Reward debug info:', response.data.debug);
                    }
                } else {
                    console.error('Request failed:', response);
                    alert('Error: ' + response.data.message);
                    if (response.data.debug) {
                        console.error('Debug info:', response.data.debug);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Full response:', xhr.responseJSON);
                alert('Request failed. Check console for details.');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading');
            }
        });
    });

    // Debug: Log nonce every 5 seconds to check for changes
    if (debugRewardData.debug) {
        setInterval(() => {
            console.log('Current nonce:', debugRewardData.nonce,
                '| Time:', new Date().toLocaleTimeString());
        }, 5000);
    }
});