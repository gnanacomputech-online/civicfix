(function () {
    'use strict';

    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.display = 'none';
        }, 6000);
    });

    // Photo input preview (complaint filing / resolution proof forms).
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-preview');
            var target = document.getElementById(targetId);
            if (!target || !input.files || !input.files[0]) {
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                target.src = e.target.result;
                target.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        });
    });
})();
