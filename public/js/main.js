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

    document.querySelectorAll('.js-delete-event').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const label = btn.getAttribute('data-event-label') || 'this event';
            const ok = confirm(`Are you sure you want to delete "${label}"? This action cannot be undone.`);
            if (!ok) {
                e.preventDefault();
            }
        });
    });

    // Aquest mètode és per actualitzar els equips que es poden escollir segons el valor de
    // l'event que l'usuari escull en editar patits.
    const form   = document.getElementById('match-edit-form');
    const select = document.getElementById('event_id');

    if (!form || !select) return;

    select.addEventListener('change', () => {
        let hidden = form.querySelector('input[name="refresh_teams"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'refresh_teams';
            hidden.value = '1';
            form.appendChild(hidden);
        } else {
            hidden.value = '1';
        }

        form.submit();
    });
});