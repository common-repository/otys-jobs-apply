<?php
$required = (!isset($args['question']['value']) && $args['question']['validation']['mandatory']) ? esc_attr('required') : '';

if (isset($args['question']['name'])) :
    ?>

    <input id="<?php echo esc_attr($args['identifier']); ?>"  type="file" name="<?php echo esc_attr($args['question']['name']) ?>[]" multiple="multiple" <?php echo esc_attr($required) ?> />

    <?php
endif;