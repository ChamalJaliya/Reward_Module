(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


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
