'use strict';
{
    /**
     * OWP Range sliders
     */
    const rangeSliders = document.getElementsByClassName('owp-range-slider-container');

    if (rangeSliders) {
        Array.from(rangeSliders).forEach((rangeSlider) => {
            let slider = rangeSlider.getElementsByClassName('owp-range-slider')[0];
            let maxValue = rangeSlider.getElementsByClassName('owp-range-slider-value')[0];
            
            slider.addEventListener('input', (event) => {
                const sliderValue = slider.value;
                maxValue.innerHTML = sliderValue;
            });
        })
    }
}