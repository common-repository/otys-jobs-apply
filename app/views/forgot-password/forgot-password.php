
<div class="forgot-password-form">
    <form action="<?php echo esc_url($args['action']); ?>" name="otys-forgot-password-form" method="POST" class="rest-form" id="login-form-<?php echo esc_attr($args['identifier']); ?>">
        <div class="rest-form-container">
            <div class="rest-form-pages">
                <div class="rest-form-page current">
                    <?php foreach ($args['questions'] as $question) { ?>
                        <div id="<?php echo esc_attr('otys-login-username'); ?>" class="form-input-wrapper">
                            <div class="form-label-col">
                                <div class="form-label">
                                    <label for="<?php echo esc_attr($question['uid'] . $args['identifier']); ?>">
                                        <?php echo esc_html($question['question']); ?>
                                    </label>
                                </div>
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

                                    <div id="input-errors-<?php echo esc_attr($question['name'] . esc_attr($args['identifier'])); ?>"
                                        class="input_errors"></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="redirect" value="<?php echo esc_attr($args['redirect']) ?>" />

        <div class="rest-form-footer-errors"></div>

        <div class="form-footer">
            <button 
                class="g-recaptcha button vacancy_apply_button"
                data-sitekey="<?php echo esc_attr($args['recaptcha-key']) ?>"
                data-callback="onSubmit<?php echo esc_attr($args['identifier']); ?>" data-action="submit">
                <?php echo $args['submit_button_text'] ?>
                <span class="form-loader"></span>
            </button>
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
                document.getElementById('login-form-<?php echo esc_attr($args['identifier']); ?>').dispatchEvent(new Event('submit', { cancelable: true }));
            }
        </script>
    </form>
</div>