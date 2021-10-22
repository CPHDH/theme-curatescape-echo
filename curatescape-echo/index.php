<?php 
$stealthMode=(get_theme_option('stealth_mode')==1)&&(is_allowed('Items', 'edit')!==true) ? true : false;
$classname='home'.($stealthMode ? ' stealth' : null);
echo head(array(
	'maptype'=>'focusarea',
	'bodyid'=>'home',
	'bodyclass'=>$classname)
); ?>

<div id="content" role="main">
	<!-- Homepage Content -->
	<section class="map">
		<h2 hidden class="hidden"><?php echo __('Map');?></h2>
		<nav aria-label="<?php echo __('Skip Interactive Map');?>"><a id="skip-map" href="#homepage"><?php echo __('Skip Interactive Map');?></a></nav>
		<figure>
			<?php echo rl_map_type('focusarea',null,null); ?>
		</figure>
	</section>	
	<article id="homepage" class="page show">
		<?php echo homepage_widget_sections();?>
	</article>
</div> 

<?php echo foot(); ?>