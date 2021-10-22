<?php
$tag = (isset($_GET['tag']) ? $_GET['tag'] : null);
$tags = (isset($_GET['tags']) ? $_GET['tags'] : null);
$term = (isset($_GET['term']) ? $_GET['term'] : null);
$query = (isset($_GET['search']) ? $_GET['search'] : null);
$advanced = (isset($_GET['advanced']) ? true : false);
$bodyclass='browse';
$maptype='focusarea';

if ((isset($tag) || isset($tags)) && !isset($query)) {
    $title = __('Exhibits tagged "%s"', ($tag ? $tag : $tags));
    $bodyclass .=' queryresults';
    $maptype='queryresults';
} elseif (isset($term) && !isset($query)) {
    $title = __('Results for subject term "%s"', $term);
    $bodyclass .=' queryresults';
    $maptype='queryresults';
} elseif (isset($query)) {
    $title = (!($advanced) ? __('Search Results for "%s"', $query) : __('Advanced Search Results'));
    $bodyclass .=' queryresults';
    $maptype='queryresults';
} elseif (isset($_GET['featured']) && $_GET['featured'] == 1) {
    $title = __('Featured Exhibits');
    $bodyclass .=' queryresults';
    $maptype='queryresults';
} else {
    $title = __('All Exhibits');
    $bodyclass .=' exhibits stories';
}
echo head(array('maptype'=>'none','title'=>$title,'bodyid'=>'exhibits','bodyclass'=>$bodyclass));
?>


<div id="content" role="main">

    <article class="browse stories items">
        <div class="browse-header">
            <h2 class="query-header"><?php
        $title .= (($total_results) ? ': <span class="item-number">'.$total_results.'</span>' : '');
        echo $title;
        ?></h2>
            <nav class="secondary-nav" id="item-browse">
                <?php echo nav(array(
                array(
                    'label' => __('All'),
                    'uri' => url('exhibits/browse')
                ),
                array(
                    'label' => __('Featured'),
                    'uri' => url('exhibits/browse?featured=1')
                ),
                array(
                    'label' => __('Tags'),
                    'uri' => url('exhibits/tags')
                )
            )); ?>
            </nav>

            <div id="helper-links">
                <span class="helper-label"><?php echo rl_icon('funnel').'&nbsp;'.__("Sort by: "); ?>
                </span>
                <?php echo browse_sort_links(array('Title'=>'title','Date Added'=>'added')); ?>
            </div>
        </div>

        <section id="results" class="">

            <?php if (count($exhibits) > 0): ?>

            <div id="exhibits">
                <?php $exhibitCount = 0; ?>
                <?php foreach (loop('exhibit') as $exhibit): ?>
                <?php $exhibitCount++; ?>
                <div class="exhibit <?php if ($exhibitCount%2==1) {
                echo ' even';
            } else {
                echo ' odd';
            } ?>">
                    <?php echo link_to_exhibit('<h3 class="title">'.metadata('exhibit', 'title').'</h3>', array('class'=>'permalink')); ?>
                    <?php if ($exhibitDescription = metadata('exhibit', 'description', array('no_escape' => true))): ?>
                    <div class="description"><?php echo strip_tags($exhibitDescription); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <p><?php echo __('There are no exhibits available yet.'); ?></p>
            <?php endif; ?>

            <div class="pagination"><?php echo pagination_links(); ?></div>

        </section>

    </article>
</div> <!-- end content -->



<?php echo foot(); ?>