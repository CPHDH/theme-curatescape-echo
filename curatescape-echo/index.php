<?php
$stealthMode=(get_theme_option('stealth_mode')==1)&&(is_allowed('Items', 'edit')!==true) ? true : false;
$classname='home'.($stealthMode ? ' stealth' : null);
echo head(
    array(
    'maptype'=>'focusarea',
    'bodyid'=>'home',
    'bodyclass'=>$classname)
); ?>

<div id="content" role="main">
    <article id="homepage" class="page show">
        <?php echo homepage_widget_sections();?>
    </article>
</div>

<?php echo foot(); ?>