<?php

$settingValue = get_option($args['option_name']);

?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select options', 'otys-jobs-apply') ?></span>
    </legend>

    <div class="otys-sortables">
    <?php
    if ($args['options']) :
        foreach ($args['options'] as $fieldId => $option) :
            $id = $fieldId;
            $value = filter_var(isset($settingValue[$id]['show']) ? $settingValue[$id]['show'] : false, FILTER_VALIDATE_BOOLEAN);

            if (!is_array($option)) {
                continue;
            }
            ?>
            <div class="otys-sortable">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M32 96C32 78.33 46.33 64 64 64C81.67 64 96 78.33 96 96C96 113.7 81.67 128 64 128C46.33 128 32 113.7 32 96zM32 256C32 238.3 46.33 224 64 224C81.67 224 96 238.3 96 256C96 273.7 81.67 288 64 288C46.33 288 32 273.7 32 256zM96 416C96 433.7 81.67 448 64 448C46.33 448 32 433.7 32 416C32 398.3 46.33 384 64 384C81.67 384 96 398.3 96 416zM160 96C160 78.33 174.3 64 192 64C209.7 64 224 78.33 224 96C224 113.7 209.7 128 192 128C174.3 128 160 113.7 160 96zM224 256C224 273.7 209.7 288 192 288C174.3 288 160 273.7 160 256C160 238.3 174.3 224 192 224C209.7 224 224 238.3 224 256zM160 416C160 398.3 174.3 384 192 384C209.7 384 224 398.3 224 416C224 433.7 209.7 448 192 448C174.3 448 160 433.7 160 416z"/></svg>
                
                <input type="hidden" name="<?php echo esc_attr($args['option_name']) ?>[<?php echo $id; ?>][show]" value="false" />
                
                <input type="text" name="<?php echo esc_attr($args['option_name']) ?>[<?php echo $id; ?>][question]" value="<?php echo esc_html($option['question']) ?>" />
                
                <div>
                    <input id="<?php echo esc_attr($args['option_name']) ?>-<?php echo $id; ?>" 
                        type="checkbox"
                        value="true"
                        name="<?php echo esc_attr($args['option_name']) ?>[<?php echo $id; ?>][show]" 
                        <?php echo $value || ($option['locked'] && $option['default']) ? esc_attr('checked') : '' ?>
                        <?= ($option['locked'] && ($value === $option['default'])) ? 'disabled' : '' ?>
                    />

                    <label 
                        for="<?php echo esc_attr($args['option_name']) ?>-<?php echo $id; ?>" 
                        
                        <?= ($option['locked'] && ($value === $option['default'])) ? 'title="'. $option['title'] .'"' : '' ?>
                        <?= ($option['locked'] && ($value === $option['default'])) ? 'disabled' : '' ?>
                    >
                    Toggle</label>
                </div>

            </div>
            <?php
        endforeach;
    endif;
    ?>
    </div>

    <?php
    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>

    <div id="match-criteria-error" class="otys-error-message" style="display: none;"><?= __('Please select at least one match criteria', 'otys-jobs-apply') ?></div>
</fieldset>