<?php
/**
 * Template used for [otys-vacancies-list]
 *
 * @since 1.0.0
 */
 ?>

<div class="vacancies-list">
    <?php
    if ($args['vacancies']) {
        foreach ($args['vacancies'] as $vacancy) {
            ?>

            <article class="vacancy">
                <a href="<?php echo esc_url($vacancy['url']) ?>" class="vacancy-content" aria-label="<?php printf(__('Vacancy %s', 'otys-jobs-apply'), $vacancy['title']); ?>">
                    <div class="vacancy-body">
                        <?php
                        if ($vacancy['photoFileName']) {
                            ?>
                            <div class="vacancy-photo">
                                <img src="<?php echo esc_attr($vacancy['photoUrl']) ?>" alt="Thumbnail" />
                            </div>
                            <?php
                            }
                        ?>
                        <div class="vacancy-description">
                            <h3 class="vacancy-title"><?php echo esc_html($vacancy['title']); ?></h3>
                            <div class="vacancy-text">
                                <p>
                                    <?php echo substr(strip_tags(wp_kses_post($vacancy['textField_summary']['text'])), 0, 300); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="vacancy-footer">
                        <div class="vacancy-criteria owp-labels">
                            <?php
                            foreach ($vacancy['labels'] as $labelKey => $label) {
                                foreach ($label['values'] as $value) {
                                ?>
                                    <div class="vacancy-criteria-option owp-label" title="<?php echo esc_html($label['name'])?>">
                                        <?php echo esc_html($value) ?>
                                    </div>
                                <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </a>
            </article>

            <?php
        }
    } else {
        ?>
        <h3><?php _e('No vacancies found', 'otys-jobs-apply'); ?></h3>
        <div class="owp-vacancies-list-text">
            <p><?php _e('There are no vacancies for your search filters. Remove filters to broaden the search.', 'otys-jobs-apply'); ?></p>
            <p><a href="<?php echo strtok(esc_url($_SERVER["REQUEST_URI"]),'?') ?>"><?php _e('Remove all filters', 'otys-jobs-apply') ?></a>
        </div>
        <?php
    }
    ?>
</div>

<?php
load_template(
    OTYS_PLUGIN_TEMPLATE_URL . '/include-parts/pagination.php',
    false,
    ['pagination' => $args['pagination']]
);