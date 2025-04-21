document.addEventListener('DOMContentLoaded', function() {
    function updateCleanCountdowns() {
        const containers = document.querySelectorAll('.clean-countdown-container');

        containers.forEach(container => {
            const endTime = new Date(container.dataset.endTime).getTime();
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                container.innerHTML = '<div class="countdown-expired">Offer Expired</div>';
                return;
            }

            // Calculate time units
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Update urgent state if less than 24 hours left
            const totalHoursLeft = (days * 24) + hours;
            container.dataset.urgent = totalHoursLeft < 24 ? 'true' : 'false';

            // Update each time unit
            if (days > 0) {
                const dayElement = container.querySelector('.days .value');
                if (dayElement) dayElement.textContent = days;
            }

            const hourElement = container.querySelector('.hours .value');
            if (hourElement) hourElement.textContent = String(hours).padStart(2, '0');

            const minuteElement = container.querySelector('.minutes .value');
            if (minuteElement) minuteElement.textContent = String(minutes).padStart(2, '0');

            const secondElement = container.querySelector('.seconds .value');
            if (secondElement) secondElement.textContent = String(seconds).padStart(2, '0');
        });
    }

    // Update immediately
    updateCleanCountdowns();

    // Then update every second
    setInterval(updateCleanCountdowns, 1000);
});