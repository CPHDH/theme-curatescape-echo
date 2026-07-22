<?php
$title = html_escape(__('Browse Timelines'));
$total_results = count($timelines);
$head = array('bodyclass' => 'timelines primary', 'title' => $title);
echo head($head);
?>

<div id="content" role="main">
	<article class="browse stories items">
		<div class="browse-header">
			<h2 class="query-header"><?php
			$title .= (($total_results) ? ': <span class="item-number">'.$total_results.'</span>' : '');
			echo $title;
			?></h2>
		</div>
		<section id="results" class="">
			<?php if ($timelines) : ?>
				<?php foreach ($timelines as $timeline): ?>
					<article class="timeline item-result">
						<?php echo link_to($timeline, 'show', '<h3 class="title">'.$timeline->title.'</h3>', array('class'=>'permalink')); ?>
						<?php echo snippet(metadata($timeline, 'description'),0,200);?>
					</article>
				<?php endforeach; ?>
				<div class="pagination">
				<?php echo pagination_links(); ?>
				</div>
			<?php else: ?>
				<p><?php echo __('You have no timelines.'); ?></p>
			<?php endif; ?>
		</section>
	</article>
</div>

<?php echo foot(); ?>