<?php
$bodyclass = 'page simple-page show';
echo head(array('maptype'=>'none','title' => html_escape(metadata('simple_pages_page', 'title')), 'bodyclass' => $bodyclass, 'bodyid' => html_escape(metadata('simple_pages_page', 'slug')))); ?>

<div id="content" role="main">
    <article class="page show">
        <h2 class="page_title"><?php echo metadata('simple_pages_page', 'title'); ?></h2>

        <?php
        $text = metadata('simple_pages_page', 'text', array('no_escape' => true));
        echo $this->shortcodes($text);
        ?>
        
    </article>

</div> <!-- end content -->

<?php echo foot(); ?>
