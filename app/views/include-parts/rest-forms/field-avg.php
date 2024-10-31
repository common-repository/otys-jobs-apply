<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'answers' => []
    ]
);
?>

<label>
    <input 
        type="hidden" 
        value="false"
        name="<?php echo esc_attr($args['question']['name']) ?>" 
    />
    <input 
        id="<?php echo esc_attr($args['identifier']); ?>" 
        type="checkbox" 
        value="true"
        name="<?php echo esc_attr($args['question']['name']) ?>" 
        <?php echo ((isset($args['question']['value']) && $args['question']['value'] == true) ? esc_attr('checked') : ''); ?> 
        <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?> 
    />

    <span>
        <?php echo $args['question']['data']['name']; ?>
    </span>
</label>