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
