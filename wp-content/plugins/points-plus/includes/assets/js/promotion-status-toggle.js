(function ($) {
  $(document).on("change", ".promotion-status-toggle", function () {
    var checkbox = $(this),
      postId = checkbox.data("id"),
      newState = checkbox.prop("checked") ? 1 : 0;

    $.post(
      PointsPlus_Admin.ajax_url,
      {
        action: "toggle_promotion_status",
        post_id: postId,
        status: newState,
        _wpnonce: PointsPlus_Admin.nonce
      },
      function (response) {
        if (!response.success) {
          alert("Could not update status");
          // revert UI
          checkbox.prop("checked", !newState);
        }
      }
    );
  });
})(jQuery);
