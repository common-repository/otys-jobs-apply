<?php
/**
 * Template used for [otys-vacancies-shortlist]
 *
 * @since 1.0.0
 */
 ?>
<div class="vacancies-list vacancies-shortlist">
    <?php
    if (!empty($args['vacancies'])) {
        foreach ($args['vacancies'] as $vacancy) {
            ?>

            <article class="vacancy vacancy-shortlist">
                <a href="<?php echo esc_url($vacancy['url']) ?>" class="vacancy-content">
                    <div class="vacancy-body">
                        <div class="vacancy-photo">
                            <?php
                            if ($vacancy['photoFileName']) {
                                ?>
                                <img src="<?php echo esc_attr($vacancy['photoUrl']) ?>" alt="Thumbnail" />
                                <?php
                            }
                            ?>
                        </div>
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
        <div class="owp-vacancies-list-text owp-vacancies-list-empty">
            <p><?php _e('No vacancies found', 'otys-jobs-apply'); ?></p>
        </div>
        <?php
    }
    ?>
</div>