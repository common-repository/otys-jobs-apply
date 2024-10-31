<?php
/**
 * Template used for shortcode [otys-interactions] form.
 * 
 * @since 2.0.x
 */
?>

<div class="interactions-form">
    <form action="<?= esc_url($args['action']); ?>" method="POST" class="form_default rest-form"
        enctype="multipart/form-data" data-identifier="<?php echo esc_attr($args['identifier']); ?>"
        id="interactions-form-<?php echo esc_attr($args['identifier']); ?>" name="interactions-form">

        <div class="rest-form-container">
            <div class="rest-form-pages">
                <?php
                foreach ($args['pages'] as $page) {
                    ?>
                    <div class="rest-form-page <?php echo $page['pageNumber'] === 1 ? 'current' : '' ?>">
                        <?php
                        foreach ($page['fields'] as $question) {
                            ?>
                            <div id="<?php echo esc_attr($question['uid']); ?>" class="form-input-wrapper">
                                <div class="form-label-col">
                                  
                                    <label for="<?php echo esc_attr($question['uid']) . esc_attr($args['identifier']); ?>">
                                        <?php echo isset($question['question']) ? esc_html($question['question']) : '' ?>
                                        <?php echo ($question['validation']['mandatory']) ? '*' : '' ?>
                                    </label>
                           
                                </div>

                                <div class="form-input-col">
                                    <div class="input-wrap">
                                        <div class="input-field">
                                            <?php
                                            load_template(
                                                $question['template'],
                                                false,
                                                [
                                                    'question' => $question,
                                                    'identifier' => $question['uid'] . $args['identifier']
                                                ]
                                            );
                                            ?>
                                        </div>

                                        <?php
                                        if (isset($question['data']['explanation']) && $question['data']['explanation']) {
                                            echo '<div class="explanation ' . sanitize_title($question['data']['type'] . '-' . $question['data']['subtype']) . '">' . $question['data']['explanation'] . '</div>';
                                        }
                                        ?>

                                        <div id="input-errors-<?php echo esc_attr($question['name'] . esc_attr($args['identifier'])); ?>"
                                            class="input_errors"></div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>

            <input type="hidden" name="uid" value="<?php echo esc_attr($args['uid']) ?>" />
            <input type="hidden" name="redirect" value="<?php echo esc_attr($args['redirect']) ?>" />

            <div class="rest-form-footer-errors"></div>

            <div class="form-footer">
                <button type="button" class="button vacancy-apply-prev hide-button" formnovalidate="formnovalidate">
                    <?php echo __('Previous', 'otys-jobs-apply'); ?>
                </button>

                <button 
                    class="g-recaptcha button vacancy_apply_button hide-button"
                    data-sitekey="<?php echo esc_attr($args['recaptcha-key']) ?>"
                    data-callback="onSubmit<?php echo esc_attr($args['identifier']); ?>" data-action="submit">
                    <?php echo $args['submit_button_text'] ?>
                    <span class="form-loader"></span>
                </button>

                <button type="button" class="button vacancy-apply-next hide-button" formnovalidate="formnovalidate">
                    <?php echo __('Next', 'otys-jobs-apply'); ?>
                </button>
            </div>
        </div>

        <template class="rest-form-success">
            <?php load_template($args['success_template'], false, $args); ?>
        </template>

        <!-- used for errors thrown by rest call -->
        <template class="input-error-template">
            <div class="input_error"></div>
        </template>

        <script>
            function onSubmit<?php echo esc_attr($args['identifier']); ?>(token) {
                document.getElementById('interactions-form-<?php echo esc_attr($args['identifier']); ?>').dispatchEvent(new Event('submit', { cancelable: true }));
            }
        </script>
    </form>
</div>
