<?php
    $currentAnswer = get_option($args['option_name'], '');

    $args = array_replace_recursive([
        'wp_media' => [
            'title' => __('Choose image', 'otys-jobs-apply'),
            'button_text' => __('Set image', 'otys-jobs-apply'),
            'preview_size' => 50
        ]
    ], $args);

    $image = wp_get_attachment_url($currentAnswer);
?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select image', 'otys-jobs-apply') ?></span>
    </legend>

    <div class="otys-cb-image-preview" data-otys-media-preview="<?php echo $args['option_name']; ?>" style="display: none">
        <img 
        data-otys-media-preview-image="<?= $args['option_name'] ?>"
        src="<?= $image ?>" style="max-width: <?= $args['wp_media']['preview_size']?>px" alt="image" />
    </div>

    <input type="hidden" name="<?php echo $args['option_name']; ?>" value="<?php echo $currentAnswer; ?>" />
    <input 
        class="button" 
        type="button"
        style="display: none;"
        value="<?php echo __('Choose image', 'otys-jobs-apply'); ?>" 
        data-otys-media="<?php echo $args['option_name']; ?>"
        data-otys-media-title="<?php echo $args['wp_media']['title']; ?>"
        data-otys-media-button-text="<?php echo $args['wp_media']['button_text']; ?>"
        />

    <input 
        type="button" 
        class="button"
        style="display: none;"
        data-otys-media-reset="<?php echo $args['option_name']; ?>" 
        value="<?php _e('Remove image', 'otys-jobs-apply'); ?>"
    />

    <?php
    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>