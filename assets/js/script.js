document.addEventListener('submit', (event) => {
    const form = event.target;

    // Some forms can ask for confirmation before submitting.
    if (
        form instanceof HTMLFormElement
        && form.dataset.confirm
        && !window.confirm(form.dataset.confirm)
    ) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-quantity-action]');
    if (!button) {
        return;
    }

    const control = button.closest('.quantity-control');
    const input = control?.querySelector('input[type="number"]');
    if (!input) {
        return;
    }

    const minimum = Number(input.min);
    const maximum = Number(input.max);
    const direction = button.dataset.quantityAction === 'increase' ? 1 : -1;
    const quantity = Number(input.value) + direction;
    input.value = String(Math.min(maximum, Math.max(minimum, quantity)));
    input.dispatchEvent(new Event('input', { bubbles: true }));
});

document.addEventListener('input', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('.quantity-control input[type="number"]')) {
        return;
    }

    const quantity = Number(input.value);
    if (input.value !== '' && Number.isFinite(quantity)) {
        input.value = String(Math.min(Number(input.max), Math.max(Number(input.min), quantity)));
    }

    const control = input.closest('.quantity-control');
    control.querySelector('[data-quantity-action="decrease"]').disabled = Number(input.value) <= Number(input.min);
    control.querySelector('[data-quantity-action="increase"]').disabled = Number(input.value) >= Number(input.max);
});
