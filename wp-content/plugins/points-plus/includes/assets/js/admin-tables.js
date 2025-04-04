(function( $ ) {
    $(document).ready(function() {
        // Simple example: Add a class to alternate table rows for styling

        // Target your custom post type's list table
        // You might need to adjust this selector based on WordPress's table structure
        var $rewardTable = $('.wp-list-table.posts.reward-item');
        var $questTable = $('.wp-list-table.posts.quest');

        // Add alternate row styling to Rewards table
        if ($rewardTable.length) {
            $rewardTable.find('tbody tr:odd').addClass('alternate-row');
        }

        // Add alternate row styling to Quests table
        if ($questTable.length) {
            $questTable.find('tbody tr:odd').addClass('alternate-row');
        }

        // --- More advanced filtering could go here ---
        // Example (Conceptual):
        // $('#my-custom-filter-button').on('click', function() {
        //     var filterValue = $('#my-custom-filter-input').val();
        //     $rewardTable.find('tbody tr').hide().filter(function() {
        //         return $(this).text().indexOf(filterValue) > -1;
        //     }).show();
        // });

    });

})( jQuery );
