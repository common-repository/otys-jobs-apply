'use strict';

{
    const applicationForms = document.querySelectorAll('[name="application-form"], [name="interactions-form"], [name="otys-login-form"], [name="otys-forgot-password-form"]');

    /**
     * Sets page validation
     */
    const setPageValidation = (page) => {
        if (!page.classList.contains('current')) {
            page.querySelectorAll('[required]').forEach((formField) => {
                formField.removeAttribute('required');
                formField.setAttribute('data-required', true);
            });
        }

        if (page.classList.contains('current')) {
            page.querySelectorAll('[data-required]').forEach((formField) => {
                formField.removeAttribute('data-required');
                formField.setAttribute('required', true);
            });
        }
    };

    /**
     * Toggles form buttons
     */
    const toggleButtons = (form) => {
        const pages = form.querySelectorAll('.rest-form-page');
        const totalPages = pages.length;
        const currentPageEl = form.querySelectorAll('.rest-form-page.current')[0];
        const currentPage = parseInt(currentPageEl.getAttribute('data-page')) + 1;

        const nextButton = form.querySelectorAll('.vacancy-apply-next');
        const prevButton = form.querySelectorAll('.vacancy-apply-prev');
        const applyButton = form.querySelectorAll('.vacancy_apply_button');

        if (totalPages == currentPage) {
            applyButton.forEach((button) => {
                button.classList.remove('hide-button');
            });
        } else {
            applyButton.forEach((button) => {
                button.classList.add('hide-button');
            });
        }

        if (parseInt(currentPage + 1) <= totalPages) {
            nextButton.forEach((button) => {
                button.classList.remove('hide-button');
            });
        } else {
            nextButton.forEach((button) => {
                button.classList.add('hide-button');
            });
        }

        if (currentPage - 1 > 0 && totalPages > 0) {
            prevButton.forEach((button) => {
                button.classList.remove('hide-button');
            });
        } else {
            prevButton.forEach((button) => {
                button.classList.add('hide-button');
            });
        }
    };

    /**
     * Add form attributes
     */
    const addFormAttributes = (form) => {
        const pages = form.querySelectorAll('.rest-form-page');

        pages.forEach((page, key) => {
            page.removeAttribute('data-page');
            page.setAttribute('data-page', key);

            if (page.getAttribute('data-page') === null) {
                page.setAttribute('data-type', 'questions');
            }

            setPageValidation(page);
        });
    };

    /**
     * Add logic to application forms
     */
    if (applicationForms.length > 0) {
        applicationForms.forEach((form) => { 
            addFormAttributes(form);
            toggleButtons(form);

            const nextButton = form.querySelectorAll('.vacancy-apply-next');
            const prevButton = form.querySelectorAll('.vacancy-apply-prev');

            // Bind next button eventlistner
            nextButton.forEach((button) => {
                button.addEventListener('click', () => {
                    const pages = form.querySelectorAll('.rest-form-page');
                    const currentPageEl = form.querySelectorAll('.rest-form-page.current')[0];
                    const currentPage = currentPageEl.getAttribute('data-page');
                    const next = parseInt(parseInt(currentPage) + 1);
                    const validity = form.reportValidity();

                    // Show form errors 
                    if (!validity) {
                        return;
                    }

                    pages.forEach((page) => {
                        page.classList.remove('current');
                    });

                    pages[next].classList.add('current');

                    toggleButtons(form);

                    pages[next].scrollIntoView({
                        behavior: 'smooth'
                    });

                    setPageValidation(pages[next]);

                    window.scrollTo({
                        behavior: 'smooth',
                        top:
                            pages[next].getBoundingClientRect().top -
                            document.body.getBoundingClientRect().top -
                            80
                    })
                });
            });

            // Bind prev button eventlistner
            prevButton.forEach((button) => {
                button.addEventListener('click', () => {
                    const pages = form.querySelectorAll('.rest-form-page');
                    const currentPageEl = form.querySelectorAll('.rest-form-page.current')[0];
                    const currentPage = currentPageEl.getAttribute('data-page');
                    const prev = parseInt(currentPage - 1);
                    const pageType = currentPageEl.getAttribute('data-type');

                    // If user navigates back, remove confirm email page
                    if (pageType === 'confirm-email') {
                        currentPageEl.remove();
                    }

                    pages.forEach((page) => {
                        // Remove the current class from each page
                        page.classList.remove('current');

                        // Set browser validation for each page
                        setPageValidation(page);
                    });

                    // Add current class to current page
                    pages[prev].classList.add('current');

                    // Enable browser validation for current page
                    setPageValidation(pages[prev]);

                    // Toggle form buttons
                    toggleButtons(form);

                    // Scroll page into the view
                    pages[prev].scrollIntoView({
                        behavior: 'smooth'
                    });

                    window.scrollTo({
                        behavior: 'smooth',
                        top:
                            pages[prev].getBoundingClientRect().top -
                            document.body.getBoundingClientRect().top -
                            80
                    })
                });
            });

            // Form submit event
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                // Submit form with REST
                doRestCall(form, event);

                return false;
            });
        });
    }

    /**
     * Rest call when submitting form
     */
    const doRestCall = async (form) => {
        let validity = form.reportValidity();
        const identifier = form.getAttribute('data-identifier');
        const callbackMethod = form.getAttribute('data-callback') ? form.getAttribute('data-callback') : null;

        // Show form errors 
        if (!validity) {
            return;
        }

        form.classList.add('loading');

        // Remove shown errors
        form.querySelectorAll('.input_errors').forEach((element) => {
            element.innerHTML = '';
        });

        form.querySelectorAll('.rest-form-footer-errors').forEach((element) => {
            element.innerHTML = '';
        });

        // Remove invalid class from input fields
        form.querySelectorAll('.invalid').forEach((element) => {
            element.classList.remove('invalid');
        });

        // Get all field data from the form
        let data = new FormData(form);

        const response = await fetch(form.getAttribute('action'), {
            method: 'POST',
            body: data
        });

        const json = await response.json(); //extract JSON from the http response

        document.dispatchEvent(new CustomEvent('otys-rest-form-callback', {
            detail: {
                formData: data,
                response: json,
                status: response.status
            }
        }));

        /**
         * Call is succesfull
         */
        if (response.status === 200) { 
            // Check if we should redirect to a external thank you page
            if (typeof json.redirect !== 'undefined') {
                window.location.href = json.redirect.value;

                return;
            }

            form.classList.add('success');
            form.classList.remove('loading');

            // Show thank you message
            if ('content' in document.createElement('template')) {
                // Form pages
                const formPages = form.querySelectorAll('.rest-form-pages');

                // Get the template
                const restFormSuccessTemplates = form.querySelectorAll('.rest-form-success');
                
                const template = restFormSuccessTemplates.length ? restFormSuccessTemplates[0] : null;

                if (template) {
                    // Make a clone of the template so we can use this node and fill it with the information needed
                    const templateClone = template.content.cloneNode(true);

                    const clonePages = templateClone.querySelectorAll('.rest-form-page[data-type="success"]');

                    // Make page active
                    clonePages.forEach((page) => {
                        page.classList.add('current');
                    });

                    let pages = form.querySelectorAll('.rest-form-page');

                    pages.forEach((element) => {
                        element.classList.remove('current');
                    });

                    // Append the cloned element to the parents template (which is the list)
                    formPages.forEach((formPagesEl) => {
                        formPagesEl.appendChild(templateClone);
                    });

                    addFormAttributes(form);
                }
            }
        }

        /**
         * Call needs confirmation
         */
        if (response.status === 409) {
            const confirmPage = form.querySelectorAll('.rest-form-page[data-type="confirm-email"]');

            if (confirmPage.length === 0 && 'content' in document.createElement('template')) {
                // Form pages
                const formPages = form.querySelectorAll('.rest-form-pages');

                // Get the template
                const restFormConfirmEmailTemplates = form.querySelectorAll('.rest-form-confirm-email');

                const template = restFormConfirmEmailTemplates ? restFormConfirmEmailTemplates[0] : null;
                
                if (template) {
                    // Make a clone of the template so we can use this node and fill it with the information needed
                    const templateClone = template.content.cloneNode(true);

                    const clonePages = templateClone.querySelectorAll('.rest-form-page[data-type="confirm-email"]');

                    // Make page active
                    clonePages.forEach((page) => {
                        page.classList.add('current');

                    });

                    let pages = form.querySelectorAll('.rest-form-page');

                    pages.forEach((element) => {
                        element.classList.remove('current');
                    });

                    // Append the cloned element to the parents template (which is the list)
                    formPages.forEach((formPagesEl) => {
                        formPagesEl.appendChild(templateClone);
                    });
                }
            }
        }

        /**
         * Internal server error
         */
        if (response.status === 500) {
            // Do rest form callback
       
            console.log(json.code);
            const errorContainers = form.querySelectorAll('.rest-form-footer-errors');
            const errorContainer = errorContainers.length ? errorContainers[0] : null;

            console.log(errorContainers);
            console.log(errorContainer);
            
            if (errorContainer) {
                if ('content' in document.createElement('template')) {
                    // Get the template
                    const inputErrorTemplates = form.querySelectorAll('.input-error-template');
                    
                    const template = inputErrorTemplates.length ? inputErrorTemplates[0] : null;

                    if (template) {
                        // Make a clone of the template so we can use this node and fill it with the information needed
                        let templateClone = template.content.cloneNode(true);

                        let inputErrorElements = templateClone.querySelectorAll('.input_error');

                        if (inputErrorElements !== null) {
                            inputErrorElements.forEach((element) => {
                                element.innerHTML = json.message;
                            });
                        }

                        console.log(inputErrorElements);
 
                        // Append the cloned element to the parents template (which is the list)
                        errorContainer.appendChild(templateClone);

                        console.log(errorContainer);
                    }
                }
            }
        }

        /**
         * Call has errors, show errors in form
         */
        if (!response.ok && (response.status === 400 || response.status === 409)) {
            // Loop through errors
            const jsonKeys = Object.keys(json);

            let errorPage = false;

            jsonKeys.forEach((key) => {
                const value = json[key];

                const keyId = key + identifier;

                const inputField = document.getElementById(keyId);
                let errorContainer = document.getElementById('input-errors-' + keyId);

                if (errorContainer === null) {
                    const errorContainers = form.querySelectorAll('.rest-form-footer-errors');
                    errorContainer = errorContainers.length ? errorContainers[0] : null;
                }

                if (value.errors && value.errors.length) {
                    errorContainer.classList.add('invalid');

                    if (inputField) {
                        inputField.classList.add('invalid');

                        if (errorPage === false && errorContainer !== null) {
                            let inputFieldPage = inputField.closest('.rest-form-page');

                            if (inputFieldPage) {
                                if (inputFieldPage !== null) {
                                    errorPage = true;

                                    let pages = form.querySelectorAll('.rest-form-page');

                                    pages.forEach((element) => {
                                        element.classList.remove('current');
                                    });

                                    inputFieldPage.classList.add('current');

                                    window.scrollTo({
                                        behavior: 'smooth',
                                        top:
                                            inputField.getBoundingClientRect().top -
                                            document.body.getBoundingClientRect().top -
                                            80
                                    });
                                }
                            }
                        }
                    }

                    // Add errors to the form based on the error template
                    value.errors.forEach((error) => {
                        if ('content' in document.createElement('template')) {
                            // Get the template
                            const inputErrorTemplates = form.querySelectorAll('.input-error-template');
                            
                            const template = inputErrorTemplates.length ? inputErrorTemplates[0] : null;

                            if (template) {
                                // Make a clone of the template so we can use this node and fill it with the information needed
                                let templateClone = template.content.cloneNode(true);

                                let inputErrorElements = templateClone.querySelectorAll('.input_error');

                                if (inputErrorElements !== null) {
                                    inputErrorElements.forEach((element) => {
                                        element.innerHTML = error;
                                    });
                                }

                                // Append the cloned element to the parents template (which is the list)
                                errorContainer.appendChild(templateClone);
                            }
                        }
                    });
                } else {
                    if (inputField) {
                        inputField.classList.remove('invalid');
                        inputField.classList.add('valid');
                    }
                }
            });

            addFormAttributes(form);
            toggleButtons(form);
        }

        /**
         * Reset reCaptcha
         */
        grecaptcha.ready(function () {
            const recaptchaButtons = form.querySelectorAll('.g-recaptcha');

            if (recaptchaButtons.length) {
                recaptchaButtons.forEach((button) => {
                    const token = button.getAttribute('data-sitekey');

                    grecaptcha.execute(token, {action: 'submit'}).then((token) => {
                    });
                });
            }
        });

        form.classList.remove('loading');

        return json;
    }
}