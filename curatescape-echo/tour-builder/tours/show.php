<?php
$maptype='tour';
$tourTitle = strip_formatting(tour('title'));
$label = rl_tour_label('singular');
if ($tourTitle != '' && $tourTitle != '[Untitled]') {
} else {
    $tourTitle = '';
}
echo head(array( 'maptype'=>$maptype,'title' => ''.$label.' | '.$tourTitle, 'content_class' => 'horizontal-nav', 'bodyid'=>'tours',
    'bodyclass' => 'show tour', 'tour'=>$tour ));
?>
<div id="content" class="wide" role="main">

    <article id="tour-content" class="tour show">

        <header id="tour-header">
            <div class="max-content-width inner-padding">
                <h1 class="tour-title title"><?php echo $tourTitle; ?></h1>
                <?php echo '<div class="byline">'.__('%s Locations', rl_tour_total_items($tour)).' | '.((tour('Credits')) ? __('Curated by %s', tour('Credits')) : __('Curated by %s', option('site_title'))).'</div>';?>
            </div>
        </header>

        <div class="max-content-width">
            <div class="separator thin wide"></div>
            <aside id="social-actions" class="inner-padding-flush">
                <?php echo rl_article_actions(strip_tags($tourTitle),rl_tour_label());?>
            </aside>
        </div>
        <div class="separator wide thin"></div>

        <section id="text" aria-label="<?php echo __('%s Description', rl_tour_label('singular'));?>">
            <div class="max-content-width inner-padding">
                <?php echo htmlspecialchars_decode(nls2p(tour('Description'))); ?>
            </div>
        </section>

        <div class="max-content-width inner-padding">
            <div class="separator flush-top center"></div>
            <section id="tour-items" class="browse" aria-label="<?php echo __('%s Locations', rl_tour_label('singular'));?>">
                <?php $i=1;
                foreach ($tour->getItems() as $tourItem):
                    if ($tourItem->public || current_user()):
                        set_current_record('item', $tourItem);
                        $url=url('/items/show/'.$tourItem->id.'?tour='.tour('id').'&index='.($i-1).'');
                        $custom = method_exists($tour,'getTourItem') ? $tour->getTourItem($tourItem->id) : null;
                        $itemLede = rl_the_lede($tourItem);
                        if($custom && isset($custom['text']) && $custom['text']){
                            // the custom tour item Description
                            $itemText = $custom['text'];
                        }elseif(($itemLede) && (strlen($itemLede) > 150)) {
                            // the Lede if it's not too short, up to 400 chars
                            $itemText = snippet($itemLede, 0, 400, '&hellip;');
                        } else {
                            // an excerpt of the Story
                            $itemText = snippet(rl_the_text($tourItem), 0, 300, '&hellip;');
                        }
                        $itemText .= '<br><a class="readmore" href="'.$url.'">'.__('View %s', rl_item_label('singular')).'</a> <span class="sep-bar" aria-hidden="true">&mdash;</span> <a role="button" data-index="'.$i.'" data-id="'.$tourItem->id.'" class="readmore showonmap" href="javascript:void(0)">'.__('Show on Map').'</a>';
                        ?>
                        <article class="item-result tour">
                            <a aria-label="<?php echo strip_tags(metadata($tourItem, array('Dublin Core', 'Title'))); ?>" class="tour-image single" style="background-image:url(<?php echo rl_get_first_image_src($tourItem, 'square_thumbnails');?>)" href="<?php echo $url;?>"></a>
                            <div class="separator thin flush-bottom flush-top"></div>
                            <div class="tour-inner">
                                <a class="permalink" href="<?php echo $url; ?>">
                                    <h2 class="h3 title"><?php echo strip_tags(metadata($tourItem, array('Dublin Core', 'Title'))); ?></h2>
                                    <?php echo ($custom && isset($custom['subtitle']) && $custom['subtitle']) ? '<p class="subtitle">'.$custom['subtitle'].'</p>' : rl_the_subtitle($tourItem);?>
                                </a>
                                <p class="item-description tour-snip">
                                    <?php echo $itemText; ?>
                                </p>
                            </div>
                        </article>
                        <?php $i++; $item_image=null;
                    endif;
                endforeach; ?>
            </section>

            <div class="separator center"></div>

            <?php if ($ps = metadata('tour', 'Postscript Text')):?>
            <section id="tour-postscript" class="max-content-width inner-padding caption" aria-label="<?php echo __('%s Postscript', rl_tour_label('singular'));?>">
                <p><?php echo htmlspecialchars_decode(metadata('tour', 'Postscript Text')); ?></p>
            </section>
            <?php endif;?>

        </div>

        <?php if (get_theme_option('comments_id')):?>
        <section id="comments-section">
            <?php echo rl_display_comments();?>
        </section>
        <?php endif;?>

        <?php echo multimap_markup(true, $tourTitle, __('Show %s Map', rl_tour_label()));?>

    </article>
</div> <!-- end content -->

<?php echo foot(); ?>