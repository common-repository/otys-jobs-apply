
<main id="site-content" role="main">
    <div class="sw container-fluid">

        <?php
        if (isset($args) && property_exists($args, 'errors')) :
            foreach ($args->errors as $errorCode => $error) :
                ?>
                <div class="error <?php echo esc_attr($errorCode) ?>">
                    <h2 class="title"><?php echo esc_html($args->get_error_message($errorCode)); ?></h2>
                    <?php echo wp_kses_post($args->get_error_data($errorCode)); ?>
                </div>
                <?php
            endforeach;
        endif;
        ?>
    </div>
</main>
