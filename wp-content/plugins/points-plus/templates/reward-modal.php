<div class="reward-modal" style="display:none;">
    <div class="reward-modal-overlay"></div>
    <div class="reward-modal-content">
        <div class="reward-modal-header">
            <h3><?php esc_html_e('Confirm Reward Redemption', 'points-plus'); ?></h3>
            <span class="reward-modal-close">&times;</span>
        </div>
        <div class="reward-modal-body">
            <p class="confirmation-message"></p>
            <div class="reward-details">
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Reload Amount:', 'points-plus'); ?></span>
                    <span class="detail-value reload-value"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Phone Number:', 'points-plus'); ?></span>
                    <span class="detail-value phone-number"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Coins Cost:', 'points-plus'); ?></span>
                    <span class="detail-value coins-cost"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Remaining Coins:', 'points-plus'); ?></span>
                    <span class="detail-value remaining-coins"></span>
                </div>
            </div>
        </div>
        <div class="reward-modal-footer">
            <button class="reward-modal-cancel"><?php esc_html_e('Cancel', 'points-plus'); ?></button>
            <button class="reward-modal-confirm"><?php esc_html_e('Confirm', 'points-plus'); ?></button>
        </div>
    </div>
</div>