<?php 
if (isset($args['errors'])) {
    foreach ($args['errors'] as $validator => $error) : ?>
        <div class="input_error">
            <?php echo wp_kses_post($error); ?>
        </div>
    <?php
    endforeach; 
}