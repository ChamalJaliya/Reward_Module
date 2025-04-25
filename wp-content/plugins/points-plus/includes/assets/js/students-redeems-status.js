(function ($) {
    console.log(
        "students-redeems-status.js loaded on screen:",
        window.location.href
    );

    // let previousStatus;

    // Remember old status on focus, per element
    $(document).on("focus", ".redeem-status-dropdown", function () {
        const $this = $(this);
        // previousStatus = $(this).val();
        $this.data("old-status", $this.val());
        // Clear any previous “confirmed” flag
        $(this).data("confirmed", false);
    });

    // Handle change
    $(document).on("change", ".redeem-status-dropdown", function () {
        const dropdown = $(this);
        const postId = dropdown.data("id");
        const newStatus = dropdown.val();
        // const oldStatus = previousStatus;
        const oldStatus = dropdown.data("old-status");
        const promoType = dropdown.data("promotion-type");
        const reloadValue = dropdown.data("reload-value");
        const coinsCost = dropdown.data("coins-cost");
        const studentEmail = dropdown.data("student-email");

        // Only show confirm for reload-based, old=pending, new=completed|failed
        if (
            promoType === "reload" &&
            oldStatus === "pending" &&
            (newStatus === "completed" || newStatus === "failed")
        ) {
            // Build a custom message
            let actionText =
                newStatus === "completed"
                    ? `grant ₹${reloadValue} reload`
                    : `refund ${coinsCost} coins and notify failure`;

            const msg = `You're about to ${actionText} for student ${studentEmail}.\n\nProceed?`;

            $("#pp-reload-confirm-text").text(msg);

            // BEFORE opening, SHOW or HIDE the reason field
            if (newStatus === "failed") {
                $("#pp-failure-reason-container").show();
            } else {
                $("#pp-failure-reason-container").hide();
            }

            // Open the jQuery UI dialog
            $("#pp-reload-confirm-dialog").dialog({
                modal: true,
                buttons: {
                    Confirm: function () {
                        $(this).dialog("close");
                        dropdown.data("confirmed", true);

                        // CAPTURE the reason
                        const reason = $("#pp-failure-reason").val().trim();

                        updateStatus(postId, newStatus, dropdown, reason);
                    },
                    Cancel: function () {
                        $(this).dialog("close");
                        dropdown.val(oldStatus);
                    }
                },
                close: function () {
                    // If they closed via X or Esc without confirming, revert
                    if (!dropdown.data("confirmed")) {
                        dropdown.val(oldStatus);
                    }
                }
            });

            return; // skip auto‑AJAX
        }

        // Otherwise just go ahead
        updateStatus(postId, newStatus, dropdown);
    });

    function updateStatus(postId, status, dropdown, reason = "") {
        $.post(
            PointsPlus_Admin.ajax_url,
            {
                action: "update_students_redeems_status",
                post_id: postId,
                status: status,
                reason: reason,
                _wpnonce: PointsPlus_Admin.nonce
            },
            function (response) {
                if (!response.success) {
                    alert(
                        "Error: " + (response.data?.message || "Could not update status.")
                    );
                    // revert using per‐element old-status
                    dropdown.val(dropdown.data("old-status"));
                } else {
                    // Marked it so dialog-close won't revert again
                    dropdown.data("confirmed", true);
                    dropdown.data("old-status", status);
                    dropdown.val(status); // ensure the UI matches

                    console.log("AJAX success — message:", response.data.message);
                    console.log("Found .wrap elements:", $(".wrap").length);

                    // update Email Sent column immediately
                    const $row = dropdown.closest("tr");
                    if (status === "completed" || status === "failed") {
                        $row
                            .find("td.column-email_sent")
                            .html('<span style="color:green;">Yes</span>');
                    }

                    // if it's now in any non‐pending state
                    if (status !== "pending") {
                        const label = status.charAt(0).toUpperCase() + status.slice(1);
                        dropdown.closest("td").text(label);
                    }

                    // show a WP admin-notice
                    var notice = $(
                        '<div class="notice notice-success is-dismissible">' +
                        '<button type="button" class="notice-dismiss">' +
                        '<span class="screen-reader-text">Dismiss this notice.</span>' +
                        "</button>" +
                        "<p>" +
                        response.data.message +
                        "</p>" +
                        "</div>"
                    );
                    // prepend inside the main .wrap container (below the page title)
                    $(".wrap").first().prepend(notice);

                    // make the “×” dismiss button work
                    notice.on("click", ".notice-dismiss", function () {
                        notice.remove();
                    });
                }
            },
            "json"
        );
    }
})(jQuery);