/**
 * Small, dependency-free enhancements for navigation, dialogs, forms, and status controls.
 * Every workflow remains server-validated when JavaScript is unavailable.
 */
document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const navigation = document.querySelector('.primary-nav');
    if (navToggle && navigation) {
        navToggle.addEventListener('click', () => {
            const open = navigation.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', String(open));
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.password-field')?.querySelector('input');
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.textContent = reveal ? 'Hide' : 'Show';
        });
    });

    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.dialogOpen);
            if (dialog instanceof HTMLDialogElement) dialog.showModal();
        });
    });
    document.querySelectorAll('dialog[data-open-on-load]').forEach((dialog) => dialog.showModal());
    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rectangle = dialog.getBoundingClientRect();
            const outside = event.clientX < rectangle.left || event.clientX > rectangle.right || event.clientY < rectangle.top || event.clientY > rectangle.bottom;
            if (outside) dialog.close();
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
    document.querySelectorAll('.toast button').forEach((button) => button.addEventListener('click', () => button.closest('.toast')?.remove()));

    document.querySelectorAll('[data-inventory-form] .switch').forEach((switchLabel) => {
        const checkbox = switchLabel.querySelector('input');
        const label = switchLabel.querySelector('em');
        const row = switchLabel.closest('tr');
        const stock = row?.querySelector('.stock-input');
        const sync = () => {
            if (stock && Number(stock.value) <= 0) checkbox.checked = false;
            label.textContent = checkbox.checked ? 'Available' : 'Unavailable';
        };
        checkbox.addEventListener('change', sync);
        stock?.addEventListener('input', sync);
    });

    document.querySelectorAll('[data-outcome-form]').forEach((form) => {
        const batch = form.querySelector('[data-batch-field] input');
        const radios = form.querySelectorAll('input[name="outcome"]');
        const sync = () => {
            const vaccinated = form.querySelector('input[name="outcome"]:checked')?.value === 'completed';
            batch.required = vaccinated;
            batch.closest('label').hidden = !vaccinated;
        };
        radios.forEach((radio) => radio.addEventListener('change', sync));
        sync();
    });
});
