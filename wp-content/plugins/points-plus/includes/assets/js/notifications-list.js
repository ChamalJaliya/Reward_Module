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
