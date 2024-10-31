<?php
/**
 * Template used for shortcode [otys-vacancies-apply] form.
 * 
 * @since 2.0.0
 */

//  var_dump($args);
?>

<div class="vacancy-form">
    <form 
        action="<?= esc_url($args['action']); ?>" 
        method="POST" class="form_default rest-form"
        enctype="multipart/form-data"
        data-identifier="<?php echo esc_attr($args['identifier']); ?>"
        id="application-form-<?php echo esc_attr($args['identifier']); ?>" 
        name="application-form">

        <div class="rest-form-container">
            <div class="rest-form-pages">
                <?php
                foreach ($args['questionset']['pages'] as $page_number => $page) {
                    ?>
                    <div class="rest-form-page <?php echo $page_number === 1 ? 'current' : '' ?>">
                        <?php
                        if ($page_number === 1 && $args['show_candidate_login']) {
                            ?>
                            <div class="vacancy-form-actions">
                                <a href="<?php echo $args['candidate_login_url']; ?>">
                                    <?php echo __('Already have an account? Login here.', 'otys-jobs-apply'); ?>
                                </a>
                            </div>
                            <?php
                        }

                        foreach ($page['questions'] as $question) {
                            ?>
                            <div id="<?php echo esc_attr($question['fieldNameId']); ?>" class="form-input-wrapper">
                                <div class="form-label-col">
                                    <?php if ($question['showQuestion']) { ?>
                                        <label for="<?php echo esc_attr($question['name']) . esc_attr($args['identifier']); ?>">
                                            <?php echo isset($question['question']) ? esc_html($question['question']) : '' ?>
                                            <?php echo ($question['validation']['mandatory']) ? '*' : '' ?>
                                        </label>
                                    <?php } ?>
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
                                                    'uid' => $args['questionset']['uid'],
                                                    'vacancy' => $args['vacancy'],
                                                    'identifier' => $question['name'] . $args['identifier']
                                                ]
                                            );
                                            ?>
                                        </div>

                                        <?php
                                        if (isset($question['data']['explanation']) && $question['data']['explanation']) {
                                            echo '<div class="explanation ' . sanitize_title($question['data']['type'] . '-' . $question['data']['subtype']) . '">' . $question['data']['explanation'] . '</div>';
                                        }
                                        ?>

                                        <div id="input-errors-<?php echo esc_attr($question['name'] . esc_attr($args['identifier'])); ?>" class="input_errors"></div>
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

            <input type="hidden" name="vacancy_uid" value="<?php echo esc_attr($args['vacancy_uid']); ?>" />
            <input type="hidden" name="redirect" value="<?php echo esc_attr($args['redirect']) ?>" />

            <div class="rest-form-footer-errors"></div>

            <div class="form-footer">
                <button type="button" class="button vacancy-apply-prev hide-button" formnovalidate="formnovalidate">
                    <?php echo __('Previous', 'otys-jobs-apply'); ?>
                </button>

                <button class="g-recaptcha button vacancy_apply_button hide-button"
                    data-sitekey="<?php echo esc_attr($args['recaptcha-key']) ?>"
                    data-callback="onSubmit<?php echo esc_attr($args['identifier']); ?>" data-action="submit">
                    <?php echo __('Apply', 'otys-jobs-apply'); ?>
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

        <template class="rest-form-confirm-email">
            <?php load_template($args['confirm_email_template'], false, $args); ?>
        </template>

        <!-- used for errors thrown by rest call -->
        <template class="input-error-template">
            <div class="input_error"></div>
        </template>
    </form>
</div>

<script>
    function onSubmit<?php echo esc_attr($args['identifier']); ?>(token) {
        document.getElementById('application-form-<?php echo esc_attr($args['identifier']); ?>').dispatchEvent(new Event('submit', { cancelable: true }));
    }
</script>