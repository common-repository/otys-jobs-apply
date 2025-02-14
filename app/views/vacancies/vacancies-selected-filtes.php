<?php
/**
 * Template used for [otys-selected-filters]
 *
 * @since 1.0.0
 */
 ?>
<div class="vacancies-selected-filters-section">
    <?php if (!empty($args['filters'])) { ?>
        <ul class="vacancies-selected-filters">
        <?php
        foreach ($args['filters'] as $filterName => $filter) {
            ?>
            <li class="vacancies-selected-filter">
                <a href="<?php echo esc_attr($filter['url']); ?>" class="vacancies-selected-filter-link">
                    <span class="vacancies-selected-filter-check">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M312.1 375c9.369 9.369 9.369 24.57 0 33.94s-24.57 9.369-33.94 0L160 289.9l-119 119c-9.369 9.369-24.57 9.369-33.94 0s-9.369-24.57 0-33.94L126.1 256L7.027 136.1c-9.369-9.369-9.369-24.57 0-33.94s24.57-9.369 33.94 0L160 222.1l119-119c9.369-9.369 24.57-9.369 33.94 0s9.369 24.57 0 33.94L193.9 256L312.1 375z"/></svg>
                    </span>
                    <?php echo implode(' ' . __('or', 'otys-jobs-apply') . ' ', $filter['values']); ?>
                </a>
            </li>
            <?php
        }
        ?>
        </ul>
    <?php } ?>
</div>