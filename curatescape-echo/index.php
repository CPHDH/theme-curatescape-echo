<?php
$stealthMode=(get_theme_option('stealth_mode')==1)&&(is_allowed('Items', 'edit')!==true) ? true : false;
$classname='home'.($stealthMode ? ' stealth' : null);
echo head(
    array(
    'maptype'=>'focusarea',
    'bodyid'=>'home',
    'bodyclass'=>$classname)
); ?>

<div id="content" role="main" class="wide">
    <?php echo rl_homepage_featured();?>
    <?php echo rl_homepage_recent_random();?>
    <?php echo rl_homepage_tours();?>
    <?php echo rl_homepage_tags();?>
    <?php echo rl_homepage_about();?>
    <?php echo rl_homepage_cta();?>
</div>

<?php echo foot(); ?>