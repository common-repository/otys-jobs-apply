<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'type' => 'text'
    ]
);

foreach ($args['question']['data']['PossibleAnswersExt'] as $answer) :
    ?>
    <label>
        <input type="radio" value="<?php echo esc_attr($answer['answerUid']) ?>" name="<?php echo esc_attr($args['question']['name']) ?>" <?php echo ((isset($args['question']['value']) && $args['question']['value'] === $answer['answerUid']) ? esc_attr('checked') : ''); ?> <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?> />
        <span>
            <?php echo is_array($answer['answer']) ? wp_kses_post($answer['answer']['name']) : wp_kses_post($answer['answer']) ?>
        </span>
    </label>
    <?php
endforeach;
