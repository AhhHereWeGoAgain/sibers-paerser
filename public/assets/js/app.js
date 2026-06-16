document.addEventListener('DOMContentLoaded', function () {
    const lazy_image_details = document.querySelectorAll('.image-details');

    lazy_image_details.forEach(function (details) {
        details.addEventListener('toggle', function () {
            if (!details.open) {
                return;
            }

            const image = details.querySelector('img[data-src]');

            if (!image) {
                return;
            }

            image.src = image.dataset.src;
            image.removeAttribute('data-src');
        });
    });
});