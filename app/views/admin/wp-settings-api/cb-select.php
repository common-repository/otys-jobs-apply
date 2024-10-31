<?php

$currentAnswer = get_option($args['option_name']);

?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select option', 'otys-jobs-apply') ?></span>
    </legend>

    <select name="<?php echo $args['option_name']; ?>">
    <?php
    if ($args['options']) :
        foreach ($args['options'] as $key => $option) :
            ?>
            
            <option <?php echo ($currentAnswer == $key) ? esc_attr('selected') : '' ?> value="<?php echo $key; ?>"><?php echo $option; ?></option>
                
            <?php
        endforeach;
    endif;
    ?>
    </select>
    <?php
    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>