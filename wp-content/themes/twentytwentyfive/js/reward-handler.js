jQuery(document).ready(function($) {
    // Function to toggle the rewards dropdown
    function toggleRewardsDropdown() {
        $('.rewards-dropdown').toggle(); // Show/hide the rewards dropdown
    }

    // Event listener for clicking the rewards icon area
    $('.rewards-icon-area').on('click', function() {
        toggleRewardsDropdown();
    });

    // Function to handle reward redemption via AJAX
    function redeemReward(rewardId) {
        if (typeof reward_ajax_object === 'undefined' || !reward_ajax_object.ajax_url) {
            console.error('reward_ajax_object or ajax_url not defined.');
            alert('Error: Could not process reward redemption.');
            return;
        }

        $.ajax({
            url: reward_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action:            'redeem_reward', // The WordPress AJAX action hook
                reward_id:         rewardId,
                _ajax_nonce:       reward_ajax_object.redeem_reward_nonce, // Security nonce
                student_identifier: reward_ajax_object.student_identifier // Assuming you're passing this
            },
            success: function(response) {
                if (response.success) {
                    console.log('Reward redeemed:', response.data);
                    alert(response.data.message || 'Reward redeemed successfully!');
                    // Optionally update the UI
                    toggleRewardsDropdown(); // Hide the dropdown after redemption
                    // You might need to refresh parts of the page or update coin count displayed elsewhere
                } else {
                    console.error('Reward redemption failed:', response.data);
                    alert(response.data.error || 'Failed to redeem reward. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error during reward redemption:', textStatus, errorThrown);
                alert(reward_ajax_object.ajax_error_message || 'An unexpected error occurred.');
            }
        });
    }

    // Event listener for redeem button clicks (using delegation)
    $(document).on('click', '.redeem-button', function() {
        var rewardId = $(this).data('reward-id');
        redeemReward(rewardId);
    });
});