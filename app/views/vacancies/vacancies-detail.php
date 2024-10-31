<?php
/**
 * Template used for Vacancy Detail
 *
 * @since 1.0.0
 */
get_header();

?>

<div id="site-content" role="main" class="site-width">
    <div class="sw container-fluid">
        <article id="vacancy-article-detail">
            <header class="vacancy-header">
                <div class="vacancy-header-slider">
                    <div class="vacancy-header-track" data-glide-el="track">
                        <div class="vacancy-header-slides" >
                            <?php
                                if ($args['vacancy']['Video'] !== NULL  && $args['vacancy']['Video']['videoPosition'] === 'top') {
                                    ?>
                                    <div class="vacancy-header-slide">
                                        <?php echo $args['vacancy']['Video']['videoEmbed']; ?>
                                    </div>
                                <?php
                                }
                            ?>
                            <?php
                            if (!empty($args['vacancy']['PhotoGallery'])) {
                                foreach ($args['vacancy']['PhotoGallery'] as $slide) {
                                    ?>
                                    <div class="vacancy-header-slide" style="background-image:url(<?php echo esc_url($slide['photoUrl']) ?>)">
                                        <img src="<?php echo esc_attr(OTYS_PLUGIN_ASSETS_URL) ?>/images/vacancies/vacancy-detail-banner-ratio.png" width="1200" height="300" alt="ratio pixel" class="vacancy-header-ratio" />
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="vacancy-header-slide" style="background-image:url(<?php echo esc_url($args['vacancy']['vacancyFallbackImage']) ?>">
                                    <img src="<?php echo esc_attr(OTYS_PLUGIN_ASSETS_URL) ?>/images/vacancies/vacancy-detail-banner-ratio.png" width="1200" height="300" alt="ratio pixel" class="vacancy-header-ratio" />
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="vacancy-header-details vacancy-spacing">
                    <div class="vacancy-header-relation">
                        <div class="vacancy-header-relation-brand">
                            <?php
                            if ($args['vacancy']['photoFileName'] !== NULL) {
                                ?>
                                <div class="vacancy-relation-logo" style="background-image: url(<?php echo esc_url($args['vacancy']['photoUrl']) ?>)"></div>
                                <?php
                            } ?>

                            <?php
                            if ($args['vacancy']['showEmployer'] === true) {
                                ?>
                                <div class="vacancy-relation-name"><?php echo esc_html($args['vacancy']['relation']) ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                    <h1 class="vacancy-title">
                        <?php echo esc_html($args['vacancy']['title']) ?>
                    </h1>

                    <div class="vacancy-info-footer">
                        <div class="vacancy-info-footer-left">
                            <div class="vacancy-criteria owp-labels">
                                <?php 
                                foreach ($args['vacancy']['labels'] as $label) {
                                    foreach ($label['values'] as $value) {
                                        ?>
                                        <div class="vacancy-criteria-option owp-label" title="<?php echo esc_html($label['name'])?>"><?php echo esc_html($value) ?></div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="vacancy-info-footer-right">
                            <div class="vacancy-date">
                                <?php echo esc_html(human_time_diff(strtotime($args['vacancy']['entryDateTime']), current_time('timestamp', 1))); ?> <?php echo __('ago', 'otys-jobs-apply') ?>
                            </div>
                            <div id="vacancy-header-actions">
                                <?php
                                if ($args['vacancy']['removeApplyButton'] !== true) {
                                    ?>
                                    <a id="vacancy-head-apply" class="button" href="<?php echo esc_url($args['vacancy']['applyUrl']); ?>"><?php _e('Apply', 'otys-jobs-apply'); ?></a>
                                    <?php
                                    } 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <section id="vacancy-article-detail-content" class="vacancy-spacing">
                <?php
                    foreach ($args['vacancy']['textFields'] as $textfieldType => $textfield) {
                        if (!empty($textfield['title']) && !empty($textfield['text'])) {
                        ?>
                            <h3 class="owp-heading-3 <?php echo 'owp-heading-'. esc_attr($textfieldType); ?>"><?php echo esc_html($textfield['title']) ?></h3>

                            <div class="vacancy-item-text text <?php echo 'vacancy-item-text-'. esc_attr($textfieldType); ?>">
                                <?php echo wp_kses_post($textfield['text']); ?>
                            </div>
                        <?php
                        }
                    }
                ?>

                <?php
                    if ($args['vacancy']['Video'] !== NULL  && $args['vacancy']['Video']['videoPosition'] === 'bottom') {
                        ?>
                        <div class="vacancy-item vacancy-video">
                            <?php echo $args['vacancy']['Video']['videoEmbed']; ?>
                        </div>
                    <?php
                    }
                ?>
                <div id="vacancy-detail-footer">
                    <?php
                    if ($args['vacancy']['removeApplyButton'] !== true) {
                        ?>
                        <a class="button" href="<?php echo esc_url($args['vacancy']['applyUrl']); ?>"><?php _e('Apply', 'otys-jobs-apply'); ?></a>
                        <?php
                    }
                    ?>
                </div>
            </section>
        </article>
    </div>
</div>

<?php
get_footer();
