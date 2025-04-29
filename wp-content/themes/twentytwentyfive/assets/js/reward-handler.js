jQuery(document).ready(function($) {
    // Create modal HTML structure
    $('body').append(`
        <div class="reward-modal" style="display:none;">
            <div class="reward-modal-overlay"></div>
            <div class="reward-modal-content">
                <div class="reward-modal-header">
                    <h3>Confirm Reward Redemption</h3>
                    <span class="reward-modal-close">&times;</span>
                </div>
                <div class="reward-modal-body">
                    <p class="confirmation-message"></p>
                    <div class="reward-details">
                        <div class="detail-row">
                            <span class="detail-label">Reload Amount:</span>
                            <span class="detail-value reload-value"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone Number:</span>
                            <span class="detail-value phone-number"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Coins Cost:</span>
                            <span class="detail-value coins-cost"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Remaining Coins:</span>
                            <span class="detail-value remaining-coins"></span>
                        </div>
                    </div>
                </div>
                <div class="reward-modal-footer">
                    <button class="reward-modal-cancel">Cancel</button>
                    <button class="reward-modal-confirm">Confirm</button>
                </div>
            </div>
        </div>
    `);

    // Function to show modal with specific content
    function showRewardModal(confirmationData ,rewardId) {
        $('.reward-modal .confirmation-message').text(confirmationData.message || 'Confirm your reward redemption');
        $('.reward-modal .reload-value').text('₹' + confirmationData.reload_value);
        $('.reward-modal .phone-number').text(confirmationData.phone_number);
        $('.reward-modal .coins-cost').text(confirmationData.coins_cost);
        $('.reward-modal .remaining-coins').text(confirmationData.remaining_coins);
        // Store the reward ID on the confirm button's data, so we know which reward to process
        $('.reward-modal-confirm').data('reward-id', rewardId);
        $('.reward-modal').fadeIn();
    }

    // Function to hide modal
    function hideRewardModal() {
        $('.reward-modal').fadeOut();
    }

    // Event listeners for modal
    $('.reward-modal-close, .reward-modal-cancel').on('click', hideRewardModal);
    // $('.reward-modal-overlay').on('click', hideRewardModal);

    // Bind confirm button once outside the AJAX call so that it persists.
    // When the button is clicked, retrieve the reward ID from its data,
    // hide the modal, and then call redeemReward() with isConfirmed = true.
    $('.reward-modal-confirm').on('click', function() {
        var rewardId = $(this).data('reward-id');
        hideRewardModal();
        redeemReward(rewardId, true);
    });

    // Function to toggle the rewards dropdown
    function toggleRewardsDropdown() {
        $('.rewards-dropdown').toggle();
    }

    // Event listener for clicking the rewards icon area
    $('.rewards-icon-area').on('click', function() {
        toggleRewardsDropdown();
    });

    // Function to handle reward redemption via AJAX
    function redeemReward(rewardId, isConfirmed = false) {
        if (typeof reward_ajax_object === 'undefined' || !reward_ajax_object.ajax_url) {
            console.error('reward_ajax_object or ajax_url not defined.');
            showAlert('Error', 'Could not process reward redemption.');
            return;
        }

        const requestData = {
            action: 'redeem_reward',
            reward_id: rewardId,
            _ajax_nonce: reward_ajax_object.redeem_reward_nonce,
            student_identifier: reward_ajax_object.student_identifier
        };

        if (isConfirmed) {
            requestData.confirmed = 'true';
        }

        $.ajax({
            url: reward_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: requestData,
            beforeSend: function() {
                // Show loading indicator
                $('.reward-modal-confirm').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: function (response) {
                console.log("AJAX success response:", response); // Debug

                if (response.data && response.data.needs_confirmation) {
                    // Show confirmation modal
                    showRewardModal(response.data.confirmation_data, rewardId);
                    $('.reward-modal-confirm').prop('disabled', false).text('Confirm');
                    return;
                }

                if (response.success) {
                    // Success
                    showAlert('Success', response.data.message || 'Reward redeemed successfully!', 'success');

                    // Update UI if needed
                    if (response.data.coins !== undefined) {
                        $('.coin-count').text(response.data.coins);
                    }
                    if (response.data.points !== undefined) {
                        $('.point-count').text(response.data.points);
                    }

                    toggleRewardsDropdown();
                } else {
                    // Error
                    console.error("Server-side error response:", response);

                    const firstMessage = response.data?.messages?.[0];
                    if (firstMessage) {
                        showAlert('Failed to Redeem', firstMessage.text, 'error');
                    } else {
                        showAlert('Failed to Redeem', 'Failed to redeem reward.', 'error');
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                let errorMessage = 'Something went wrong. Please try again later.';

                if (jqXHR.responseJSON && jqXHR.responseJSON.data?.messages?.[0]) {
                    errorMessage = jqXHR.responseJSON.data.messages[0].text;
                }

                showAlert('Failed to Redeem', errorMessage, 'error');
            },
            complete: function() {
                $('.reward-modal-confirm').prop('disabled', false).text('Confirm');
            }
        });
    }


    // Function to show styled alerts
    function showAlert(title, message, type = 'info') {
        // Remove any existing alerts
        $('.reward-alert').remove();

        // Create and show new alert
        $('body').append(`
            <div class="reward-alert reward-alert-${type}">
                <strong>${message}</strong> 
                <span class="reward-alert-close">&times;</span>
            </div>
        `);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.reward-alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);

        // Close button handler
        $('.reward-alert-close').on('click', function() {
            $(this).parent().fadeOut(500, function() {
                $(this).remove();
            });
        });
    }

    // Event listener for redeem button clicks
    $(document).on('click', '.redeem-button', function(e) {
        e.preventDefault();
        var rewardId = $(this).data('reward-id');
        redeemReward(rewardId);
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.rewards-icon-area, .rewards-dropdown').length) {
            $('.rewards-dropdown').hide();
        }
    });
});