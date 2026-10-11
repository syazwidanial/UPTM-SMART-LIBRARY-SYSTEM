(() => {
    const passwordButton = document.querySelector('[data-password-toggle]');
    const passwordInput = document.querySelector('#password');

    if (passwordButton && passwordInput) {
        passwordButton.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            passwordButton.textContent = isPassword ? 'Hide' : 'Show';
            passwordButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    const borrowForm = document.querySelector('[data-borrow-form]');
    const duePreview = document.querySelector('[data-due-preview]');

    if (borrowForm && duePreview) {
        const radios = borrowForm.querySelectorAll('input[name="borrow_days"]');
        radios.forEach((radio) => {
            radio.addEventListener('change', () => {
                if (radio.checked && radio.dataset.dueDate) {
                    duePreview.textContent = radio.dataset.dueDate;
                }
            });
        });
    }

    const ratingPicker = document.querySelector('[data-rating-picker]');
    const ratingCaption = document.querySelector('[data-rating-caption]');

    if (ratingPicker) {
        const choices = [...ratingPicker.querySelectorAll('[data-rating-value]')];
        const captions = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];

        const paint = (value, className) => {
            choices.forEach((choice) => {
                const choiceValue = Number(choice.dataset.ratingValue || 0);
                choice.classList.toggle(className, choiceValue <= value);
            });
        };

        const clearHover = () => choices.forEach((choice) => choice.classList.remove('is-hovered'));

        const syncSelected = () => {
            const checked = ratingPicker.querySelector('input[name="rating"]:checked');
            const value = checked ? Number(checked.value) : 0;
            choices.forEach((choice) => choice.classList.remove('is-active'));
            paint(value, 'is-active');
            if (ratingCaption) {
                ratingCaption.textContent = value ? captions[value] : 'Select 1 to 5 stars';
            }
        };

        choices.forEach((choice) => {
            const value = Number(choice.dataset.ratingValue || 0);
            const input = choice.querySelector('input[name="rating"]');

            choice.addEventListener('mouseenter', () => {
                clearHover();
                paint(value, 'is-hovered');
                if (ratingCaption) ratingCaption.textContent = captions[value];
            });

            choice.addEventListener('mouseleave', () => {
                clearHover();
                syncSelected();
            });

            if (input) input.addEventListener('change', syncSelected);
        });

        syncSelected();
    }
})();
