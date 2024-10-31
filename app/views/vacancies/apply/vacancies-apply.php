<?php
/**
 * Template used for vacancies apply page
 * 
 * @since 2.0.0
 */

get_header();

?>

<main id="site-content" role="main">
    <div class="site-width">
        <article id="vacancy-apply">
            <header class="vacancy-header">
                <div id="vacancy-apply-header" class="vacancy-spacing">
                    <div class="vacancy-apply-left">
                        <div class="vacancy-head-apply-text">
                            <?php _e('You are currently applying for', 'otys-jobs-apply'); ?>
                        </div>
                        <h1 class="vacancy-title"><?php echo esc_html($args['vacancy']['title']) ?></h1>
                    </div>
                </div>
            </header>

            <section id="vacancy-form" class="vacancy-spacing">
                <?= do_shortcode('[otys-vacancies-apply vacancy-uid="'. $args['uid'] .'"]'); ?>
            </section>
        </article>
    </div>
</main>

<?php
get_footer();