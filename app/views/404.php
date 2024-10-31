<?php
get_header();

if ($args['errors']) :
?>

    <main id="site-content" role="main" class="404">
        <div class="sw container-fluid">

            <?php
            foreach ($args['errors'] as $error) :
            ?>
                <div class="error <?php echo esc_attr($error->get_error_code()); ?>">
                    <h2 class="title"><?php echo esc_html($error->get_error_message()); ?></h2>
                    <?php echo wp_kses_post($error->get_error_data()); ?>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </main>

<?php endif;

get_footer();
?>