<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'answers' => []
    ]
);

if (!empty($args['question']['answers'])) {
    foreach ($args['question']['answers'] as $answer) :
        ?>
        <label>
            <input
                type="checkbox" 
                value="<?php echo esc_attr($answer['answerUid']) ?>" 
                <?php echo isset($args['question']['name']) ? 'name="' . $args['question']['name'] . '"' : '' ?> <?php echo ((isset($args['question']['value']) && $args['question']['value'] === $answer['answerUid']) ? esc_attr('checked') : ''); ?>
                <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?>
            />
            <span><?php echo wp_kses_post($answer['answer']) ?></span>
        </label>
        <?php
    endforeach;
} else {
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
    </label>
    <?php
}