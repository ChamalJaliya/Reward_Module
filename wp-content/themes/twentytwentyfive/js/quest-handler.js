window.$ = jQuery;

jQuery(document).ready(function($) {

    // --- Quest Completion Handling --- //
    $(document.body).on('click', '.play-quest-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var questId = $button.data('quest-id');

        if (typeof quest_ajax_object === 'undefined' || typeof quest_ajax_object.play_nonce === 'undefined') {
            alert('Error: AJAX config missing. Cannot send request.');
            return;
        }

        $button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: quest_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'play_quest',
                nonce: quest_ajax_object.play_nonce,
                quest_id: questId
            },
            success: function(response) {
                if (response.success) {
                    // alert('Quest completed! ' + response.data.message); // Optional alert
                    $('.student-points').text('Points: ' + response.data.new_points);
                    $('.student-coins').text('Coins: ' + response.data.new_coins);
                    $button.text('Completed').css('background-color', 'lightgrey');
                    // --- Refresh notification count after completing a quest ---
                    fetchAndUpdateNotificationCount();
                } else {
                    alert('Error: ' + response.data.message);
                    $button.prop('disabled', false).text('Play Quest');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Quest AJAX Error:", textStatus, errorThrown);
                alert('An error occurred (Quest).');
                $button.prop('disabled', false).text('Play Quest');
            }
        });
    });

    // --- Notification Bell Handling --- //
    var $bellArea = $('.notification-bell-area');
    var $dropdown = $('.notifications-dropdown');
    var studentIdentifier = quest_ajax_object.student_identifier || null; // Get identifier passed from PHP

    // Function to fetch and update notification count badge
    function fetchAndUpdateNotificationCount() {
        if (!studentIdentifier || typeof quest_ajax_object === 'undefined' || typeof quest_ajax_object.fetch_nonce === 'undefined') return;

        // Fetch only unread count (modify PHP handler later for efficiency if needed)
        $.ajax({
            url: quest_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_student_notifications', // Reuses fetch action
                nonce: quest_ajax_object.fetch_nonce,
                student_identifier: studentIdentifier
            },
            success: function(response) {
                var unreadCount = 0;
                if (response.success && response.data.notifications) {
                    response.data.notifications.forEach(function(item){
                        if (!item.is_read) {
                            unreadCount++;
                        }
                    });
                }
                updateBadge(unreadCount);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Count Fetch Error:", textStatus, errorThrown);
            }
        });
    }

    // Function to update the badge display
    function updateBadge(count) {
        var $badge = $bellArea.find('.notification-count-badge');
        if (count > 0) {
            $badge.text(count).show();
        } else {
            $badge.text('0').hide();
        }
    }

    // Click handler for the bell area
    $bellArea.on('click', function(e) {
        e.stopPropagation(); // Prevent clicks inside dropdown from closing it immediately
        // Toggle dropdown
        var wasVisible = $dropdown.is(':visible');
        closeAllDropdowns(); // Close other potential dropdowns if needed
        if(!wasVisible) {
            $dropdown.show();
            loadNotificationsIntoDropdown();
        }

    });

    // Function to load notifications into the dropdown
    function loadNotificationsIntoDropdown() {
        if (!studentIdentifier || typeof quest_ajax_object === 'undefined' || typeof quest_ajax_object.fetch_nonce === 'undefined') return;

        $dropdown.html('<div class="notifications-loading">Loading...</div>'); // Show loading state

        $.ajax({
            url: quest_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_student_notifications',
                nonce: quest_ajax_object.fetch_nonce,
                student_identifier: studentIdentifier
            },
            success: function(response) {
                $dropdown.empty(); // Clear loading/previous content
                if (response.success && response.data.notifications && response.data.notifications.length > 0) {
                    var listHtml = '<ul>';
                    response.data.notifications.forEach(function(item) {
                        var readClass = item.is_read ? 'is-read' : 'is-unread';
                        listHtml += '<li class="notification-item ' + readClass + '" data-index="' + item.index + '">';
                        listHtml += '<span class="notification-message">' + item.message + '</span>';
                        listHtml += '<span class="notification-timestamp">' + item.timestamp + '</span>';
                        // Add "Mark Read" button only if unread
                        if (!item.is_read) {
                            listHtml += '<button class="mark-as-read-btn" data-index="' + item.index + '">Mark Read</button>';
                        }
                        listHtml += '</li>';
                    });
                    listHtml += '</ul>';
                    listHtml += '<div class="notifications-footer"><button class="mark-all-read-btn">Mark all as read</button></div>';
                    $dropdown.html(listHtml);
                } else if (response.success) {
                    $dropdown.html('<div class="no-notifications">No notifications found.</div>');
                }
                else {
                    $dropdown.html('<div class="no-notifications">Could not load notifications.</div>');
                    console.error("Fetch Error:", response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $dropdown.html('<div class="no-notifications">Error loading notifications.</div>');
                console.error("Fetch AJAX Error:", textStatus, errorThrown);
            }
        });
    }

    // --- Mark as Read Handling --- //
    $(document.body).on('click', '.mark-as-read-btn', function (e) {
        e.preventDefault();
        console.log('Delegated click working!');
    });
    $(document.body).on('click', '.mark-as-read-btn', function(e) {
        console.log('Click triggered for .mark-as-read-btn');
        e.preventDefault();
        e.stopPropagation(); // Prevent dropdown from closing
        var $button = $(this);
        var notificationIndex = $button.data('index');
        markNotificationRead(notificationIndex, $button);
    });

    // Click handler for "Mark All Read" button
    $(document.body).on('click', '.mark-all-read-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        markNotificationRead(null, null, true); // Pass null index, set markAll flag
    });

    // Function to send AJAX request to mark notification(s) as read
    function markNotificationRead(index, $buttonElement, markAll = false) {
        // --- ADD THESE CONSOLE LOGS ---
        console.log('markNotificationRead called. Index:', index, 'Mark All:', markAll);
        console.log('Checking quest_ajax_object:', quest_ajax_object);
        // 'studentIdentifier' should be defined outside this function scope,
        // check it's accessible, assuming it's defined like:
        // var studentIdentifier = quest_ajax_object.student_identifier || null;
        console.log('Using studentIdentifier:', studentIdentifier);
        // --- END OF ADDED CONSOLE LOGS ---
        if (!studentIdentifier || typeof quest_ajax_object === 'undefined' || typeof quest_ajax_object.mark_read_nonce === 'undefined' || typeof quest_ajax_object.ajax_url === 'undefined' ) {
            console.error('Mark Read prerequisites missing!'); // Added console error
            return; // Exit if essential data isn't available
        }

        var ajaxData = {
            action: 'mark_notification_read',
            nonce: quest_ajax_object.mark_read_nonce,
            student_identifier: studentIdentifier,
            mark_all: markAll
        };
        // Add index only if marking a specific one
        if (!markAll && index !== null) {
            ajaxData.notification_index = index;
        }

        // Optionally disable button while processing
        if($buttonElement) $buttonElement.prop('disabled', true);

        $.ajax({
            url: quest_ajax_object.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    updateBadge(response.data.new_unread_count);
                    // If marking all, reload list; otherwise, update specific item UI
                    if (markAll) {
                        loadNotificationsIntoDropdown(); // Reload to show all as read
                    } else if ($buttonElement) {
                        // Update UI for the specific item
                        $buttonElement.closest('li.notification-item').addClass('is-read').css('opacity', 0.7);
                        $buttonElement.remove(); // Remove the button
                    }

                } else {
                    alert('Error marking notification as read.');
                    if($buttonElement) $buttonElement.prop('disabled', false);
                    console.error("Mark Read Error:", response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('Error communicating with server to mark notification read.');
                if($buttonElement) $buttonElement.prop('disabled', false);
                console.error("Mark Read AJAX Error:", textStatus, errorThrown);
            }
        });
    }


    // --- Helper to close dropdown when clicking outside --- //
    function closeAllDropdowns() {
        $('.notifications-dropdown').hide();
    }
    // Close dropdown if clicking anywhere else on the page
    $(document).on('click', function(e) {
        // If the click is not on the bell area or inside the dropdown, close it
        if (!$bellArea.is(e.target) && $bellArea.has(e.target).length === 0 && !$dropdown.is(e.target) && $dropdown.has(e.target).length === 0) {
            closeAllDropdowns();
        }
    });


    // Initial setup on page load
    fetchAndUpdateNotificationCount(); // Get initial count when page loads


}); // End jQuery document ready