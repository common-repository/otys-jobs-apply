'use strict';

{
    const loginForms = document.querySelectorAll('[name="otys-login-form"]');

     /**
     * Add logic to application forms
     */
     loginForms.forEach((form) => { 
        // Form submit event
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            // Submit form with REST
            doRestCall(form, event);

            return false;
        });
    });


    /**
     * Rest call when submitting form
     */
    const doRestCall = async (form) => {
        let validity = form.reportValidity();
        const identifier = form.getAttribute('data-identifier');

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
                const formContainer = form.querySelectorAll('.rest-form-container')[0];

                // Get the template
                const restFormSuccessTemplates = form.querySelectorAll('.rest-form-success');
                
                const template = restFormSuccessTemplates.length ? restFormSuccessTemplates[0] : null;

                if (template) {
                    // Make a clone of the template so we can use this node and fill it with the information needed
                    const templateClone = template.content.cloneNode(true);

                    const clonePage = templateClone.querySelectorAll('.rest-form-page[data-type="success"]');

                    // Make page active
                    clonePage.forEach((page) => {
                        page.classList.add('current');
                    });

                    // Append the cloned element to the parents template (which is the list)
                    formContainer.appendChild(templateClone);
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
