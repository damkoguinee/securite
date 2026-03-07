document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form");
    
    forms.forEach(function (form) {
        const submitBtn = form.querySelector("button[type='submit']");
        if (!submitBtn) return;

        form.addEventListener("submit", function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        });
    });
});
