document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("userMenuBtn");
    const dropdown = document.getElementById("userDropdown");

    if (btn && dropdown) {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdown.classList.toggle("open");
        });

        document.addEventListener("click", (e) => {
            if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.remove("open");
            }
        });
    }

    document.querySelectorAll('.js-delete-match').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const matchName = this.dataset.matchLabel || 'this match';
            if (confirm(`Are you sure you want to delete ${matchName}?`)) {
                window.location.href = this.href;
            }
        });
    });

    document.querySelectorAll('.js-delete-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const label = btn.getAttribute('data-event-label') || 'this event';
            if (!confirm(`Are you sure you want to delete "${label}"? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    const form = document.getElementById('match-edit-form');
    const select = document.getElementById('event_id');

    if (form && select) {
        select.addEventListener('change', () => {
            let hidden = form.querySelector('input[name="refresh_teams"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'refresh_teams';
                hidden.value = '1';
                form.appendChild(hidden);
            }
            form.submit();
        });
    }

    document.querySelectorAll('.js-delete-user').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const userLabel = this.dataset.userLabel || 'this user';
            if (confirm(`Are you sure you want to delete ${userLabel}? All their predictions will be removed.`)) {
                window.location.href = this.href;
            }
        });
    });

    document.querySelectorAll('.js-delete-team').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const teamLabel = this.dataset.teamLabel || 'this team';
            if (confirm(`Are you sure you want to delete ${teamLabel}?`)) {
                window.location.href = this.href;
            }
        });
    });
});