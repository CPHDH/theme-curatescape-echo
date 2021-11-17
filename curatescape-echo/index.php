<?php
$stealthMode=(get_theme_option('stealth_mode')==1)&&(is_allowed('Items', 'edit')!==true) ? true : false;
$classname='home'.($stealthMode ? ' stealth' : null);
echo head(
    array(
    'bodyid'=>'home',
    'bodyclass'=>$classname)
); ?>

<div id="content" role="main" class="wide">
    
    <?php 
    echo rl_homepage_featured();
    echo rl_homepage_recent_random();
    echo rl_homepage_tours();
    echo rl_homepage_tags();
    echo rl_homepage_about();
    echo rl_homepage_cta();         
    ?>
    
</div>

<?php echo foot(); ?>