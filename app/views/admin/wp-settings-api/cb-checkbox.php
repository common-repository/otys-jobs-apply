<?php

$args = wp_parse_args($args, [
    'options' => []
]);

$currentAnswer = intval(get_option($args['option_name'], 0));

?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select option', 'otys-jobs-apply') ?></span>
    </legend>
    <?php
    if (!empty($args['options'])) :
        foreach ($args['options'] as $key => $option) :
            ?>
            <label>
                <input type="checkbox" value="<?php echo esc_html($key) ?>" name="<?php echo esc_html($args['option_name']) ?>[]" <?php echo ($currentAnswer === $key) ? 'checked' : '' ?> />

                <?php echo esc_html($option) ?>
            </label><br>
            <?php
        endforeach;
    endif;

    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>