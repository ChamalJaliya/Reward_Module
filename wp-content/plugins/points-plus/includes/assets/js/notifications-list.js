// (function(){
//     document.addEventListener('click', function(e){
//       var btn = e.target.closest('.mark-as-read-button');
//       if (!btn) return;
//       e.preventDefault();

//       // Find the <li> and container
//       var li        = btn.closest('.notification-item');
//       var container = li.closest('.pp-notifications-container');

//       // Only if it was unread
//       if ( li && container && li.classList.contains('unread') ) {
//         li.classList.remove('unread');
//         li.classList.add('read');
//         btn.remove();

//         // Decrement count and fire an event
//         var count = parseInt( container.dataset.unreadCount, 10 ) || 0;
//         count = Math.max(0, count - 1);
//         container.dataset.unreadCount = count;

//         var ev = new CustomEvent('ppNotifications.countChanged', {
//           detail: { unreadCount: count }
//         });
//         container.dispatchEvent(ev);
//       }
//     });
//   })();

// Working

// (function () {
//   document.addEventListener("click", function (e) {
//     const btn = e.target.closest(".mark-as-read-button");
//     if (!btn) return;
//     e.preventDefault();

//     const li = btn.closest(".notification-item");
//     const container = li.closest(".pp-notifications-container");

//     if (li && container && li.classList.contains("unread")) {
//       // prepare for animation
//       const height = li.offsetHeight + "px";
//       li.style.height = height;
//       li.style.transition =
//         "opacity 0.4s, height 0.4s, margin 0.4s, padding 0.4s";
//       li.style.opacity = "1";

//       // trigger fade/slide
//       requestAnimationFrame(() => {
//         li.style.opacity = "0";
//         li.style.height = "0";
//         li.style.margin = "0";
//         li.style.padding = "0";
//       });

//       // after animation, remove from DOM & update count
//       setTimeout(() => {
//         li.parentNode.removeChild(li);

//         let count = parseInt(container.dataset.unreadCount, 10) || 0;
//         count = Math.max(0, count - 1);
//         container.dataset.unreadCount = count;

//         container.dispatchEvent(
//           new CustomEvent("ppNotifications.countChanged", {
//             detail: { unreadCount: count }
//           })
//         );
//       }, 400);
//     }
//   });
// })();

// Working Perfectly

// (function () {
//   document.addEventListener("click", function (e) {
//     const btn = e.target.closest(".mark-as-read-button");
//     if (!btn) return;
//     e.preventDefault();

//     const li = btn.closest(".notification-item");
//     const container = li.closest(".pp-notifications-container");
//     // const rowIndex = li.dataset.rowIndex;
//     const rowIndex = li.dataset.originalIndex;

//     if (li && container && li.classList.contains("unread")) {
//       const formData = new URLSearchParams();
//       formData.append("action", "pp_mark_notification_read");
//       formData.append("student_id", ppNotifications.student_id);
//       formData.append("row_index", rowIndex);
//       formData.append("nonce", ppNotifications.nonce);

//       fetch(ppNotifications.ajaxurl, {
//         method: "POST",
//         headers: {
//           "Content-Type": "application/x-www-form-urlencoded"
//         },
//         body: formData
//       })
//         .then((response) => response.json())
//         .then((data) => {
//           if (data.success) {
//             // Animation and removal code
//             const height = li.offsetHeight + "px";
//             li.style.height = height;
//             li.style.transition =
//               "opacity 0.4s, height 0.4s, margin 0.4s, padding 0.4s";
//             li.style.opacity = "1";

//             requestAnimationFrame(() => {
//               li.style.opacity = "0";
//               li.style.height = "0";
//               li.style.margin = "0";
//               li.style.padding = "0";
//             });

//             setTimeout(() => {
//               li.parentNode.removeChild(li);

//               // Remove parent UL if empty
//               const list = container.querySelector(".notifications-list");
//               if (list.children.length === 0) {
//                 list.parentNode.removeChild(list);
//               }

//               let count = parseInt(container.dataset.unreadCount, 10) || 0;
//               count = Math.max(0, count - 1);
//               container.dataset.unreadCount = count;
//               container.dispatchEvent(
//                 new CustomEvent("ppNotifications.countChanged", {
//                   detail: { unreadCount: count }
//                 })
//               );
//             }, 400);
//           } else {
//             alert("Failed to mark notification as read.");
//           }
//         })
//         .catch((error) => {
//           console.error("Error:", error);
//         });
//     }
//   });
// })();

// (function () {
//   document.addEventListener("click", function (e) {
//     const btn = e.target.closest(".mark-as-read-button");
//     if (!btn) return;
//     e.preventDefault();

//     const li = btn.closest(".notification-item");
//     const container = li.closest(".pp-notifications-container");
//     const rowIndex = li.dataset.originalIndex || li.dataset.rowIndex;

//     if (!li.classList.contains("unread")) return;

//     // Prepare AJAX payload
//     const params = new URLSearchParams();
//     params.append("action", "pp_mark_notification_read");
//     params.append("student_id", ppNotifications.student_id);
//     params.append("row_index", rowIndex);
//     params.append("nonce", ppNotifications.nonce);

//     fetch(ppNotifications.ajaxurl, {
//       method: "POST",
//       headers: { "Content-Type": "application/x-www-form-urlencoded" },
//       body: params
//     })
//       .then((res) => res.json())
//       .then((json) => {
//         if (!json.success) {
//           alert("Failed to mark notification as read.");
//           return;
//         }

//         // Trigger the CSS transition
//         li.classList.add("removing");

//         // Wait for the max-height transition to finish
//         li.addEventListener("transitionend", function handler(evt) {
//           if (evt.propertyName !== "max-height") return;
//           li.removeEventListener("transitionend", handler);

//           // Remove the <li>
//           li.remove();

//           // Update unread badge count
//           let count = parseInt(container.dataset.unreadCount, 10) || 0;
//           count = Math.max(0, count - 1);
//           container.dataset.unreadCount = count;
//           container.dispatchEvent(
//             new CustomEvent("ppNotifications.countChanged", {
//               detail: { unreadCount: count }
//             })
//           );

//           // If list empty, remove the UL
//           const list = container.querySelector(".notifications-list");
//           if (list && list.children.length === 0) {
//             list.remove();
//           }
//         });
//       })
//       .catch((err) => {
//         console.error("Error marking notification read:", err);
//         alert("An error occurred.");
//       });
//   });
// })();

// (function () {
//   document.addEventListener("click", function (e) {
//     const btn = e.target.closest(".mark-as-read-button");
//     if (!btn) return;
//     e.preventDefault();

//     const li = btn.closest(".notification-item");
//     const container = li.closest(".pp-notifications-container");
//     const rowIndex = li.dataset.originalIndex;

//     if (!li.classList.contains("unread")) return;

//     // Prepare AJAX payload
//     const params = new URLSearchParams();
//     params.append("action", "pp_mark_notification_read");
//     params.append("student_id", ppNotifications.student_id);
//     params.append("row_index", rowIndex);
//     params.append("nonce", ppNotifications.nonce);

//     fetch(ppNotifications.ajaxurl, {
//       method: "POST",
//       headers: { "Content-Type": "application/x-www-form-urlencoded" },
//       body: params
//     })
//       .then((res) => res.json())
//       .then((json) => {
//         if (!json.success) {
//           alert("Failed to mark notification as read.");
//           return;
//         }

//         // Trigger the CSS transition
//         li.classList.add("removing");

//         // Wait for the transition to finish
//         li.addEventListener("transitionend", function handler(evt) {
//           if (evt.propertyName !== "max-height") return;
//           li.removeEventListener("transitionend", handler);

//           // Remove the <li>
//           li.remove();

//           // Update unread count
//           let count = parseInt(container.dataset.unreadCount, 10) || 0;
//           count = Math.max(0, count - 1);
//           container.dataset.unreadCount = count;

//           // Dispatch count change event
//           container.dispatchEvent(
//             new CustomEvent("ppNotifications.countChanged", {
//               detail: { unreadCount: count }
//             })
//           );

//           // Check if list is empty
//           const list = container.querySelector(".notifications-list");
//           if (list && list.children.length === 0) {
//             // Remove list and show message
//             list.remove();
//             container.insertAdjacentHTML(
//               "beforeend",
//               ppNotifications.no_notifications_message
//             );

//             // Optional: Update container styling
//             container.classList.add("empty");
//           }
//         });
//       })
//       .catch((err) => {
//         console.error("Error marking notification read:", err);
//         alert("An error occurred.");
//       });
//   });
// })();

(function () {
  document.addEventListener("click", function (e) {
    // Only fire when clicking an UNREAD item (not its children)
    const li = e.target.closest(".notification-item.unread");
    if (!li) return;
    e.preventDefault();

    const container = li.closest(".pp-notifications-container");
    const rowIndex = li.dataset.originalIndex;

    // AJAX payload
    const params = new URLSearchParams();
    params.append("action", "pp_mark_notification_read");
    params.append("student_id", ppNotifications.student_id);
    params.append("row_index", rowIndex);
    params.append("nonce", ppNotifications.nonce);

    fetch(ppNotifications.ajaxurl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params
    })
      .then((res) => res.json())
      .then((json) => {
        if (!json.success) {
          alert("Failed to mark notification as read.");
          return;
        }

        // Flip it to “read” state
        li.classList.remove("unread");
        li.classList.add("read");

        // Update badge count
        let count = parseInt(container.dataset.unreadCount, 10) || 0;
        count = Math.max(0, count - 1);
        container.dataset.unreadCount = count;
        container.dispatchEvent(
          new CustomEvent("ppNotifications.countChanged", {
            detail: { unreadCount: count }
          })
        );
      })
      .catch((err) => {
        console.error("Error marking notification read:", err);
        alert("An error occurred.");
      });
  });
})();
