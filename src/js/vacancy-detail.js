'use strict';

{
    const slider = document.getElementsByClassName('vacancy-header-slider');

    if (slider.length > 0) {
        new Glide('.vacancy-header-slider', {
            type: 'carousel',
            autiplay: '2000',
            gap: 0
        }).mount();
    }
}

{
    /**
     * Track view count
     */
    let formData = new FormData();

    formData.append('vacancy_url', window.location.href);

    fetch(otys_rest.end_point + 'vacancy/analytics', {
        method : "POST",
        body: formData
    });
}