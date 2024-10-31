<?php
/**
 * Template used for [otys-vacancies-filters]
 *
 * @since 1.0.0
 */
 ?>

<button id="toggle-vacancies-filters" aria-label="<?php echo __('Toggle filters', 'otys-jobs-apply'); ?>">
    <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M3.853 54.87C10.47 40.9 24.54 32 40 32H472C487.5 32 501.5 40.9 508.1 54.87C514.8 68.84 512.7 85.37 502.1 97.33L320 320.9V448C320 460.1 313.2 471.2 302.3 476.6C291.5 482 278.5 480.9 268.8 473.6L204.8 425.6C196.7 419.6 192 410.1 192 400V320.9L9.042 97.33C-.745 85.37-2.765 68.84 3.854 54.87L3.853 54.87z"/></svg>
    <svg class="close-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/></svg>
</button>

<div id="vacancies-filters">
    <div class="vacancies-matchcriteria">
        <h2 class="owp-heading-2"><?php _e('Filters', 'otys-jobs-apply'); ?></h2>
        <?php foreach ($args['matchCriteriaList'] as $criteria_slug => $criteria) {
            if (!array_key_exists($criteria_slug, $args['preSelectedCriteria'])) {
            ?>
            <div class="matchcriteria-filters <?php echo esc_attr($criteria['id']) ?>">
                <h3 class="owp-heading-3"><?php echo esc_attr($criteria['name']) ?></h3>
                <ul>
                    <?php foreach ($criteria['options'] as $option_slug => $option) { ?>
                        <li <?php echo ($option['active']) ? 'class="active"' : '' ?>>
                            <a href="<?php echo '?' . esc_attr($option['url']) ?>" rel="nofollow">
                                <span class="option-check">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M438.6 105.4C451.1 117.9 451.1 138.1 438.6 150.6L182.6 406.6C170.1 419.1 149.9 419.1 137.4 406.6L9.372 278.6C-3.124 266.1-3.124 245.9 9.372 233.4C21.87 220.9 42.13 220.9 54.63 233.4L159.1 338.7L393.4 105.4C405.9 92.88 426.1 92.88 438.6 105.4H438.6z"/></svg>
                                </span>

                                <span class="option-name"><?php echo esc_html($option['name']) ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
            <?php
            }
        } ?>
    </div>
</div>