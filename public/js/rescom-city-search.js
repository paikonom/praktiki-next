document.querySelectorAll('[data-city-search]').forEach((input) => {
    const hiddenInput = document.getElementById(input.dataset.target);
    const results = document.getElementById(input.dataset.results);
    let timeout;

    input.addEventListener('input', () => {
        hiddenInput.value = '';
        results.replaceChildren();
        clearTimeout(timeout);

        if (input.value.trim().length < 2) {
            return;
        }

        timeout = setTimeout(async () => {
            try {
                const response = await fetch(`${input.dataset.url}?q=${encodeURIComponent(input.value.trim())}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const cities = await response.json();
                results.replaceChildren(...cities.map((city) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action py-2';
                    button.textContent = city.text;
                    button.addEventListener('click', () => {
                        input.value = city.text;
                        hiddenInput.value = city.id;
                        results.replaceChildren();
                    });

                    return button;
                }));
            } catch {
                results.replaceChildren();
            }
        }, 250);
    });
});
