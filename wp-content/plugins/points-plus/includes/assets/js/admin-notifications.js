// (function ($) {
//   console.log("pp-notifications.js loaded");

//   // Poll the count every 10s
//   setInterval(function () {
//     $.post(
//       PP_Notifications.ajax_url,
//       {
//         action: "get_pending_redemptions_count",
//         nonce: PP_Notifications.nonce
//       },
//       function (resp) {
//         if (resp.success) {
//           $("#wp-admin-bar-pp-pending-redemptions .pp-count-badge").text(
//             resp.data.count
//           );
//         }
//       }
//     );
//   }, 10000);

//   // On click, fetch & show dropdown
//   $(document).on(
//     "click",
//     "#wp-admin-bar-pp-pending-redemptions > a.ab-item",
//     function (e) {
//       e.preventDefault();
//       let $node = $("#wp-admin-bar-pp-pending-redemptions");

//       // Toggle if already there
//       if ($node.find(".pp-dropdown").length) {
//         $node.find(".pp-dropdown").toggle();
//         return;
//       }

//       // Otherwise load items
//       $.post(
//         PP_Notifications.ajax_url,
//         {
//           action: "get_pending_redemptions_items",
//           nonce: PP_Notifications.nonce
//         },
//         function (resp) {
//           if (!resp.success) return;
//           let $ul = $('<ul class="pp-dropdown"></ul>');
//           if (resp.data.length === 0) {
//             $ul.append('<li class="empty">' + "No pending requests</li>");
//           } else {
//             resp.data.forEach(function (item) {
//               $ul.append(
//                 `<li>
//                 <strong>${item.student_name}</strong>
//                 (${item.reload_type})<br>
//                 <small>${item.datetime}</small><br>
//                 <em>SID:</em>${item.student_id}
//                 <em>RID:</em>${item.reward_id}
//               </li>`
//               );
//             });
//           }
//           $node.append($ul);
//         }
//       );
//     }
//   );
// })(jQuery);

(function ($) {
  // update count periodically…
  setInterval(updateCount, 10000);
  function updateCount() {
    $.post(
      PP_Notifications.ajax_url,
      {
        action: "get_pending_redemptions_count",
        nonce: PP_Notifications.nonce
      },
      function (resp) {
        if (resp.success) {
          $("#wp-admin-bar-pp-pending-redemptions .pp-count-badge").text(
            resp.data.count
          );
        }
      }
    );
  }

  // click to toggle
  $(document).on(
    "click",
    "#wp-admin-bar-pp-pending-redemptions .ab-item",
    function (e) {
      e.preventDefault();
      var $li = $("#wp-admin-bar-pp-pending-redemptions");
      var $dropdown = $li.children(".pp-dropdown");
      if ($dropdown.length) {
        $dropdown.toggle();
      } else {
        // first time: fetch & build
        $.post(
          PP_Notifications.ajax_url,
          {
            action: "get_pending_redemptions_items",
            nonce: PP_Notifications.nonce
          },
          function (resp) {
            if (!resp.success) return;
            var $ul = $('<ul class="pp-dropdown"></ul>').appendTo($li);
            if (resp.data.length === 0) {
              $ul.append('<li class="empty">No pending requests</li>');
            } else {
              resp.data.forEach(function (item) {
                $ul.append(
                  "<li>" +
                    "<strong>" +
                    item.student_name +
                    "</strong> (" +
                    item.reload_type +
                    ")<br>" +
                    "<small>" +
                    item.datetime +
                    "</small><br>" +
                    "<em>SID:</em> " +
                    item.student_id +
                    " <em>RID:</em> " +
                    item.reward_id +
                    "</li>"
                );
              });
            }
          }
        );
      }
    }
  );

  // clicking outside closes it
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#wp-admin-bar-pp-pending-redemptions").length) {
      $("#wp-admin-bar-pp-pending-redemptions .pp-dropdown").hide();
    }
  });
})(jQuery);
