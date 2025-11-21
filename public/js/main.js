document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("userMenuBtn");
    const dropdown = document.getElementById("userDropdown");

    if (!btn || !dropdown) return;

    btn.addEventListener("click", (e) => {
        e.stopPropagation(); 
        dropdown.classList.toggle("open");
    });

    document.addEventListener("click", (e) => {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove("open");
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-delete-match').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const matchName = this.dataset.matchLabel || 'this match';

            const ok = confirm(`Are you sure you want to delete ${matchName}?`);

            if (ok) {
                window.location.href = this.href;
            }
        });
    });
});