'use strict'

{
    let filtersToggle = document.getElementById("toggle-vacancies-filters");

    if (typeof filtersToggle !== 'undefined') {
        filtersToggle.addEventListener("click", () => {
            document.body.classList.toggle("show-vacancies-filters");
        });
    }
}