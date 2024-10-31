<?php
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
                <input 
                    type="radio" 
                    value="<?php echo esc_attr($key) ?>" 
                    name="<?php echo esc_attr($args['option_name']) ?>" <?php echo ($currentAnswer === $key) ? esc_attr('checked') : '' ?> />

                <?php echo $option ?>
            </label><br>
            <?php
        endforeach;
    endif;

    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>