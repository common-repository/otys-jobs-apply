'use strict';

{
    const checkMatchCriteria = function() {
        let filterInputs = document.querySelectorAll('[id*="otys_option_jobalert_filters-matchCriteria"]');

        let matchCriteria = 0;

        filterInputs.forEach(filterInput => {
            // check if input is checked
            if (filterInput.checked) {
                matchCriteria++;
            }

        });

        return matchCriteria;
    }
    
    const checkIfWeMaySave = function() {
        let matchCriteria = checkMatchCriteria();

        let saveButton = document.getElementById('submit');

        if (saveButton) {
            const criteriaError = document.getElementById('match-criteria-error');

            if (criteriaError) {
                if (matchCriteria > 0) {
                    saveButton.disabled = false;
                    criteriaError.style.display = 'none';
                    return true;
                } 
            
                criteriaError.style.display = 'block';

                saveButton.disabled = true;
            }
        }
        
        return false;
    }

    checkIfWeMaySave();

    let form = document.querySelector('form');

    if (form) {
        // Listen for input events on the form
        form.addEventListener('input', function (event) {
            const weMaySave = checkIfWeMaySave();
        });
    }
}