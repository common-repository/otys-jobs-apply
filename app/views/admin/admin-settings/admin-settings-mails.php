<?php
add_thickbox();
?>

<p>
    <?php
        echo __('Emails sent from the plugin are using documents from your OTYS enviroment.' ,'otys-jobs-apply');
    ?>
</p>

<p>
    <?php
        echo __('Below you see mails that get triggered when events happen. For each email that gets triggered you can choose which document to use. When choosing disabled the email will not be send.', 'otys-jobs-apply'); 
    ?>
</p>

<p>
    <?php
        echo __('If you as a user has rights to the document module in OTYS, you are able to edit these documents and also able to create new documents which can be used for emailing aswell.', 'otys-jobs-apply');
    ?>
</p>

<a id="cspd_yt_thickbox" href="https://www.youtube.com/embed/T3J7mv2suZk?autoplay=1&KeepThis=true&TB_iframe=true&width=600&height=550" class="thickbox" title="<?php echo __('Watch documents module instruction video', 'otys-jobs-apply'); ?>">
    <?php echo __('Watch documents module instruction video', 'otys-jobs-apply'); ?>
</a>