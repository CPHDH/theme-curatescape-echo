<?php
    $fileTitle = metadata('file', array('Dublin Core', 'Title')) ? strip_formatting(metadata('file', array('Dublin Core', 'Title'))) : __('Untitled');

    echo head(array('file'=>$file, 'maptype'=>'none','bodyid'=>'file','bodyclass'=>'show item-file','title' => $fileTitle ));
?>
<div id="content" role="main">

    <article class="page file show">

        <header id="file-header">
            <h2 class="item-title"><?php echo $fileTitle; ?></h2>
            <?php
        $info = array();

        ($fileid=metadata('file', 'id')) ? $info[]='<span class="file-id">ID: '.$fileid.'</span>' : null;

        ($source=metadata('file', array('Dublin Core','Source'))) ? $info[] = '<span class="file-source">'.__('Source').': '.$source.'</span>' : null;
        ($creators=metadata('file', array('Dublin Core','Creator'), true)) ? $info[] = '<span class="file-creator">'.__('Creator').': '.implode(', ', $creators).'</span>' : null;
        ($date=metadata('file', array('Dublin Core','Date'), true)) ? $info[] = '<span class="file-date">'.__('Date').': '.implode(', ', $date).'</span>' : null;

        echo count($info) ? '<span id="file-header-info" class="story-meta byline">'.implode(" ~ ", $info).'</span>' : null;

        ?>
        </header>

        <div id="item-primary" class="show">
            <hr>
            <?php
        $record=get_record_by_id('Item', $file->item_id);
        $title=metadata($record, array('Dublin Core','Title'));
        echo __('This file appears in').': '.link_to_item(strip_tags($title), array('class'=>'file-appears-in-item'), 'show', $record);
        ?>
            <hr>

            <figure>
                <?php echo rl_single_file_show($file); ?>
                <?php if ($rights = metadata('file', array('Dublin Core','Rights'))) {
            echo '<figcaption class="rights-caption">'.$rights.'</figcaption>';
        }?>
            </figure>

            <div id="key-file-metadata">
                <?php
        echo ($desc=metadata('file', array('Dublin Core','Description'))) ? '<p class="file-desc">'.$desc.'</p>' : null;
        ?>
            </div>

            <div class="additional_file_metadata">
                <?php rl_file_metadata_additional();?>
            </div>

            <hr>
            <?php echo __('This file appears in').': '.link_to_item(strip_tags($title), array('class'=>'file-appears-in-item'), 'show', $record);?>
            <hr>
            <?php echo rl_hero_item($record);?>

        </div><!-- end primary -->

    </article>

</div> <!-- end content -->


<?php echo foot(); ?>