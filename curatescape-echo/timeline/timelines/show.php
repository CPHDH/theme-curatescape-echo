<?php
echo head(array('bodyclass' => 'timelines primary','title' => metadata($timeline, 'title')));
?>
<div id="content" role="main" class="wide">
		<section class="inner-padding browse">
			<h2 class="query-header"><?php echo metadata($timeline, 'title'); ?></h2>
			<!-- Construct the timeline. -->
			<?php echo $this->partial('timelines/_timelinejs.php', array('items' => $items, 'timelinejs' => $timeline)); ?>
			<script>
			jQuery(document).ready(function($) {
					// Only fade out divs that exceed max slide height of 320px
					function fadeOut() {
						if ($(this).find('.tl-text-content').height() +
							$(this).find('.tl-headline-date').height() +
							$(this).find('.tl-headline').height() > 320) {
								$(this).addClass('truncateContentContainerAfter');
						}
					}
			
					// Use webkit-line-clamp and linear-gradient to truncate if selected (see timeline.css)
					<?php if (metadata($timeline, 'truncate')): ?>
						$('.tl-text').addClass('truncateText');
						$('.tl-media').addClass('truncateMedia');
						$('.tl-text-content-container').addClass('truncateContentContainer');
						$('.tl-headline a').addClass('truncateHeadlineLink');
						$('.tl-timeline p').addClass('truncateTimelineParagraph');
						// Iterate through slide text containers
						$('.tl-text-content-container').each(fadeOut);
						// Run after window load for Chrome to calculate heights correctly
						$(window).on('load', function () {
							$('.tl-text-content-container').each(fadeOut);
						});
					<?php endif; ?>
				});
			</script>
		</section>
</div> <!-- end content -->
<?php echo foot(); ?>
