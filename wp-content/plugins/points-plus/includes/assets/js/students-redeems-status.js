(function ($) {
  $(document).on("change", ".redeem-status-dropdown", function () {
    const dropdown = $(this);
    const postId = dropdown.data("id");
    const status = dropdown.val();

    $.post(
      PointsPlus_Admin.ajax_url,
      {
        action: "update_students_redeems_status",
        post_id: postId,
        status: status,
        _wpnonce: PointsPlus_Admin.nonce
      },
      function (response) {
        if (!response.success) {
          alert(
            "Error: " + (response.data?.message || "Could not update status.")
          );
        }
      }
    );
  });
})(jQuery);
