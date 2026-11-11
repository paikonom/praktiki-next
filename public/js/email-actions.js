document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.js-email-send-form');

    if (!form) {
        return;
    }

    event.preventDefault();

    if (!window.confirm('Να σταλεί το email;')) {
        return;
    }

    const wrapper = form.closest('[data-email-action]');
    const button = form.querySelector('button[type="submit"]');
    const counter = wrapper.querySelector('.js-email-count');
    const status = wrapper.querySelector('.js-email-status');
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = 'Αποστολή…';
    status.className = 'small text-muted js-email-status';
    status.textContent = '';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Η αποστολή απέτυχε.');
        }

        counter.textContent = `(${result.count})`;
        counter.classList.remove('text-bg-danger');
        counter.classList.add('text-bg-success');
        status.className = 'small text-success js-email-status';
        status.textContent = 'OK!';
    } catch {
        status.className = 'small text-danger js-email-status';
        status.textContent = 'NOK!';
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});
