<?php
/**
 * Template used for vacancies apply confirm email page
 * 
 * @since 2.0.0
 */
?>

<div class="rest-form-page" data-type="confirm-email">
    <div class="form-intro">
        <p>
            <?php echo __('We sent a confirmation code to your email address. Please confirm your application by filling in the confirmation code.', 'otys-jobs-apply'); ?>
        </p>
    </div>

    <div class="form-input-wrapper">
        <div class="form-label-col">
            <label for="vacancy-form-email"><?php _e('Confirmation code', 'otys-jobs-apply'); ?></label>
        </div>

        <div class="form-input-col">
            <div class="input-wrap">
                <div class="input-field">
                    <input id="confirmationcode<?php echo esc_attr($args['identifier']); ?>" type="number" value="" name="confirmationcode" placeholder="<?php _e('Enter the confirmation code here', 'otys-jobs-apply') ; ?>" required/>
                </div>

                <div id="input-errors-confirmationcode<?php echo esc_attr($args['identifier']); ?>" class="input_errors"></div>
            </div>
        </div>
    </div>
</div>