'use strict';

{
    /**
     * Repeater
     */
    let repeatingLists = document.querySelectorAll (".repeating-list");

    if (repeatingLists) {
        repeatingLists.forEach(list => {
            list.addEventListener('click', (event) => {
                let target = event.target;

                if (target.classList.contains('route-list-add')) {
                    if ('content' in document.createElement('template')) {
                        // Get template id so we can select the template to use
                        let templateId = list.getAttribute('id') + '-template';

                        // Ge the template
                        let template = document.getElementById(templateId);

                        // Make a clone of the template so we can use this node and fill it with the information needed
                        let templateClone = template.content.cloneNode(true);

                        let items = list.querySelectorAll('.repeating-item');
                        
                        // Generate a key that is higher then the last data-key
                        let key = (items.length > 0) ? Number(Number(items[items.length - 1].getAttribute('data-key')) + 1) : 0;
                        
                        // Get repeating items of cloned element
                        let clonedRepeatingItems = templateClone.querySelectorAll('.repeating-item');
                        
                        // Loop through repeating items of cloned element and edit each repeating item
                        clonedRepeatingItems.forEach(repeatingItem => {
                            let elementKey = repeatingItem.getAttribute('data-key');
                            
                            repeatingItem.setAttribute('data-key', elementKey.replace('${key}', key));
                            
                            // Get the named items of the repeating item and loop through them to edit them
                            let namedElements = repeatingItem.querySelectorAll('[name]');
        
                            // Loop through all named elements and replace the ${key}
                            namedElements.forEach(namedElement => {
                                // Get the name of the current element
                                let elementName = namedElement.getAttribute('name');
        
                                // Rename the element
                                namedElement.name =  elementName.replace('${key}', key);
                            });
                        });
                        
                        // Set data-key for cloned element
                        templateClone["data-key"] = key;

                        // Append the cloned element to the parents template (which is the list)
                        template.parentElement.appendChild(templateClone);

                        let listErrors = target.closest('.repeating-list').querySelectorAll('.error-message');
                        
                        if (listErrors.length) {
                            listErrors[0].remove();
                        }
                        
                        // templateParent.appendChild(templateClone);
                    } else {
                        alert('Please use a modern browser like the latest version of Google Chrome');
                    }

                    event.preventDefault();
                }

                if (target.classList.contains('delete')) {
                    target.parentElement.parentElement.remove();
                    
                    event.preventDefault();
                }
            });
        });
    }
}

{
    /**
     * WP OPTIONS
     */
    const ajaxForm = document.getElementById('otys-ajax-form');

    if (ajaxForm !== null) {

        const ajaxFormEl = ajaxForm.form;
        const action = ajaxForm.getAttribute('action');

        ajaxFormEl.addEventListener('submit', function (event) {
            const data = this;
            ajaxFormEl.classList.add('loading');
        });
    }
}