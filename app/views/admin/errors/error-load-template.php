<?php if (is_wp_error($error)) : ?>
<div class="error">
    <h2 class="title"><?php echo esc_attr($error->get_error_code()); ?></h2>
    <?php
    foreach ($error->get_error_messages() as $message) {
        echo '<p>' . wp_kses_post($message) . '</p>';
    }
    ?>
</div>
<?php endif; ?>