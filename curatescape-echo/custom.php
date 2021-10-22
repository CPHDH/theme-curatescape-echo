<?php
/*
** Fallback images
*/
add_file_fallback_image('audio', 'ionicons/headset-sharp.svg');
add_file_fallback_image('video', 'ionicons/film-sharp.svg');
add_file_fallback_image('application', 'ionicons/document-text-sharp.svg');
add_file_fallback_image('default', 'ionicons/document-sharp.svg');

/*
** Relabel Search Record Types
*/
add_filter('search_record_types', 'rl_search_record_types');
function rl_search_record_types($recordTypes)
{
    if (plugin_is_active('SimplePages')) {
        $recordTypes['SimplePagesPage'] = __('Page');
    }
    $recordTypes['Item'] = rl_item_label('singular');
    if (plugin_is_active('TourBuilder', '1.6', '>=')) {
        $recordTypes['Tour'] = rl_tour_label('singular');
    }
    return $recordTypes;
}

/*
** Admin Messages
** Set $roles to limit visibility
*/
function rl_admin_message($which=null, $roles=array('admin','super','contributor','researcher','editor','author'))
{
    if ($user=current_user()) {
        if (in_array($user['role'], $roles)) {
            switch ($which) {
                case 'items-browse':
                    if (intval(option('per_page_public')) % 6 > 0) {
                        $title = '<strong>'.__('Admin Notice').'</strong> ';
                        $ps = __('This message is only visible to site admins.');
                        return '<div class="warning message">'.rl_icon('warning').$title.': '.__('To ensure the optimal user experience at all screen sizes, please <a href="%s">update your site settings</a> so that the value of <em>Results Per Page (Public)</em> is a number divisible by both 2 and 3 (for example, 12 or 18).', admin_url('appearance/edit-settings')).' '.$ps.'</div>';
                    }
                break;

                default:
                    return null;
            }
        }
    }
}

/*
** Set Default Search Record Types
*/
add_filter('search_form_default_record_types', 'rl_search_form_default_record_types');
function rl_search_form_default_record_types()
{
    $recordTypes=array();
    $recordTypes[]='Item';
    if (plugin_is_active('TourBuilder', '1.6', '>=') && get_theme_option('default_tour_search')) {
        $recordTypes[]='Tour';
    }
    if (plugin_is_active('SimplePages') && get_theme_option('default_page_search')) {
        $recordTypes[]='SimplePagesPage';
    }
    if (get_theme_option('default_file_search')) {
        $recordTypes[]='File';
    }
    return $recordTypes;
}


/*
** Sitewide Search Results
*/
function rl_search_results($records=array(), $html=null)
{
    $filter = new Zend_Filter_Word_CamelCaseToDash();
    $html .= '<div id="result-cards">';
    foreach ($records as $searchText) {
        $type=$searchText['record_type'];
        $class=strtolower($filter->filter($searchText['record_type']));
        $html .= rl_search_result_card($searchText, $type, $class);
    }
    $html .= '</div>';
    return $html;
}
/*
** Sitewide Search Result Card
*/
function rl_search_result_card($searchText=null, $type=null, $class=null)
{
    $record = get_record_by_id($type, $searchText['record_id']);
    $mime= ($type=="File") ? metadata($record, 'MIME Type') : null;
    $mime_label = ($mime) ? ' / '.rl_clean_mime($mime) : null;
    set_current_record($type, $record);
    $html = '<article class="result-card '.$class.'">';
    $icon = rl_icon_name_by_type($type, $mime);
    $html .= '<div class="card-inner">';
    $html .= '<span class="card-label" aria-label="'.__('Result Type').'">'.rl_icon($icon).rl_relabel_type($type).$mime_label.'</span>';
    $html .= '<div class="card-detail">';
    $html .= '<a class="permalink" href="'.record_url($record, 'show').'"><h3 class="title">'.($searchText['title'] ? $searchText['title'] : '[Untitled]').'</h3></a>';
    $description = snippet(rl_search_text($type, $record), 0, 120, '&hellip;');
    $sub = rl_subhead_by_type($type, $record);
    $html .= '<div class="card-preview">';
    $html .= '<span class="search-sub">'.$sub.'</span><p class="search-snip">'.($description ? $description : __('Preview text unavailable.')).'</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    if ($type == 'Item') {
        if ($src = rl_get_first_image_src($record)) {
            $itemimg = '<img src="'.$src.'"/>';
        } elseif (metadata($record, 'has thumbnail')
                && (!stripos($img, 'ionicons') && !stripos($img, 'fallback'))
            ) {
            $itemimg = item_image('square_thumbnail');
        } else {
            $itemimg = '<img src="'.img('ionicons/custom/blank.svg').'"/>';
        }
        $html .= link_to($record, 'show', $itemimg, array('class' => 'result-image'));
    } elseif ($type == 'Tour') {
        $tourimg = $record->getItems() && $record->getItems()[0]
                ? '<img src="'.rl_get_first_image_src($record->getItems()[0]).'"/>'
                : '<img src="'.img('ionicons/compass-sharp.svg').'"/>';
        $html .= link_to($record, 'show', $tourimg, array('class' => 'result-image'));
    } elseif ($recordImage = record_image($type)) {
        $html .= link_to($record, 'show', $recordImage, array('class' => 'result-image'));
    }
    $html .= '</article>';
    return $html;
}
/*
** Result subhead by type
*/
function rl_subhead_by_type($type=null, $record=null)
{
    switch ($type) {
        case 'Item':
            return strip_tags(rl_the_byline($record, false), '<a>');
        case 'File':
            $parent=get_record_by_id('Item', $record->item_id);
            $title=metadata($parent, array('Dublin Core','Title'));
            return __('This file appears in: %s', link_to($parent, 'show', strip_tags($title)));
        case 'Tour':
            return __('%s Locations', rl_tour_total_items($record));
        case 'Collection':
            return __('%1s %2s', metadata($record, 'total_items'), rl_item_label('plural'));
        default:
            return null;
    }
}
/*
** Icons names by content type
*/
function rl_icon_name_by_type($type=null, $mime=null)
{
    switch ($type) {
        case 'Item':
            $i = 'location';
            break;
        case 'File':
            if ($mime) {
                switch ($mime) {
                    case substr($mime, 0, 5) === 'audio':
                        $i = 'headset';
                        break;
                    case substr($mime, 0, 5) === 'video':
                        $i = 'film';
                        break;
                    case substr($mime, 0, 5) === 'image':
                        $i = 'image';
                        break;
                    default:
                        $i = 'document-text';
                        break;
                }
                break;
            }
        $i = 'document-text';
        break;

        case 'Tour':
            $i = 'compass';
            break;
        case 'Collection':
            $i = 'folder';
            break;
        default:
            $i = 'globe';
            break;
    }
    return $i;
}
/*
** Sitewide Search Result Text
*/
function rl_search_text($type=null, $record=null)
{
    switch ($type) {
        case 'Item':
            return rl_the_text($record);
        case 'File':
            return metadata($record, array('Dublin Core', 'Description'), array('no_escape' => true));
        case 'Tour':
            $tour = $record;
            return tour('Description');
        case 'SimplePagesPage':
            return strip_tags(metadata($record, 'text', array('no_escape' => true)));
        case 'Collection':
            return metadata($record, array('Dublin Core', 'Description'), array('no_escape' => true));;
        case 'Exhibit':
            return metadata($record, 'description', array('no_escape' => true));
        case 'ExhibitPage':
            return null;
        default:
            return null;
    }
}
/*
** Get Basic MIME type
*/
function rl_clean_mime($mime=null)
{
    $array = explode('/', $mime);
    return $array[0] !== 'application' ? $array[0] : $array[1];
}
/*
** Normalize Record Type Names
*/
function rl_relabel_type($type=null)
{
    switch ($type) {
        case 'Item':
            return rl_item_label('singular');
        case 'SimplePagesPage':
            return __('Page');
        case 'ExhibitPage':
            return __('Exhibit Page');
        default:
            return $type;
    }
}
/*
** Remove select plugin/core assets from queue
** view: $this
** paths: array('/plugins/Geolocation','admin-bar','family=Arvo:400')
*/
function rl_assets_blacklist($view=null, $paths=array())
{
    if ($view) {
        $scripts = $view->headScript();
        foreach ($scripts as $key=>$file) {
            foreach ($paths as $path) {
                if (strpos($file->attributes['src'], $path) !== false) {
                    $scripts[$key]->type = null;
                    $scripts[$key]->attributes['src'] = null;
                    $scripts[$key]->attributes['source'] = null;
                }
            }
        }
        $styles = $view->headLink();
        foreach ($styles as $key=>$file) {
            foreach ($paths as $path) {
                if (strpos($file->href, $path) !== false) {
                    $styles[$key]->href = null;
                    $styles[$key]->type = null;
                    $styles[$key]->rel = null;
                    $styles[$key]->media = null;
                    $styles[$key]->conditionalStylesheet = null;
                }
            }
        }
    }
}

/*
** SEO Page Description
*/
function rl_seo_pagedesc($item=null, $tour=null, $file=null)
{
    if ($item != null) {
        $itemdesc=snippet(rl_the_text($item), 0, 500, "...");
        return htmlspecialchars(strip_tags($itemdesc));
    } elseif ($tour != null) {
        $tourdesc=snippet(tour('Description'), 0, 500, "...");
        return htmlspecialchars(strip_tags($tourdesc));
    } elseif ($file != null) {
        $filedesc=snippet(metadata('file', array('Dublin Core', 'Description')), 0, 500, "...");
        return htmlspecialchars(strip_tags($filedesc));
    } else {
        return rl_seo_sitedesc();
    }
}

/*
** SEO Site Description
*/
function rl_seo_sitedesc()
{
    return strip_tags(option('description')) ? strip_tags(option('description')) : (rl_about() ? strip_tags(rl_about()) : null);
}

/*
** SEO Page Title
*/
function rl_seo_pagetitle($title, $item)
{
    $subtitle=$item ? (rl_the_subtitle($item) ? ' - '.rl_the_subtitle($item) : null) : null;
    $pt = $title ? $title.$subtitle.' | '.option('site_title') : option('site_title');
    return strip_tags($pt);
}

/*
** SEO Page Image
*/
function rl_seo_pageimg($item=null, $file=null)
{
    if ($item) {
        if (metadata($item, 'has thumbnail')) {
            $itemimg=item_image('fullsize') ;
            preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $itemimg, $result);
            $itemimg=array_pop($result);
        }
    } elseif ($file) {
        if ($itemimg=file_image('fullsize')) {
            preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $itemimg, $result);
            $itemimg=array_pop($result);
        }
    }
    return isset($itemimg) ? $itemimg : rl_seo_pageimg_custom();
}

/*
** SEO Site Image
*/
function rl_seo_pageimg_custom()
{
    $custom_img = get_theme_option('custom_meta_img');
    $custom_img_url = $custom_img ? WEB_ROOT.'/files/theme_uploads/'.$custom_img : rl_the_logo_url();
    return $custom_img_url;
}

/*
** Get theme CSS link with version number
*/
function rl_theme_css($media='all')
{
    $themeName = Theme::getCurrentThemeName();
    $theme = Theme::getTheme($themeName);
    echo '<link href="'.WEB_PUBLIC_THEME.'/'.$themeName.'/css/screen.css?v='.$theme->version.'" media="'.$media.'" rel="stylesheet">';
}


/*
** Custom Label for Items/Stories
*/
function rl_item_label($which=null)
{
    if ($which=='singular') {
        return ($singular=get_theme_option('item_label_singular')) ? $singular : __('Story');
    } elseif ($which=='plural') {
        return ($plural=get_theme_option('item_label_plural')) ? $plural : __('Stories');
    } else {
        return __('Story');
    }
}

/*
** Custom Label for Tours
*/
function rl_tour_label($which=null)
{
    if ($which=='singular') {
        return ($singular=get_theme_option('tour_label_singular')) ? $singular : __('Tour');
    } elseif ($which=='plural') {
        return ($plural=get_theme_option('tour_label_plural')) ? $plural : __('Tours');
    } else {
        return __('Tour');
    }
}


/*
** Global navigation
*/
function rl_global_nav($nested=false)
{
    $curatenav=get_theme_option('default_nav');
    if ($curatenav==1 || !isset($curatenav)) {
        return nav(array(
                array('label'=>__('Home'),'uri' => url('/')),
                array('label'=>rl_item_label('plural'),'uri' => url('items/browse')),
                array('label'=>rl_tour_label('plural'),'uri' => url('tours/browse/')),
                array('label'=>__('Map'),'uri' => url('items/map/')),
                array('label'=>__('About'),'uri' => url('about/')),
            ));
    } elseif ($nested) {
        return '<div class="custom nested">'.public_nav_main()->setMaxDepth(1).'</div>';
    } else {
        return '<div class="custom">'.public_nav_main()->setMaxDepth(0).'</div>';
    }
}

/*
** Subnavigation for items/browse
*/
function rl_item_browse_subnav()
{
    echo nav(array(
            array('label'=>__('All') ,'uri'=> url('items/browse')),
            array('label'=>__('Featured') ,'uri'=> url('items/browse?featured=1')),
            array('label'=>__('Tags'), 'uri'=> url('items/tags')),
        ));
}

/*
** Subnavigation for search and items/search
*/
function rl_search_subnav()
{
    echo nav(array(
            array('label'=>__('%s Search', rl_item_label('singular')), 'uri'=> url('items/search')),
            array('label'=>__('Sitewide Search'), 'uri'=> url('search')),
        ));
}


/*
** Subnavigation for collections/browse
*/

function rl_collection_browse_subnav()
{
    echo nav(array(
            array('label'=>__('All') ,'uri'=> url('collections/browse')),
            array('label'=>__('Featured') ,'uri'=> url('collections/browse?featured=1')),
        ));
}

function rl_tour_browse_subnav()
{
    echo nav(array(
            array('label'=>__('All') ,'uri'=> url('tours/browse')),
            array('label'=>__('Featured') ,'uri'=> url('tours/browse?featured=1')),
        ));
}

/*
** Logo URL
*/
function rl_the_logo_url()
{
    $logo = get_theme_option('lg_logo');
    return $logo ? WEB_ROOT.'/files/theme_uploads/'.$logo : img('logo.png');
}

/*
** Logo IMG Tag
*/
function rl_the_logo()
{
    return '<img src="'.rl_the_logo_url().'" alt="'.option('site_title').' '.__('Logo').'"/>';
}

/*
** Link to Random Item
*/
function random_item_link($text=null, $class='show', $hasImage=true)
{
    if (!$text) {
        $text= __('View a Random %s', rl_item_label('singular'));
    }
    $randitems = get_records('Item', array( 'sort_field' => 'random', 'hasImage' => $hasImage), 1);

    if (count($randitems) > 0) {
        $link = link_to($randitems[0], 'show', $text, array( 'class' => 'random-story-link ' . $class ));
    } else {
        $link = link_to(
            '/',
            'show',
            __('Publish some items to activate this link'),
            array( 'class' => 'random-story-link ' . $class )
        );
    }
    return $link;
}

/*
** Ionicons
** https://ionic.io/ionicons
*/
function rl_icon($name=null, $variant="-sharp")
{
    try {
        $file = physical_path_to('images/ionicons/'.$name.$variant.'.svg');
        $svg = file_get_contents($file);
    } catch (exception $e) {
        $svg = null;
    }
    return $svg ? '<span class="icon '.$name.'">'.$svg.'</span>' : null;
}

/*
** Global header
*/
function rl_global_header($html=null)
{
    ?>
<nav id="top-navigation" class="" aria-label="<?php echo __('Main Navigation'); ?>">

    <!-- Home / Logo -->
    <?php echo link_to_home_page(rl_the_logo(), array('id'=>'home-logo', 'aria-label'=>'Home')); ?>
    <div id="nav-desktop">
        <?php echo '<a class="button transparent" href="'.url('items/browse').'">'.rl_icon("location").rl_item_label('plural').'</a>'; ?>
        <?php echo '<a class="button transparent" href="'.url('tours/browse').'">'.rl_icon("compass").rl_tour_label('plural').'</a>'; ?>
        <?php echo '<a class="button transparent" href="'.url('items/map').'">'.rl_icon("map").__('Map').'</a>'; ?>
    </div>
    <div id="nav-interactive">
        <!-- Search -->
        <a tabindex="0" title="<?php echo __('Search'); ?>" id="search-button" href="#footer-search-form" class="button transparent"><?php echo rl_icon("search"); ?>
            <span>
                <?php echo __('Search'); ?></span></a>
        <!-- Menu Button -->
        <a tabindex="0" title="<?php echo __('Menu'); ?>" id="menu-button" href="#footer-nav" class="button transparent"><?php echo rl_icon("menu"); ?>
            <span>
                <?php echo __('Menu'); ?></span></a>
    </div>

</nav>
<div id="header-search-container">
    <div id="header-search-inner" class="inner-padding">
        <?php echo rl_simple_search('header-search', array('id'=>'header-search-form','class'=>'capsule'), __('Search')); ?>
        <div class="search-options">
            <?php echo '<a href="'.url('items/search').'">'.__('Advanced %s Search', rl_item_label()).' &#9656;</a>'; ?><br>
            <?php echo '<a href="'.url('search').'">'.__('Sitewide Search').' &#9656;</a>'; ?>
        </div>
    </div>
    <div class="overlay" onclick="overlayClick()"></div>
</div>

<div id="header-menu-container">
    <div id="header-menu-inner">
        <?php echo rl_find_us('transparent-on-dark'); ?>
        <nav>
            <?php echo rl_global_nav(true); ?>
        </nav>
        <div class="menu-random-container"><?php echo random_item_link(rl_icon('dice').__("View a Random %s", rl_item_label('singular')), $class='button transparent', $hasImage=true); ?></div>
        <div class="menu-appstore-container"><?php echo rl_appstore_downloads(); ?></div>
    </div>
    <div class="overlay" onclick="overlayClick()"></div>
</div>
<?php
}

/*
** Sanitize user-input to prevent bad control character messages
*/
function rl_json_plaintextify($text=null)
{
    return trim(addslashes(preg_replace("/\r|\n/", " ", strip_tags($text))));
}

/*
** Single Tour JSON
*/
function rl_get_tour_json($tour=null)
{
    if ($tour) {
        $tourItems=array();
        foreach ($tour->Items as $item) {
            $location = get_db()->getTable('Location')->findLocationByItem($item, true);
            $address = (element_exists('Item Type Metadata', 'Street Address'))
                ? metadata($item, array( 'Item Type Metadata','Street Address' ))
                : null;
            $title=metadata($item, array( 'Dublin Core', 'Title' ));
            if ($location && $item->public) {
                $tourItems[] = array(
                    'id'		=> $item->id,
                    'title'		=> rl_json_plaintextify($title),
                    'address'	=> rl_json_plaintextify($address),
                    'latitude'	=> $location[ 'latitude' ],
                    'longitude'	=> $location[ 'longitude' ],
                    );
            }
        }
        $tourMetadata = array(
             'id'           => $tour->id,
             'items'        => $tourItems,
             );
        return json_encode($tourMetadata);
    }
}

/*
** Map Type
** Uses variable set in each page template
*/
function rl_map_type($maptype='none', $item=null, $tour=null)
{
    if ($maptype == 'focusarea') {
        return rl_display_map('focusarea', null, null);
    } elseif ($maptype == 'story') {
        return rl_display_map('story', $item, null, null);
    } elseif ($maptype == 'queryresults') {
        return rl_display_map('queryresults', null, null);
    } elseif ($maptype == 'tour') {
        return rl_display_map('tour', null, $tour);
    } elseif ($maptype == 'collection') {
        return rl_display_map('queryresults', null, null);
    } elseif ($maptype == 'none') {
        return null;
    } else {
        return null;
    }
}


/*
** Render the map
** Source feeds generated from Mobile JSON plugin
** Location data (LatLon and Zoom) created and stored in Omeka using stock Geolocation plugin
*/
function rl_display_map($type=null, $item=null, $tour=null)
{
    $pluginlng=(get_option('geolocation_default_longitude')) ? get_option('geolocation_default_longitude') : null;
    $pluginlat=(get_option('geolocation_default_latitude')) ? get_option('geolocation_default_latitude') : null;
    $zoom=(get_option('geolocation_default_zoom_level')) ? get_option('geolocation_default_zoom_level') : 12;
    $color=get_theme_option('marker_color') ? get_theme_option('marker_color') : '#222222';
    $featured_color=get_theme_option('featured_marker_color') ? get_theme_option('featured_marker_color') : $color;
    switch ($type) {
        case 'focusarea':
            /* all stories, map is centered on focus area (plugin center) */
            $json_source=WEB_ROOT.'/items/browse?output=mobile-json';
            break;

        case 'global':
            /* all stories, map is bounded according to content */
            $json_source=WEB_ROOT.'/items/browse?output=mobile-json';
            break;

        case 'queryresults':
            /* browsing by tags, subjects, search results, etc, map is bounded according to content */
            $uri=WEB_ROOT.$_SERVER['REQUEST_URI'];
            $json_source=$uri.'&output=mobile-json';
            break;

        case 'tour':
            /* single tour, map is bounded according to content  */
            $json_source= ($tour) ? rl_get_tour_json($tour) : null;
            break;

        default:
            $json_source=WEB_ROOT.'/items/browse?output=mobile-json';
    } ?>
<script>
// // PHP Variables
// var type =  '<?php echo $type ; ?>';
// var color = '<?php echo $color ; ?>';
// var featured_color = '<?php echo $featured_color ; ?>';
// var root = '<?php echo WEB_ROOT ; ?>';
// var source ='<?php echo $json_source ; ?>';
// var center =[<?php echo $pluginlat.','.$pluginlng ; ?>];
// var zoom = <?php echo $zoom ; ?>;
// var defaultItemZoom=<?php echo get_theme_option('map_zoom_single') ? (int)get_theme_option('map_zoom_single') : 14; ?>;
// var featuredStar = <?php echo get_theme_option('featured_marker_star'); ?>;
// var useClusters = <?php echo get_theme_option('clustering'); ?>; 
// var clusterTours = <?php echo get_theme_option('tour_clustering'); ?>; 
// var clusterIntensity = <?php echo get_theme_option('cluster_intensity') ? get_theme_option('cluster_intensity') : 15; ?>; 
// var alwaysFit = <?php echo get_theme_option('fitbounds') ? get_theme_option('fitbounds') : 0; ?>; 
// var markerSize = '<?php echo get_theme_option('marker_size') ? get_theme_option('marker_size') : "m"; ?>'; 
// var mapBounds; // keep track of changing bounds
// var root_url = '<?php echo WEB_ROOT; ?>';
// var geolocation_icon = '<?php echo img('geolocation.png'); ?>';
// var mapLayerThemeSetting = '<?php echo get_theme_option('map_style') ? get_theme_option('map_style') : 'CARTO_VOYAGER'; ?>';
// var leafletjs='<?php //echo src('leaflet.maki.combined.min.js','javascripts');?>'+'?v=1.1';
// var leafletcss='<?php //echo src('leaflet/leaflet.min.css','javascripts');?>'+'?v=1.1';	
// var leafletClusterjs='<?php //echo src('leaflet.markercluster/leaflet.markercluster.js','javascripts');?>'+'?v=1.1';
// var leafletClustercss='<?php //echo src('leaflet.markercluster/leaflet.markercluster.min.css','javascripts');?>'+'?v=1.1';
// var mapbox_tile_layer='<?php echo get_theme_option('mapbox_tile_layer'); ?>';
// var mapbox_access_token='<?php echo get_theme_option('mapbox_access_token'); ?>';
// var mapbox_layer_title='<?php echo get_theme_option('mapbox_tile_layer') ? ucwords(str_replace(array('-v11','-v10','-v9','-'), ' ', get_theme_option('mapbox_tile_layer'))) : "Mapbox"; ?>';
// 
// // End PHP Variables
// 
// var isSecure = window.location.protocol == 'https:' ? true : false;	
// 
// jQuery(document).ready(function() {
// 	loadCSS( leafletcss );
// 	if(useClusters==true) loadCSS( leafletClustercss );
// 	
// 	loadJS( leafletjs, function(){
// 		console.log('Leaflet ready...');
// 		
// 		var terrain = L.tileLayer('//stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{retina}.jpg', {
// 				attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | Map Tiles by <a href="http://stamen.com/">Stamen Design</a>',
// 				retina: (L.Browser.retina) ? '@2x' : '',
// 			});		
// 		var carto = L.tileLayer('//cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}{retina}.png', {
// 		    	attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | <a href="https://cartodb.com/attributions">CartoDB</a>',
// 				retina: (L.Browser.retina) ? '@2x' : '',
// 			});
// 		var voyager = L.tileLayer('//cartodb-basemaps-{s}.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}{retina}.png', {
// 		    	attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | <a href="https://cartodb.com/attributions">CartoDB</a>',
// 				retina: (L.Browser.retina) ? '@2x' : '',
// 			});					
// 		var mapbox = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/'+mapbox_tile_layer+'/tiles/{z}/{x}/{y}?access_token={accessToken}', {
// 		    	attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | <a href="https://www.mapbox.com/feedback/">Mapbox</a>',
// 				accessToken: mapbox_access_token,
// 				tileSize: 512,
// 				zoomOffset: -1,
// 			});	
// 			
// 		var defaultMapLayer;	
// 		switch(mapLayerThemeSetting){
// 			case 'TERRAIN':
// 				defaultMapLayer=terrain;
// 				break;
// 			case 'CARTO':
// 				defaultMapLayer=carto;
// 				break;
// 			case 'CARTO_VOYAGER':
// 				defaultMapLayer=voyager;
// 				break;						
// 			case 'MAPBOX_TILES':
// 				defaultMapLayer=mapbox;
// 				break;	
// 			default:
// 				defaultMapLayer=wikimedia;				
// 		}
// 						
// 		// helper for title attributes with encoded HTML
// 		function convertHtmlToText(value) {
// 		    var d = document.createElement('div');
// 		    d.innerHTML = value;
// 		    return d.innerText;
// 		}				
// 
// 		var mapDisplay =function(){
// 			// Build the base map
// 			var map = L.map('curatescape-map-canvas',{
// 				layers: defaultMapLayer,
// 				minZoom: 3,
// 				scrollWheelZoom: false,
// 			}).setView(center, zoom);
// 			
// 			
// 			// Geolocation controls
// 			if( !isSecure || !navigator.geolocation){			
// 				console.warn('Geolocation is not available over insecure origins on this browser.');
// 			}else{
// 				var geolocationControl = L.control({position: 'topleft'});
// 				geolocationControl.onAdd = function (map) {
// 				    var div = L.DomUtil.create('div', 'leaflet-control leaflet-control-geolocation');
// 				    div.innerHTML = '<a class="leaflet-control-geolocation-toggle" href="#" aria-label="Geolocation" title="Geolocation" role="button"><i class="fa fa fa-location-arrow" aria-hidden="true"></i></a>'; 
// 				    return div;
// 				};
// 				geolocationControl.addTo(map);				
// 			}
// 			
// 			// Fullscreen controls
// 			var fullscreenControl = L.control({position: 'topleft'});
// 			fullscreenControl.onAdd = function (map) {
// 			    var div = L.DomUtil.create('div', 'leaflet-control leaflet-control-fullscreen');
// 			    div.innerHTML = '<a class="leaflet-control-fullscreen-toggle" href="#" aria-label="Fullscreen" title="Fullscreen" role="button"><i class="fa fa-expand" aria-hidden="true"></i></a>'; 
// 			    return div;
// 			};
// 			fullscreenControl.addTo(map);
// 			
// 			// Layer controls
// 			var allLayers={
// 				"Street":(defaultMapLayer == terrain) ? voyager : defaultMapLayer,
// 				"Terrain":terrain,
// 			};
// 			if(mapbox_access_token){
// 				allLayers[mapbox_layer_title]=mapbox;
// 			}
// 			L.control.layers(allLayers).addTo(map);		
// 			
// 			
// 								
// 			
// 			// Center marker and popup on open
// 			map.on('popupopen', function(e) {
// 				// find the pixel location on the map where the popup anchor is
// 			    var px = map.project(e.popup._latlng); 
// 			    // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
// 			    px.y -= e.popup._container.clientHeight/2;
// 			    // pan to new center
// 			    map.panTo(map.unproject(px),{animate: true}); 
// 			});				
// 			// Add Markers
// 			var addMarkers = function(data){				
// 			    function icon(color,markerInner){ 
// 			        return L.MakiMarkers.icon({
// 			        	icon: markerInner, 
// 						color: color, 
// 						size: markerSize,
// 						accessToken: "pk.eyJ1IjoiZWJlbGxlbXBpcmUiLCJhIjoiY2ludWdtOHprMTF3N3VnbHlzODYyNzh5cSJ9.w3AyewoHl8HpjEaOel52Eg"
// 			    		});	
// 			    }				
// 				if(typeof(data.items)!="undefined"){ // tours and other multi-item maps
// 					
// 					var group=[];
// 					if(useClusters==true){
// 						var markers = L.markerClusterGroup({
// 							spiderfyOnMaxZoom: false, // should be an option?
// 							zoomToBoundsOnClick:true,
// 							disableClusteringAtZoom: clusterIntensity,
// 							polygonOptions: {
// 								'stroke': false,
// 								'color': '#000',
// 								'fillOpacity': .1
// 							}
// 						});
// 					}
// 					
// 			        jQuery.each(data.items,function(i,item){
// 						var appendQueryParams=(type=='tour') ? '?tour='+data.id+'&index='+i : '';
// 				        var address = item.address ? item.address : '';
// 						var c = (item.featured==1 && featured_color) ? featured_color : color;
// 						var inner = (item.featured==1 && featuredStar) ? "star" : "circle";
// 				        if(typeof(item.thumbnail)!="undefined"){
// 					        var image = '<a href="'+root_url+'/items/show/'+item.id+'" class="curatescape-infowindow-image '+(!item.thumbnail ? 'no-img' : '')+'" style="background-image:url('+item.thumbnail+');"></a>';
// 					    }else{
// 						    var image = '';
// 					    }
// 					    var number = (type=='tour') ? '<span class="number">'+(i+1)+'</span>' : '';
// 				        var html = image+number+'<span><a class="curatescape-infowindow-title" href="'+root_url+'/items/show/'+item.id+appendQueryParams+'">'+item.title+'</a><br>'+'<div class="curatescape-infowindow-address">'+address.replace(/(<([^>]+)>)/ig,"")+'</div></span>';
// 														
// 						var marker = L.marker([item.latitude,item.longitude],{
// 							icon: icon(c,inner),
// 							title: convertHtmlToText(item.title),
// 							alt: convertHtmlToText(item.title),
// 							}).bindPopup(html);
// 						
// 						group.push(marker);  
// 						
// 						if(useClusters==true) markers.addLayer(marker);
// 			
// 			        });
// 			        
// 			        if(useClusters==true && type!=='tour' || type=='tour' && clusterTours==true){
// 				        map.addLayer(markers);
// 				        mapBounds = markers.getBounds();
// 				    }else{
// 			        	group=new L.featureGroup(group); 
// 						group.addTo(map);	
// 						mapBounds = group.getBounds();				    
// 				    }
// 			        
// 					// Fit map to markers as needed			        
// 			        if((type == 'queryresults'|| type == 'tour') || alwaysFit==true){
// 				        if(useClusters==true){
// 					        map.fitBounds(markers.getBounds());
// 					    }else{
// 						    map.fitBounds(group.getBounds());
// 					    }
// 			        }
// 			        
// 			        
// 				}else{ // single items
// 					map.setView([data.latitude,data.longitude],defaultItemZoom);	
// 			        var address = data.address ? data.address : data.latitude+','+data.longitude;
// 			
// 			        var image = (typeof(data.thumbnail)!="undefined") ? '<a href="" class="curatescape-infowindow-image '+(!data.thumbnail ? 'no-img' : '')+'" style="background-image:url('+data.thumbnail+');" title="'+data.title+'"></a>' : '';
// 			
// 			        var html = image+'<div class="curatescape-infowindow-address single-item"><span class="icon-map-marker" aria-hidden="true"></span> '+address.replace(/(<([^>]+)>)/ig,"")+'</div>';
// 					
// 					var marker = L.marker([data.latitude,data.longitude],{
// 						icon: icon(color,"circle"),
// 						title: convertHtmlToText(data.title),
// 						alt: convertHtmlToText(data.title),
// 						}).bindPopup(html);					
// 					
// 					marker.addTo(map);
// 
// 					mapBounds = map.getBounds();
// 				
// 				}
// 				
// 			}		
// 			
// 			if(type=='story'){
// 				var data = jQuery.parseJSON(source);
// 				if(data){
// 					addMarkers(data);
// 				}
// 				
// 			}else if(type=='tour'){
// 				var data = jQuery.parseJSON(source);
// 				addMarkers(data);
// 				
// 			}else if(type=='focusarea'){
// 				jQuery.getJSON( source, function(data) {
// 					var data = data;
// 					addMarkers(data);
// 				});
// 				
// 			}else if(type=='queryresults'){
// 				jQuery.getJSON( source, function(data) {
// 					var data = data;
// 					addMarkers(data);
// 				});
// 				
// 			}else{
// 				jQuery.getJSON( source, function(data) {
// 					var data = data;
// 					addMarkers(data);
// 				});
// 			}
// 			
// 			/* Map Action Buttons */
// 			
// 			// Fullscreen
// 			jQuery('.leaflet-control-fullscreen-toggle').click(function(e){
// 				e.preventDefault();
// 				jQuery("body").toggleClass("fullscreen-map");
// 				jQuery(".leaflet-control-fullscreen-toggle i").toggleClass('fa-expand').toggleClass('fa-compress');
// 				map.invalidateSize();
// 			});
// 			jQuery(document).keyup(function(e) {
// 				if ( e.keyCode == 27 ){ // exit fullscreen
// 					if(jQuery('body').hasClass('fullscreen-map')) jQuery('.leaflet-control-fullscreen-toggle').click();
// 				}
// 			});
// 			
// 			// Geolocation
// 			jQuery('.leaflet-control-geolocation-toggle').click(
// 				function(e){
// 				e.preventDefault();	
// 				var options = {
// 					enableHighAccuracy: true,
// 					maximumAge: 30000,
// 					timeout: 15000
// 				};
// 				jQuery(".leaflet-control-geolocation-toggle").addClass("working");
// 				navigator.geolocation.getCurrentPosition(
// 					function(pos) {
// 						var userLocation = [pos.coords.latitude, pos.coords.longitude];					
// 						// adjust map view
// 						if(type=='story'|| type=='tour' || type == 'queryresults'){
// 							if(jQuery(".leaflet-popup-close-button").length) jQuery(".leaflet-popup-close-button")[0].click(); // close popup
// 							var newBounds = new L.LatLngBounds(mapBounds,new L.LatLng(pos.coords.latitude, pos.coords.longitude));
// 							map.fitBounds(newBounds);
// 						}else{
// 							map.panTo(userLocation);
// 						}
// 						// add/update user location indicator
// 						if(typeof(userMarker)==='undefined') {
// 							userMarker = new L.circleMarker(userLocation,{
// 							  radius: 8,
// 							  fillColor: "#4a87ee",
// 							  color: "#ffffff",
// 							  weight: 3,
// 							  opacity: 1,
// 							  fillOpacity: 0.8,
// 							}).addTo(map);
// 							jQuery(".leaflet-control-geolocation-toggle").removeClass("working");
// 						}else{
// 							userMarker.setLatLng(userLocation);
// 							jQuery(".leaflet-control-geolocation-toggle").removeClass("working");
// 						}
// 					}, 
// 					function(error) {
// 						console.log(error);
// 						var errorMessage = error.message ? ' Error message: "' + error.message + '"' : 'Oops! We were unable to determine your current location.';
// 						jQuery(".leaflet-control-geolocation-toggle").removeClass("working");
// 						alert(errorMessage);
// 					}, 
// 					options);
// 			});	
// 			
// 			// enable mouse scrollwheel zoom if the user has interacted with the map
// 			map.once('focus', function() { map.scrollWheelZoom.enable(); });					
// 					
// 		}
// 
// 
// 
// 
// 		if(useClusters==true){
// 			loadJS( leafletClusterjs, function(){
// 				console.log('Clustering ready...')
// 				mapDisplay();
// 			});
// 		}else{
// 			mapDisplay();
// 		}
// 
// 		
// 	});
// 
// 
// 
// });
</script>

<!-- Map Container -->
<div class="curatescape-map">
    <div id="curatescape-map-canvas"></div>
</div>

<?php
}


// single story map

function rl_story_map_single($title=null, $location=null, $address=null, $hero_img=null, $hero_orientation=null) { ?>
<nav aria-label="<?php echo __('Skip Interactive Map');?>"><a id="skip-map" href="#map-actions"><?php echo __('Skip Interactive Map');?></a></nav>
<figure id="story-map" data-default-layer="<?php echo get_theme_option('map_style') ? get_theme_option('map_style') : 'CARTO_VOYAGER';?>" data-lat="<?php echo $location[ 'latitude' ];?>" data-lon="<?php echo $location[ 'longitude' ];?>" data-zoom="<?php echo $location['zoom_level'];?>" data-title="<?php echo strip_tags($title);?>" data-image="<?php echo $hero_img;?>" data-orientation="<?php echo $hero_orientation;?>" data-address="<?php echo strip_tags($address);?>" data-color="<?php echo get_theme_option('marker_color');?>" data-featured-color="<?php echo get_theme_option('featured_marker_color');?>" data-featured-star="<?php echo get_theme_option('featured_marker_star');?>" data-rool-url="<?php echo WEB_ROOT;?>" data-maki-js="<?php echo src('maki/maki.min.js', 'javascripts');?>" data-providers="<?php echo src('providers.js', 'javascripts');?>" data-leaflet-js="<?php echo src('theme-leaflet/leaflet.js', 'javascripts');?>" data-leaflet-css="<?php echo src('theme-leaflet/leaflet.css', 'javascripts');?>">
    <div class="curatescape-map">
        <div id="curatescape-map-canvas"></div>
    </div>
    <figcaption><?php echo rl_map_caption();?></figcaption>
</figure>
<div id="map-actions"><a class="button directions" target="_blank" rel="noopener" href="https://maps.google.com/maps?location&daddr=<?php echo $address ? urlencode(strip_tags($address)) : $location[ 'latitude' ].','.$location[ 'longitude' ];?>"><?php echo rl_icon("logo-google", null);?>
        <span class="label">
            <?php echo __('Open in Google Maps');?></span></a></div>
<?php }

/*
** Add the map actions toolbar
*/
function rl_map_actions($item=null, $tour=null, $collection=null, $saddr='current', $coords=null)
{
    $street_address=null;

    if ($item!==null) {

            // get the destination coordinates for the item
        $location = get_db()->getTable('Location')->findLocationByItem($item, true);
        $coords=$location[ 'latitude' ].','.$location[ 'longitude' ];
        $street_address=rl_street_address($item);

        $showlink=true;
    } elseif ($tour!==null) {

            // get the waypoint coordinates for the tour
        $coords = array();
        foreach ($tour->Items as $item) {
            set_current_record('item', $item);
            $location = get_db()->getTable('Location')->findLocationByItem($item, true);
            $coords[] = rl_street_address($item) ? urlencode(strip_tags(rl_street_address($item))) : $location['latitude'].','.$location['longitude'];
        }

        $daddr=end($coords);
        reset($coords);
        $waypoints=array_pop($coords);
        $waypoints=implode('+to:', $coords);
        $coords=$daddr.'+to:'.$waypoints;
    } ?>

<!-- Directions link -->
<?php if ($coords && ($item || $tour)):?>
<div id="map-actions"><a class="button directions" target="_blank" rel="noopener" href="https://maps.google.com/maps?saddr=<?php echo $saddr; ?>+location&daddr=<?php echo $street_address ? urlencode(strip_tags($street_address)) : $coords; ?>"><i class="fa fa-lg fa-google" aria-hidden="true"></i><span class="label"><?php echo __('Open in Google Maps'); ?></span></a></div>
<?php endif; ?>

<?php
}


/*
** Modified search form
** Adds HTML "placeholder" attribute
** Adds HTML "type" attribute
** Includes settings for simple and advanced search via theme options
*/

function rl_simple_search($inputID='search', $formProperties=array(), $ariaLabel="Search")
{
    $sitewide = (get_theme_option('use_sitewide_search') == 1) ? 1 : 0;
    $qname = ($sitewide==1) ? 'query' : 'search';
    $searchUri = ($sitewide==1) ? url('search') : url('items/browse?sort_field=relevance');
    $placeholder =  __('Search for %s', strtolower(rl_item_label('plural')));
    $default_record_types = rl_search_form_default_record_types();


    $searchQuery = array_key_exists($qname, $_GET) ? $_GET[$qname] : '';
    $formProperties['action'] = $searchUri;
    $formProperties['method'] = 'get';
    $html = '<form ' . tag_attributes($formProperties) . '>' . "\n";
    $html .= '<fieldset>' . "\n\n";
    $html .= get_view()->formText('search', $searchQuery, array('aria-label'=>$ariaLabel,'name'=>$qname,'id'=>$inputID,'class'=>'textinput search','placeholder'=>$placeholder));
    $html .= '</fieldset>' . "\n\n";

    // add hidden fields for the get parameters passed in uri
    $parsedUri = parse_url($searchUri);
    if (array_key_exists('query', $parsedUri)) {
        parse_str($parsedUri['query'], $getParams);
        foreach ($getParams as $getParamName => $getParamValue) {
            $html .= get_view()->formHidden($getParamName, $getParamValue, array('id'=>$inputID.'-'.$getParamValue));
        }
    }
    if ($sitewide==1 && count($default_record_types)) {
        foreach ($default_record_types as $drt) {
            $html .= get_view()->formHidden('record_types[]', $drt, array('id'=>$inputID.'-'.$drt));
        }
    }

    $html .= '<button aria-label="'.__("Submit").'" type="submit" class="submit button" name="submit_'.$inputID.'" id="submit_search_advanced_'.$inputID.'">'.rl_icon('search').'</button>';

    $html .= '</form>';
    return $html;
}


/*
** App Store links on homepage
*/
function rl_appstore_downloads()
{
    if (get_theme_option('enable_app_links')) {
        $apps=array();
        $ios_app_id = get_theme_option('ios_app_id');
        if ($ios_app_id) {
            $href='https://itunes.apple.com/us/app/'.$ios_app_id;
            $apps[]='<a class="button appstore ios" href="'.$href.'" target="_blank" rel="noopener">'.
            rl_icon('logo-apple-appstore', null).__('App Store').'</a>';
        }

        $android_app_id = get_theme_option('android_app_id');
        if ($android_app_id) {
            $href='http://play.google.com/store/apps/details?id='.$android_app_id;
            $apps[]='<a class="button appstore android" href="'.$href.'" target="_blank" rel="noopener">'.
            rl_icon('logo-google-playstore', null).__('Google Play').'</a>';
        }


        if (count($apps) > 1) {
            return implode(' ', $apps);
        }
    }
}


/*
** App Store links in footer
*/
function rl_appstore_footer()
{
    if (get_theme_option('enable_app_links')) {
        echo '<div id="app-store-links">';

        $ios_app_id = get_theme_option('ios_app_id');
        $android_app_id = get_theme_option('android_app_id');
        if (($ios_app_id != false) && ($android_app_id == false)) {
            echo '<a id="apple-text-link" class="app-store-footer" href="https://itunes.apple.com/us/app/'.$ios_app_id.'">'.__('Get the app for iPhone').'</a>';
        } elseif (($ios_app_id == false) && ($android_app_id != false)) {
            echo '<a id="android-text-link" class="app-store-footer" href="http://play.google.com/store/apps/details?id='.$android_app_id.'">'.__('Get the app for Android').'</a>';
        } elseif (($ios_app_id != false)&&($android_app_id != false)) {
            $iphone='<a id="apple-text-link" class="app-store-footer" href="https://itunes.apple.com/us/app/'.$ios_app_id.'">'.__('iPhone').'</a>';
            $android='<a id="android-text-link" class="app-store-footer" href="http://play.google.com/store/apps/details?id='.$android_app_id.'">'.__('Android').'</a>';
            echo __('Get the app for %1$s and %2$s', $iphone, $android);
        } else {
            echo __('iPhone + Android Apps Coming Soon!');
        }
        echo '</div>';
    }
}


/*
** Replace BR tags, wrapping text in P tags instead
*/
function replace_br($data)
{
    $data = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p>
<p>', $data);
    return "
<p>$data</p>";
}

/*
** primary item text
*/

function rl_the_text($item='item', $options=array())
{
    $dc_desc = metadata($item, array('Dublin Core', 'Description'), $options);
    $primary_text = element_exists('Item Type Metadata', 'Story') ? metadata($item, array('Item Type Metadata', 'Story'), $options) : null;

    return $primary_text ? replace_br($primary_text) : ($dc_desc ? replace_br($dc_desc) : null);
}

/*
** Title
*/
function rl_the_title($item='item')
{
    return '<h1 class="title">'.strip_tags(metadata($item, array('Dublin Core', 'Title')), array('index'=>0)).'</h1>';
}


/*
** Subtitle
*/

function rl_the_subtitle($item='item')
{
    $dc_title2 = metadata($item, array('Dublin Core', 'Title'), array('index'=>1));
    $subtitle=element_exists('Item Type Metadata', 'Subtitle') ? metadata($item, array('Item Type Metadata', 'Subtitle')) : null;

    return $subtitle ? '<p class="subtitle">'.$subtitle.'</p>' : ($dc_title2!=='[Untitled]' ? '<p class="subtitle">'.$dc_title2.'</p>' : null);
}

/*
** Lede
*/
function rl_the_lede($item='item')
{
    if (element_exists('Item Type Metadata', 'Lede')) {
        $lede=metadata($item, array('Item Type Metadata', 'Lede'));
        return $lede ? '<p class="lede">'.strip_tags($lede, '<a><em><i><u><b><strong><strike>').'</p>' : null;
    }
}

/*
** Title + Subtitle (for search/browse/home)
*/
function rl_the_title_expanded($item='item')
{
    $title='<h3 class="title">'.strip_tags(metadata($item, array('Dublin Core', 'Title'))).'</h3>';
    if (element_exists('Item Type Metadata', 'Subtitle')) {
        if ($s=metadata($item, array('Item Type Metadata','Subtitle'))) {
            $subtitle = '<p class="subtitle">'.strip_tags($s).'</p>';
            $title = $title.$subtitle;
        }
    }
    return link_to($item, 'show', $title, array('class'=>'permalink'));
}

/*
** Snippet: Lede + Story (for search/browse/home)
*/
function rl_snippet_expanded($item='item')
{
    $story=element_exists('Item Type Metadata', 'Story') ? metadata($item, array('Item Type Metadata', 'Story'), array('snippet'=>250)) : null;
    if (get_theme_option('lede_on_browse') && element_exists('Item Type Metadata', 'Lede')) {
        $lede = strip_tags(metadata($item, array('Item Type Metadata','Lede'))).' ';
        $story = $lede.$story;
    }
    return snippet($story, 0, 250, '&hellip;');
}


/*
** sponsor for use in item byline
*/
function rl_the_sponsor($item='item')
{
    if (element_exists('Item Type Metadata', 'Sponsor')) {
        $sponsor=metadata($item, array('Item Type Metadata','Sponsor'));
        return $sponsor ? '<span class="sponsor"> '.__('with research support from %s', $sponsor).'</span>' : null;
    }
}

/*
** Filed Under
** returns link to: (public) collection for item, or first subject, or first tag
*/
function rl_filed_under($item=null)
{
    if ($collection = get_collection_for_item() && $collection->public) {
        return link_to_collection_for_item($collection->display_name, array('class'=>'tag tag-alt'), 'show');
    } elseif ($subject = metadata('item', array('Dublin Core', 'Subject'), 0)) {
        $link = WEB_ROOT;
        $link .= htmlentities('/items/browse?term=');
        $link .= rawurlencode($subject);
        $link .= htmlentities('&search=&advanced[0][element_id]=49&advanced[0][type]=contains&advanced[0][terms]=');
        $link .= urlencode(str_replace('&amp;', '&', $subject));
        $node .= '<a class="tag tag-alt" href="'.$link.'">'.$subject.'</a>';
        return $node;
    } elseif (metadata($item, 'has tags') && $tag = $item->Tags[0]) {
        $link = WEB_ROOT;
        $link .= htmlentities('/items/browse?tags=');
        $link .= rawurlencode($tag);
        $node .= '<a class="tag tag-alt" href="'.$link.'">'.$tag.'</a>';
        return $node;
    } else {
        return link_to('items', 'browse', rl_item_label('singular'));
    }
}

/*
** Subjects for item
** Raw = output as <a>
    ** !Raw = output as <div>
        <h3>
            <ul>
                <li><a>
                        */
                        function rl_subjects($raw=false, $rawfirst=false)
                        {
                            $subjects = metadata('item', array('Dublin Core', 'Subject'), 'all');
                            $array=array();
                            $html = null;
                            if (count($subjects) > 0) {
                                foreach ($subjects as $subject) {
                                    $link = WEB_ROOT;
                                    $link .= htmlentities('/items/browse?term=');
                                    $link .= rawurlencode($subject);
                                    $link .= htmlentities('&search=&advanced[0][element_id]=49&advanced[0][type]=contains&advanced[0][terms]=');
                                    $link .= urlencode(str_replace('&amp;', '&', $subject));
                                    $node = '
                <li><a title="'.__('Subject').': '.$subject.'" class="tag tag-alt" href="'.$link.'">'.$subject.'</a></li>';
                                    array_push($array, $node);
                                }
                                $html .= '<div id="subjects">';
                                $html .= '<ul>';
                                $html .= implode('', $array);
                                $html .= '</ul>';
                                $html .= '</div>';
                            }
                            return $html;
                        }

                /*
                ** Display the item tags
                items/browse?tags=Cleveland+Metroparks
                */
                function rl_tags($item)
                {
                    $html = null;
                    if (metadata($item, 'has tags')) {
                        $array=array();
                        foreach ($item->Tags as $tag) {
                            $link = WEB_ROOT;
                            $link .= htmlentities('/items/browse?tags=');
                            $link .= urlencode($tag);
                            $node = '<li><a title="'.__('Tag').': '.$tag.'" class="tag" href="'.$link.'">'.$tag.'</a></li>';
                            array_push($array, $node);
                        }
                        $html .= '<div id="tags">';
                        $html .= '<ul>';
                        $html .= implode('', $array);
                        $html .= '</ul>';
                        $html .= '</div>';
                    }
                    return $html;
                }

                /*
                ** Display the item collection
                */
                function rl_collection($item)
                {
                    if ($collection = get_collection_for_item() && $collection->public) {
                        return '<div id="collection">'.link_to_collection_for_item(null, array('class'=>'tag tag-alt','title'=>__('Collection')), 'show').'</div>';
                    }
                }

                /* get a list of related tour links for a given item, for use on items/show template */
                function rl_tours_for_item($item_id=null, $html=null)
                {
                    if (plugin_is_active('TourBuilder')) {
                        if (is_int($item_id)) {
                            $db = get_db();
                            $prefix=$db->prefix;
                            $select = $db->select()
                ->from(array('ti' => $prefix.'tour_items')) // SELECT * FROM omeka_tour_items as ti
                ->join(array('t' => $prefix.'tours'), // INNER JOIN omeka_tours as t
                'ti.tour_id = t.id') // ON ti.tour_id = t.id
                ->where("item_id=$item_id AND public=1"); // WHERE item_id=$item_id
                $q = $select->query();
                            $results = $q->fetchAll();

                            if ($results) {
                                $html.='<div id="tour-for-item">
                    <ul>';
                                foreach ($results as $result) {
                                    $html.='<li><a class="tag tag-alt" href="/tours/show/'.$result['id'].'">';
                                    $html.=$result['title'];
                                    $html.='</a></li>';
                                }
                                $html.='</ul>
                </div>';
                            }
                            return $html;
                        }
                    }
                }

                /*
                ** Return SRC for an item's first image (excluding video thumbs, etc)
                */
                function rl_get_first_image_src($item, $size='fullsize')
                {
                    if ($item && $item->id) {
                        $db = get_db();
                        $table = $db->getTable('Files');
                        $select = $table->getSelect();
                        $select->where('item_id = '.$item->id);
                        $select->where('has_derivative_image = 1');
                        $select->where('mime_type LIKE "image%"');
                        $q = $select->query();
                        $results = $q->fetchAll();
                        if ($results) {
                            // first image file
                            $sanitized_filename = str_ireplace(array('.JPG','.jpeg','.JPEG','.png','.PNG','.gif','.GIF', '.bmp','.BMP'), '.jpg', $results[0]['filename']);
                            return WEB_ROOT.'/files/'.$size.'/'.$sanitized_filename;
                        } else {
                            return null;
                        }
                    } else {
                        return null;
                    }
                }

                /*
                ** Return formatted meta links
                */
                function rl_meta_style($heading=null, $array=array())
                {
                    $html = null;
                    if ($heading && count($array)) {
                        foreach ($array as $node) {
                            $html .= $node;
                        }
                    }
                    if ($html) {
                        return '<div class="meta-'.str_replace(' ', '-', strtolower($heading)).'">
                    <h3 class="metadata-label">'.$heading.'</h3>
                    <div class="meta-style">'.$html.'</div>
                </div>';
                    } else {
                        return null;
                    }
                }

                /*
                ** Display the official website
                */
                function rl_official_website($item='item')
                {
                    $html = null;
                    if (element_exists('Item Type Metadata', 'Official Website')) {
                        $website=metadata($item, array('Item Type Metadata','Official Website'));
                        $html .= $website ? '<div class="break">'.$website.'</div>' : null;
                    }
                    return $html;
                }

                /*
                ** Display the street address
                */
                function rl_street_address($item='item')
                {
                    if (element_exists('Item Type Metadata', 'Street Address')) {
                        $address=metadata($item, array('Item Type Metadata','Street Address'));
                        $map_link='<a target="_blank" rel="noopener" href="https://maps.google.com/maps?saddr=current+location&daddr='.urlencode(strip_tags($address)).'">map</a>';
                        return $address ? $address : null;
                    } else {
                        return null;
                    }
                }

                /*
                ** Display the access info
                */
                function rl_access_information($item='item', $formatted=true)
                {
                    if (element_exists('Item Type Metadata', 'Access Information')) {
                        $access_info=metadata($item, array('Item Type Metadata', 'Access Information'));
                        return $access_info ? ($formatted ? '<div class="access-information">
                    <h3>'.__('Access Information').'</h3>
                    <div>'.$access_info.'</div>
                </div>' : $access_info) : null;
                    } else {
                        return null;
                    }
                }

                /*
                ** Display the map caption
                */

                function rl_map_caption($item='item')
                {
                    $caption=array();
                    if ($addr=rl_street_address($item)) {
                        $caption[]=strip_tags($addr, '<a>');
                    }
                    if ($accs=rl_access_information($item, false)) {
                        $caption[]=strip_tags($accs, '<a>');
                    }
                    return implode(' | ', $caption);
                }

                        /*
                        ** Display the factoid
                        */
                        function rl_factoid($item='item', $html=null)
                        {
                            if (element_exists('Item Type Metadata', 'Factoid')) {
                                $factoids=metadata($item, array('Item Type Metadata','Factoid'), array('all'=>true));
                                if ($factoids) {
                                    $html .= '<div class="separator"></div>';
                                    foreach ($factoids as $factoid) {
                                        $html.='<div class="factoid caption">'.rl_icon('information-circle').'<span>'.$factoid.'</span></div>';
                                    }
                                    if ($html) {
                                        return '<aside id="factoid" artia-label="'.__('Factoids').'">'.$html.'</aside>';
                                    }
                                }
                            }
                        }

                        /*
                        ** Display related links
                        */
                        function rl_related_links()
                        {
                            $dc_relations_field = metadata('item', array('Dublin Core', 'Relation'), array('all' => true));

                            $related_resources = element_exists('Item Type Metadata', 'Related Resources') ? metadata('item', array('Item Type Metadata', 'Related Resources'), array('all' => true)) : null;

                            $relations = $related_resources ? $related_resources : $dc_relations_field;

                            if ($relations) {
                                $html= '<div class="related-resources">
                            <ol>';
                                $i=1;
                                foreach ($relations as $relation) {
                                    $html.= '<li id="footnote-'.$i.'">'.strip_tags($relation, '<a><i><cite><em><b><strong>').'</li>';
                                    $i++;
                                }
                                $html.= '</ol>
                        </div>';
                                return $html;
                            }
                        }


                        /*
                        ** Author Byline
                        */
                        function rl_the_byline($itemObj='item', $include_sponsor=false)
                        {
                            $html='<div class="byline">'.__('By').' ';
                            if (metadata($itemObj, array('Dublin Core', 'Creator'))) {
                                $authors=metadata($itemObj, array('Dublin Core', 'Creator'), array('all'=>true));
                                $total=count($authors);
                                $index=1;
                                $authlink=get_theme_option('link_author');
                                foreach ($authors as $author) {
                                    if ($authlink==1) {
                                        $href='/items/browse?search=&advanced[0][element_id]=39&advanced[0][type]=is+exactly&advanced[0][terms]='.$author;
                                        $author='<a href="'.$href.'">'.$author.'</a>';
                                    }
                                    switch ($index) {
                            case ($total):
                            $delim ='';
                            break;
                            case ($total-1):
                            $delim =' <span class="amp">&amp;</span> ';
                            break;

                            default:
                            $delim =', ';
                            break;
                            }
                                    $html .= $author.$delim;
                                    $index++;
                                }
                            } else {
                                $html .= option('site_title');
                            }
                            $html .= (($include_sponsor) && (rl_the_sponsor($itemObj)!==null)) ? ''.rl_the_sponsor($itemObj) : null;
                            $html .='</div>';
                            return $html;
                        }


                        /*
                        ** Custom item citation
                        */
                        function rl_item_citation()
                        {
                            return '<div class="item-citation">
                            <div>'.html_entity_decode(metadata('item', 'citation')).'</div>
                        </div>';
                        }

                        /*
                        ** Post Added/Modified String
                        */
                        function rl_post_date()
                        {
                            if (get_theme_option('show_datestamp')==1) {
                                $a=format_date(metadata('item', 'added'));
                                $m=format_date(metadata('item', 'modified'));

                                return '<div class="item-post-date">'.__('Published on %s.', $a).(($a!==$m) ? ' '.__('Last updated on %s.', $m) : null).'</div>';
                            }
                        }

                        /*
                        ** Build caption from description, source, creator, date
                        */
                        function rl_file_caption($file, $includeTitle=true)
                        {
                            $caption=array();

                            $title = metadata($file, array( 'Dublin Core', 'Title' ));
                            $caption[] = '<span class="file-title"><cite><a title="'.__('View File Record').'" href="/files/show/'.$file->id.'">'.($title ? $title : __('Untitled')).'</a></cite></span>';

                            if ($description = metadata($file, array( 'Dublin Core', 'Description' ))) {
                                $caption[]= '<span class="file-description">'.strip_tags($description, '<a><u><strong><em><i><cite>').'</span>';
                            }

                            if ($source = metadata($file, array( 'Dublin Core', 'Source' ))) {
                                $caption[]= '<span class="file-source"><span>'.__('Source').'</span>: '.$source.'</span>';
                            }

                            if ($creator = metadata($file, array( 'Dublin Core', 'Creator' ))) {
                                $caption[]= '<span class="file-creator"><span>'.__('Creator').'</span>: '.$creator.'</span>';
                            }

                            if ($date = metadata($file, array( 'Dublin Core', 'Date' ))) {
                                $caption[]= '<span class="file-date"><span>'.__('Date').'</span>: '.$date.'</span>';
                            }

                            if (count($caption)) {
                                return implode(' ', $caption);
                            }
                        }

                        /*
                        ** Loop through and display audio/video files
                        */
                        function rl_streaming_files($filesArray=null, $type=null, $openFirst=false)
                        {
                            $html=null;
                            $index=0;
                            $videoTypes = array('video/mp4','video/mpeg','video/quicktime'); // @todo: in_array($file['mime'],$videoTypes)
                        $audioTypes = array('audio/mp3'); // @todo: in_array($file['mime'],$videoTypes)
                        foreach ($filesArray as $file) {
                            $index++;
                            $html.='<div>';
                            $html.='<div class="media-player '.$type.' '.($openFirst && $index==1 ? 'active' : '').'" data-type="'.$type.'" data-index="'.$index.'" data-src="'.WEB_ROOT.'/files/original/'.$file['src'].'">';
                            if ($type == 'audio') {
                                $html.='<audio controls preload="auto">
                                    <p class="media-no-support">'.__('Your web browser does not support HTML5 audio').'</p>
                                    <source src="'.WEB_ROOT.'/files/original/'.$file['src'].'" type="audio/mp3">
                                </audio>';
                            } elseif ($type="video") {
                                $html.='<video playsinline controls preload="auto">
                                    <source src="'.WEB_ROOT.'/files/original/'.$file['src'].'" type="video/mp4">
                                    <p class="media-no-support">'.__('Your web browser does not support HTML5 video').'</p>
                                </video>';
                            }
                            $html .='</div>';
                            $html.='<div class="media-select">';
                            $html.='<div class="media-thumb"><a tabindex="0" data-type="'.$type.'" data-index="'.$index.'" title="play" class="button icon-round media-button"></a></div>';
                            $html.='<div class="media-caption">'.$file['caption'].'</div>';
                            $html.='</div>';
                            $html.='</div>';
                        };
                            if ($html): ?>
                        <figure id="item-media" class="<?php echo $type; ?>">
                            <div class="media-container">
                                <div class="media-list">
                                    <?php echo $html; ?>
                                </div>
                            </div>
                        </figure>
                        <?php endif;
                        }

/*
** loop through and display DOCUMENT files other than the supported audio, video, and image types
*/
function rl_document_files($files=array())
{
    $html=null;
    foreach ($files as $file) {
        $src=WEB_ROOT.'/files/original/'.$file['src'];
        $extension=pathinfo($src, PATHINFO_EXTENSION);
        $size=formatSizeUnits($file['size']);
        $title = $file['title'] ? $file['title'] : $file['filename'];

        $html .= '<tr>';
        $html .= '<td class="title"><a title="'.__('View File Details').'" href="/files/show/'.$file['id'].'">'.$title.'</a></td>';
        $html .= '<td class="info"><span>'.$extension.'</span> / '.$size.'</td>';
        $html .= '<td class="download"><a class="button" target="_blank" href="'.$src.'"><i class="fa fa-download" aria-hidden="true"></i><span>Download</span></a></td>';
        $html .= '</tr>';
    }
    if ($html) {
        echo '<figure id="item-documents">';
        echo '<table><tbody><tr><th>Name</th><th>Info</th><th>Actions</th></tr>'.$html.'</tbody></table>';
        echo '</figure>';
    }
}
/*
** display single file in FILE TEMPLATE
*/

function rl_single_file_show($file=null)
{
    $html=null;
    $mime = metadata($file, 'MIME Type');
    $img = array('image/jpeg','image/jpg','image/png','image/jpeg','image/gif');
    $audioTypes = array('audio/mpeg');
    $videoTypes = array('video/mp4','video/mpeg','video/quicktime');


    // SINGLE AUDIO FILE
    if (array_search($mime, $audioTypes) !== false) {
        ?>
                        <figure id="item-audio">
                            <div class="media-container audio">
                                <audio src="<?php echo file_display_url($file, 'original'); ?>" id="curatescape-player-audio" class="video-js" controls preload="auto">
                                    <p class="media-no-js">To listen to this audio please consider upgrading to a web browser that supports HTML5 audio</p>
                                </audio>
                            </div>
                        </figure>
                        <?php


        // SINGLE VIDEO FILE
    } elseif (array_search($mime, $videoTypes) !== false) {
        $videoTypes = array('video/mp4','video/mpeg','video/quicktime');
        $videoFile = file_display_url($file, 'original');
        $videoTitle = metadata($file, array('Dublin Core', 'Title'));
        $videoDesc = rl_file_caption($file, false);
        $videoTitle = metadata($file, array('Dublin Core','Title'));
        $embeddable=embeddableVersion($file, $videoTitle, $videoDesc, array('Dublin Core','Relation'), false);
        if ($embeddable) {
            // If a video has an embeddable streaming version, use it.
            $html.= $embeddable;
        } else {
            $html .= '<div class="item-file-container">';
            $html .= '<video width="725" height="410" controls preload="auto" data-setup="{}">';
            $html .= '<source src="'.$videoFile.'" type="video/mp4">';
            $html .= '<p class="media-no-js">To listen to this audio please consider upgrading to a web browser that supports HTML5 video</p>';
            $html .= '</video>';
        }

        return $html;

    // SINGLE IMAGE OR OTHER FILE
    } else {
        return file_markup($file, array('imageSize'=>'fullsize'));
    }
}
/*
** display additional (non-core) file metadata in FILE TEMPLATE
*/
function rl_file_metadata_additional($file='file', $html=null)
{
    $fields = all_element_texts($file, array('return_type'=>'array','show_element_sets'=>'Dublin Core'));

    if ($fields['Dublin Core']) {

        // Omit Primary DC Fields
        $dc = array_filter($fields['Dublin Core'], function ($key) {
            $omit=array('Description','Title','Creator','Date','Rights','Source');
            return !(in_array($key, $omit));
        }, ARRAY_FILTER_USE_KEY);

        // Output
        foreach ($dc as $dcname=>$values) {
            $html.='<div class="additional-element">';
            $html.='<h4 class="additional-element-name">'.$dcname.'</h4>';
            $html.='<div class="additional-element-value-container">';
            foreach ($values as $value) {
                $html.='<div class="additional-element-value">'.$value.'</div>';
            }
            $html.='</div>';
            $html.='</div>';
        }
    }

    if ($html) {
        echo '<h3>'.__('Additional Information').'</h3>';
        echo '<div class="additional-elements">'.$html.'</div>';
    }
}

/*
** Checks file metadata record for embeddable version of video file
** Because YouTube and Vimeo have better compression, etc.
** returns string $html | false
*/
function embeddableVersion($file, $title=null, $desc=null, $field=array('Dublin Core','Relation'), $caption=true)
{
    $youtube= (strpos(metadata($file, $field), 'youtube.com')) ? metadata($file, $field) : false;
    $youtube_shortlink= (strpos(metadata($file, $field), 'youtu.be')) ? metadata($file, $field) : false;
    $vimeo= (strpos(metadata($file, $field), 'vimeo.com')) ? metadata($file, $field) : false;

    if ($youtube) {
        // assumes YouTube links look like https://www.youtube.com/watch?v=NW03FB274jg where the v query contains the video identifier
        $url=parse_url($youtube);
        $id=str_replace('v=', '', $url['query']);
        $html= '<div class="embed-container youtube" id="v-streaming" style="position: relative;padding-bottom: 56.25%;height: 0; overflow: hidden;"><iframe style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;" src="//www.youtube.com/embed/'.$id.'" frameborder="0" width="725" height="410" allowfullscreen></iframe></div>';
        if ($caption==true) {
            $html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
            $html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file, 'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>', array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
        }
        return '<div class="item-file-container">'.$html.'</div>';
    } elseif ($youtube_shortlink) {
        // assumes YouTube links look like https://www.youtu.be/NW03FB274jg where the path string contains the video identifier
        $url=parse_url($youtube_shortlink);
        $id=$url['path'];
        $html= '<div class="embed-container youtube" id="v-streaming" style="position: relative;padding-bottom: 56.25%;height: 0; overflow: hidden;"><iframe style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;" src="//www.youtube.com/embed/'.$id.'" frameborder="0" width="725" height="410" allowfullscreen></iframe></div>';
        if ($caption==true) {
            $html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
            $html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file, 'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>', array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
        }
        return '<div class="item-file-container">'.$html.'</div>';
    } elseif ($vimeo) {
        // assumes the Vimeo links look like http://vimeo.com/78254514 where the path string contains the video identifier
        $url=parse_url($vimeo);
        $id=$url['path'];
        $html= '<div class="embed-container vimeo" id="v-streaming" style="padding-top:0; height: 0; padding-top: 25px; padding-bottom: 67.5%; margin-bottom: 10px; position: relative; overflow: hidden;"><iframe style=" top: 0; left: 0; width: 100%; height: 100%; position: absolute;" src="//player.vimeo.com/video'.$id.'?color=222" width="725" height="410" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>';
        if ($caption==true) {
            $html .= ($title) ? '<h4 class="title video-title sib">'.$title.' <span class="icon-info-sign" aria-hidden="true"></span></h4>' : '';
            $html .= ($desc) ? '<p class="description video-description sib">'.$desc.link_to($file, 'show', '<span class="view-file-link"><span class="icon-file" aria-hidden="true"></span> '.__('View File Details Page').'</span>', array('class'=>'view-file-record','rel'=>'nofollow')).'</p>' : '';
        }
        return '<div class="item-file-container">'.$html.'</div>';
    } else {
        return false;
    }
}


/*
** DISQUS COMMENTS
** disqus.com
*/
function rl_disquss_comments($shortname)
{
    if ($shortname) {
        ?>
                        <div id="disqus_thread" class="inner-padding max-content-width">
                            <a class="load-comments button button-wide" title="Click to load the comments section" href="javascript:void(0)" onclick="disqus();return false;"><i aria-hidden="true" class="fa fa-comments"></i>Show Comments</a>
                        </div>
                        <script async defer>
                        var disqus_shortname = "<?php echo $shortname; ?>";
                        var disqus_loaded = false;

                        function disqus() {
                            if (!disqus_loaded) {
                                disqus_loaded = true;
                                var e = document.createElement("script");
                                e.type = "text/javascript";
                                e.async = true;
                                e.src = "//" + disqus_shortname + ".disqus.com/embed.js";
                                (document.getElementsByTagName("head")[0] ||
                                    document.getElementsByTagName("body")[0])
                                .appendChild(e);
                            }
                        }
                        </script>
                        <?php
    }
}

/*
** DISPLAY COMMENTS
*/
function rl_display_comments()
{
    if (get_theme_option('comments_id')) {
        return rl_disquss_comments(get_theme_option('comments_id'));
    } else {
        return null;
    }
}

/*
** Get total tour items, omitting unpublished items unless logged in
*/
function rl_tour_total_items($tour)
{
    $i=0;
    foreach ($tour->Items as $ti) {
        if ($ti->public || current_user()) {
            $i++;
        }
    }
    return $i;
}

/*
** Display the Tours search results
*/
function rl_tour_preview($s)
{
    $html=null;
    $record=get_record_by_id($s['record_type'], $s['record_id']);
    set_current_record('tour', $record);
    $html.=  '<article>';
    $html.=  '<h3 class="tour-result-title"><a href="'.record_url($record, 'show').'">'.($s['title'] ? $s['title'] : '[Unknown]').'</a></h3>';
    $html.=  '<div class="tour-meta-browse browse-meta-top byline">';
    $html.= '<span class="total">'.rl_tour_total_items($record).' '.__('Locations').'</span> ~ ';
    if (tour('Credits')) {
        $html.=  __('%1s curated by %2s', rl_tour_label('singular'), tour('Credits'));
    } else {
        $html.=  __('%1s curated by %2s', rl_tour_label('singular'), option('site_title'));
    }
    $html.=  '</div>';
    $html.=  ($text=strip_tags(html_entity_decode(tour('Description')))) ? '<span class="tour-result-snippet">'.snippet($text, 0, 300).'</span>' : null;
    if (get_theme_option('show_tour_item_thumbs') == true) {
        $html.=  '<span class="tour-thumbs-container">';
        foreach ($record->Items as $mini_thumb) {
            $html.=  metadata($mini_thumb, 'has thumbnail') ?
            '<div class="mini-thumb">'.item_image('square_thumbnail', array('height'=>'40','width'=>'40'), null, $mini_thumb).'</div>' :
            null;
        }
        $html.=  '</span>';
    }
    $html.= '</article>';
    return $html;
}


/*
** Display the Tours list
*/
function rl_display_homepage_tours($num=5, $scope='featured')
{
    $scope=get_theme_option('homepage_tours_scope') ? get_theme_option('homepage_tours_scope') : $scope;

    // Get the database.
    $db = get_db();

    // Get the Tour table.
    $table = $db->getTable('Tour');

    // Build the select query.
    $select = $table->getSelect();
    $select->where('public = 1');

    // Get total count
    $public = $table->fetchObjects($select);

    // Continue, get scope
    switch ($scope) {
        case 'random':
            $select->from(array(), 'RAND() as rand');
            break;
        case 'featured':
            $select->where('featured = 1');
            break;
    }


    // Fetch some items with our select.
    $items = $table->fetchObjects($select);
    $customheader=get_theme_option('tour_header');
    if ($scope=='random') {
        shuffle($items);
        $heading = $customheader ? $customheader : __('Take a').' '.rl_tour_label('singular');
    } else {
        $heading = $customheader ? $customheader : ucfirst($scope).' '.rl_tour_label('plural');
    }
    $num = (count($items)<$num) ? count($items) : $num;
    $html=null;



    $html .= '<h3 class="result-type-header">'.$heading.'</h3>';
    if ($items) {
        for ($i = 0; $i < $num; $i++) {
            set_current_record('tour', $items[$i]);
            $tour=get_current_tour();


            if (tour('credits')) {
                $byline= __('Curated by %s', tour('credits'));
            } else {
                $byline= __('Curated by %s', option('site_title'));
            }

            $html .= '<article class="item-result '.(get_theme_option('fetch_tour_images') ? 'fetch-tour-image' : null).'" data-tour-id="'.tour('id').'">';
            $html .= get_theme_option('fetch_tour_images') ? '<div class="tour-image-container"></div>' : null;
            $html .= '<div><h3 class="home-tour-title"><a href="' . WEB_ROOT . '/tours/show/'. tour('id').'">' . tour('title').'</a></h3><span class="total">'.__('%s Locations', rl_tour_total_items($tour)).'</span> ~ <span>'.$byline.'</span></div>';
            $html .= '</article>';
        }
        if (count($public)>1) {
            $html .= '<p class="view-more-link"><a class="button" href="'.WEB_ROOT.'/tours/browse/">'.__('Browse all <span>%1$s %2$s</span>', count($public), rl_tour_label('plural')).'</a></p>';
        }
    } else {
        $html .= '<p>'.__('No tours are available. Publish some now.').'</p>';
    }

    return $html;
}

// return story navigation and (when applicable) tour navigation
function rl_story_nav($has_images=0, $has_audio=0, $has_video=0, $has_other=0, $has_location=false, $tour=false, $tour_index=false)
{
    $totop = '<li class="foot"><a title="'.__('Return to Top').'" class="icon-capsule no-bg" href="#site-content">'.rl_icon("arrow-up").'<span class="label">'.__('Top').'</span></a></li>';

    // Media List HTML
    $media_list = null;
    if ($has_video) {
        $media_list .= '<li><a title="'.__('Skip to %s', __('Video')).'" class="icon-capsule" href="#video">'.rl_icon("film").'<span class="label">'.__('Video').' ('.$has_video.')</span></a></li>';
    }
    if ($has_audio) {
        $media_list .= '<li><a title="'.__('Skip to %s', __('Audio')).'" class="icon-capsule" href="#audio">'.rl_icon("headset").'<span class="label">'.__('Audio').' ('.$has_audio.')</span></a></li>';
    }
    if ($has_images) {
        $media_list .= '<li><a title="'.__('Skip to %s', __('Images')).'" class="icon-capsule" href="#images">'.rl_icon("images").'<span class="label">'.__('Images').' ('.$has_images.')</span></a></li>';
    }
    if ($has_other) {
        $media_list .= '<li><a title="'.__('Skip to %s', __('Documents')).'" class="icon-capsule" href="#documents">'.rl_icon("documents").'<span class="label">'.__('Documents').' ('.$has_other.')</span></a></li>';
    }

    $tournav = null;
    if ($tour && isset($tour_index)) {
        $index = $tour_index;
        $tour_id = $tour;
        $tour = get_record_by_id('tour', $tour_id);
        $prevIndex = $index -1;
        $nextIndex = $index +1;
        $tourTitle = metadata($tour, 'title');
        $tourURL = html_escape(public_url('tours/show/'.$tour_id));

        $current = tour_item_id($tour, $index);
        $next = tour_item_id($tour, $nextIndex);
        $prev = tour_item_id($tour, $prevIndex);

        $tournav .= '<ul class="tour-nav">';
        $tournav .= '<li class="head"><a title="'.__('%s Navigation', rl_tour_label('singular')).'" class="icon-capsule label" href="javascript:void(0)">'.rl_icon("list").'<span class="label">'.__('%s Navigation', rl_tour_label('singular')).'</span></a></li>';
        $tournav .= $prev ? '<li><a title="'.__('Previous Loction').'" class="icon-capsule" href="'.public_url("items/show/$prev?tour=$tour_id&index=$prevIndex").'">'.rl_icon("arrow-back").'<span class="label">'.__('Previous').'</span></a></li>' : null;
        $tournav .= '<li class="info"><a title="'.__('%s Info', rl_tour_label('singular')).': '.$tourTitle.'" class="icon-capsule" href="'.$tourURL.'">'.rl_icon("compass").'<span class="label">'.__('%s Info', rl_tour_label('singular')).'</span></a></li>';
        $tournav .= $next ? '<li><a title="'.__('Next Location').'" class="icon-capsule" href="'.public_url("items/show/$next?tour=$tour_id&index=$nextIndex").'">'.rl_icon("arrow-forward").'<span class="label">'.__('Next').'</span></a></li>' : null;
        $tournav .= '</ul>';
    }

    // Location HTML
    $location = null;
    if ($has_location && plugin_is_active('Geolocation')) {
        $location .= '<li><a title="'.__('Skip to %s', __('Map Location')).'" class="icon-capsule" href="#map-section">'.rl_icon("location").'<span class="label">'.__('Location').'</span></a></li>';
    }

    // Output HTML
    $html .= '<nav class="rl-toc"><ul>'.
    '<li class="head"><a title="'.__('%s Contents', rl_item_label('singular')).'" class="icon-capsule label" href="javascript:void(0)">'.rl_icon("list").'<span class="label">'.__('%s Contents', rl_item_label('singular')).'</span></a></li>'.
    '<li><a title="'.__('Skip to Main Text').'" class="icon-capsule" href="#text-section">'.rl_icon("book").'<span class="label">'.__('Main Text').'</span></a></li>'.
    $media_list.
    $location.
    '<li><a title="'.__('Skip to %s', __('Metadata')).'" class="icon-capsule" href="#metadata-section">'.rl_icon("pricetags").'<span class="label">'.__('Metadata').'</span></a></li>'.
    $totop.
    '</ul>'.$tournav.'</nav>';

    return $html;
}

// an array of files for the item, sorted by type
function rl_item_files_by_type($item=null, $output=null)
{
    $output=array(
        'images'=>array(),
        'audio'=>array(),
        'video'=>array(),
        'other'=>array()
    );

    if (metadata($item, 'has files')) {
        foreach (loop('files', $item->Files) as $file) {
            $mime = $file->mime_type;
            switch ($mime) {
                case strpos($mime, 'image') !== false:
                $src=str_ireplace(array('.JPG','.jpeg','.JPEG','.png','.PNG','.gif','.GIF', '.bmp','.BMP'), '.jpg', $file->filename);
                $size=getimagesize(WEB_ROOT.'/files/fullsize/'.$src);
                $orientation = $size[0] > $size[1] ? 'landscape' : 'portrait';
                array_push(
                    $output['images'],
                    array(
                         'title'=>metadata($file, array('Dublin Core','Title')),
                         'id'=>$file->id,
                         'src'=>$src,
                         'caption'=>rl_file_caption($file),
                         'size'=>array($size[0],$size[1]),
                         'orientation'=>$orientation
                     )
                );
                break;
                case strpos($mime, 'audio') !== false:
                array_push($output['audio'], array('id'=>$file->id, 'src'=>$file->filename,'caption'=>rl_file_caption($file)));
                break;
                case strpos($mime, 'video') !== false:
                array_push($output['video'], array('id'=>$file->id, 'src'=>$file->filename,'caption'=>rl_file_caption($file)));
                break;
                default:
                array_push($output['other'], array('id'=>$file->id, 'src'=>$file->filename,'size'=>$file->size,'title'=>metadata($file, array('Dublin Core','Title')),'filename'=>$file->original_filename));
            }
        }
    }
    return $output;
}

/*
These images load via js unless the $class is set to "featured" (i.e. in header)
Should be used with rl_nojs_images() for users w/o js
*/
function rl_gallery_figure($image=null, $class=null, $hrefOverride=null)
{
    if ($image['src']) {
        $src = WEB_ROOT.'/files/fullsize/'.$image['src'];
        $url = WEB_ROOT.'/files/show/'.$image['id'];
        $data_or_style_attr = $class == 'featured' ? 'style' : 'data-style';
        return '<figure class="image-figure '.$class.'" itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
		<a itemprop="contentUrl" aria-label="Image: '.$image['title'].'" href="'.($hrefOverride ? $hrefOverride : $src).'" class="gallery-image '.$image['orientation'].' file-'.$image['id'].'" '.$data_or_style_attr.'="background-image:url('.$src.')" data-pswp-width="'.$image['size'][0].'" data-pswp-height="'.$image['size'][1].'"></a>
		<figcaption>'.$image['caption'].'</figcaption></figure>';
    }
}

/*
These fallback styles load in a <noscript> tag
*/
function rl_nojs_images($images=array(), $css=null)
{
    foreach ($images as $img) {
        $css .= '.file-'.$img['id'].'{background-image:url('.WEB_ROOT.'/files/fullsize/'.$img['src'].');}';
    }
    return '<style>'.$css.'</style>';
}

function rl_hero_item($item)
{
    $itemTitle = rl_the_title_expanded($item);
    $itemDescription = rl_snippet_expanded($item);
    $class=get_theme_option('featured_tint')==1 ? 'tint' : 'no-tint';
    $html=null;

    if (metadata($item, 'has thumbnail')) {
        $img_markup=item_image('fullsize', array(), 0, $item);
        preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $img_markup, $result);
        $img_url = array_pop($result);
        $html .= '<article class="featured-story-result '.$class.'">';
        $html .= '<div class="featured-decora-outer">' ;
        $html .= '<div class="featured-decora-bg" style="background-image:url('.$img_url.')">' ;

        $html .= '<div class="featured-decora-text"><div class="featured-decora-text-inner">';
        $html .= '<header><h3>' . link_to_item($itemTitle, array(), 'show', $item) . '</h3><span class="featured-item-author">'.rl_the_byline($item, false).'</span></header>';
        if ($itemDescription) {
            $html .= '<div class="item-description">' . strip_tags($itemDescription) . '</div>';
        } else {
            $html .= '<div class="item-description">'.__('Preview text not available.').'</div>';
        }

        $html .= '</div></div>' ;

        $html .= '</div></div>' ;
        $html .= '</article>';
    }

    return $html;
}

/*
** Display random featured item(s)
*/
function rl_display_random_featured_item($withImage=false, $num=1)
{
    $featuredItems = get_random_featured_items($num, $withImage);
    $html = '<h3 class="result-type-header">'.__('Featured %s', rl_item_label('plural')).'</h3>';

    if ($featuredItems) {
        foreach ($featuredItems as $item):
            $html .=rl_hero_item($item);
        endforeach;

        $html.='<p class="view-more-link"><a class="button" href="/items/browse?featured=1">'.__('Browse Featured %s', rl_item_label('plural')).'</a></p>';
    } else {
        $html .= '<article class="featured-story-result none">';
        $html .= '<p>'.__('No featured items are available. Publish some now.').'</p>';
        $html .= '</article>';
    }

    return $html;
}


/*
** Display the customizable "About" content on homepage
*/
function rl_home_about($length=800, $html=null)
{
    $html .= '<div class="about-text">';
    $html .= '<article>';

    $html .= '<header>';
    $html .= '<h2>'.option('site_title').'</h2>';
    $html .= '<span class="sponsor">'.__('A project by').' <span class="sponsor-name">'.rl_owner_link().'</span></span>';
    $html .= '</header>';

    $html .= '<div class="about-main"><p>';
    $html .= substr(rl_about(), 0, $length);
    $html .= ($length < strlen(rl_about())) ? '... ' : null;
    $html .= '</p><a class="button u-full-width" href="'.url('about').'">'.__('Read more About Us').'</a></div>';

    $html .= '</article>';
    $html .= '</div>';

    return $html;
}

/*
** Display the customizable "Call to Action" content on homepage
*/
function rl_home_cta($html=null)
{
    $cta_title=get_theme_option('cta_title');
    $cta_text=get_theme_option('cta_text');
    $cta_img_src=get_theme_option('cta_img_src');
    $cta_button_label=get_theme_option('cta_button_label');
    $cta_button_url=get_theme_option('cta_button_url');
    $cta_button_url_target=get_theme_option('cta_button_url_target') ? ' target="_blank" rel="noreferrer noopener"' : null;

    if ($cta_title && $cta_button_label && $cta_button_url) {
        $html .='<h3 class="result-type-header">'.$cta_title.'</h3>';

        $html .= '<div class="cta-inner">';
        $html .= '<article style="background-image:url(/files/theme_uploads/'.$cta_img_src.');">';
        if ($cta_img_src) {
            $html .= '<div class="cta-hero">';
            $html .= '<a class="button button-primary" href="'.$cta_button_url.'" '.$cta_button_url_target.'>'.$cta_button_label.'</a>';
            $html .= '</div>';
        }
        if ($cta_text) {
            $html .= '<div class="cta-description">';
            $html .= '<p>';
            $html .= $cta_text;
            $html .= '</p>';
            $html .= '<a class="button" href="'.$cta_button_url.'" '.$cta_button_url_target.'>'.$cta_button_label.'</a>';
            $html .= '</div>';
        }
        $html .= '</article>';
        $html .= '</div>';

        return $html;
    }
}

function rl_footer_cta($html=null)
{
    $footer_cta_button_label=get_theme_option('footer_cta_button_label');
    $footer_cta_button_url=get_theme_option('footer_cta_button_url');
    $footer_cta_button_target=get_theme_option('footer_cta_button_target') ? 'target="_blank" rel="noreferrer noopener"' : null;
    if ($footer_cta_button_label && $footer_cta_button_url) {
        $html.= '<div class="footer_cta"><a class="button button-primary" href="'.$footer_cta_button_url.'" '.$footer_cta_button_target.'>'.$footer_cta_button_label.'</a></div>';
    }
    return $html;
}

/*
** Tag cloud for homepage
*/
function rl_home_popular_tags($num=40)
{
    $tags=get_records('Tag', array('sort_field' => 'count', 'sort_dir' => 'd'), $num);
    $html = '<h3 class="result-type-header">'.__('Popular Tags').'</h3>';
    $html.=tag_cloud($tags, url('items/browse'));
    $html.='<p class="view-more-link"><a class="button" href="/items/tags/">'.__('Browse all %s tags', total_records('Tags')).'</a></p>';
    return $html;
}



/*
** List of recent or random items for homepage
*/
function rl_home_item_list()
{
    return rl_random_or_recent(($mode=get_theme_option('random_or_recent')) ? $mode : 'recent');
}

/*
** Build an array of social media links (including icons) from theme settings
*/
function rl_social_array($max=5)
{
    $services=array();
    ($email=get_theme_option('contact_email') ? get_theme_option('contact_email') : get_option('administrator_email')) ? array_push($services, '<a target="_blank" rel="noopener" title="email" href="mailto:'.$email.'" class="button social icon-round email">'.rl_icon("mail").'</i></a>') : null;
    ($facebook=get_theme_option('facebook_link')) ? array_push($services, '<a target="_blank" rel="noopener" title="facebook" href="'.$facebook.'" class="button social icon-round facebook">'.rl_icon("logo-facebook", null).'</a>') : null;
    ($twitter=get_theme_option('twitter_username')) ? array_push($services, '<a target="_blank" rel="noopener" title="twitter" href="https://twitter.com/'.$twitter.'" class="button social icon-round twitter">'.rl_icon("logo-twitter", null).'</a>') : null;
    ($youtube=get_theme_option('youtube_username')) ? array_push($services, '<a target="_blank" rel="noopener" title="youtube" href="'.$youtube.'" class="button social icon-round youtube">'.rl_icon("logo-youtube", null).'</a>') : null;
    ($instagram=get_theme_option('instagram_username')) ? array_push($services, '<a target="_blank" rel="noopener" title="instagram" href="https://www.instagram.com/'.$instagram.'" class="button social icon-round instagram">'.rl_icon("logo-instagram", null).'</a>') : null;
    ($pinterest=get_theme_option('pinterest_username')) ? array_push($services, '<a target="_blank" rel="noopener" title="pinterest" href="https://www.pinterest.com/'.$pinterest.'" class="button social icon-round pinterest">'.rl_icon("logo-pinterest", null).'</a>') : null;
    ($tumblr=get_theme_option('tumblr_link')) ? array_push($services, '<a target="_blank" rel="noopener" title="tumblr" href="'.$tumblr.'" class="button social icon-round tumblr">'.rl_icon("logo-tumblr", null).'</a>') : null;
    ($reddit=get_theme_option('reddit_link')) ? array_push($services, '<a target="_blank" rel="noopener" title="reddit" href="'.$reddit.'" class="button social icon-round reddit">'.rl_icon("logo-reddit", null).'</a>') : null;

    if (($total=count($services)) > 0) {
        if ($total>$max) {
            for ($i=$total; $i>($max-1); $i--) {
                unset($services[$i]);
            }
        }
        return $services;
    } else {
        return false;
    }
}

/*
** Build a series of social media icon links for the footer
*/
function rl_find_us($class=null, $max=9)
{
    if ($services=rl_social_array($max)) {
        return '<div class="link-icons '.$class.'">'.implode(' ', $services).'</div>';
    }
}

/*
** Build a series of icon action buttons for the story (i.e. print/share)
** @todo: https://css-tricks.com/simple-social-sharing-links/
*/
function rl_story_actions($class=null, $title=null, $id=null)
{
    $url=WEB_ROOT.'/items/show/'.$id;
    $actions = array(
        '<a rel="noopener" title="print" href="javascript:void" onclick="window.print();" class="button social icon-round">'.rl_icon("print").'</a>',
        '<a target="_blank" rel="noopener" title="email" href="mailto:?subject='.$title.'&body='.$url.'" class="button social icon-round">'.rl_icon("mail").'</a>',
        '<a target="_blank" rel="noopener" title="facebook" href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($url).'" class="button social icon-round">'.rl_icon("logo-facebook", null).'</a>',
        '<a target="_blank" rel="noopener" title="twitter" href="https://twitter.com/intent/tweet?text='.urlencode($url).'" class="button social icon-round">'.rl_icon("logo-twitter", null).'</a>'
    );
    return '<div class="link-icons '.$class.'">'.implode(' ', $actions).'</div>';
}


/*
** Build a link for the footer copyright statement and credit line on homepage
*/
function rl_owner_link()
{
    $fallback=(option('author')) ? option('author') : option('site_title');

    $authname=(get_theme_option('sponsor_name')) ? get_theme_option('sponsor_name') : $fallback;

    return $authname;
}


/*
** Build HTML content for homepage widget sections
** Each widget can be used ONLY ONCE
*/

function homepage_widget_1($content='recent_or_random')
{
    get_theme_option('widget_section_1') ? $content=get_theme_option('widget_section_1') : null;

    return $content;
}

function homepage_widget_2($content='featured')
{
    get_theme_option('widget_section_2') ? $content=get_theme_option('widget_section_2') : null;

    return $content;
}

function homepage_widget_3($content='tours')
{
    get_theme_option('widget_section_3') ? $content=get_theme_option('widget_section_3') : null;

    return $content;
}
function homepage_widget_4($content='about')
{
    get_theme_option('widget_section_4') ? $content=get_theme_option('widget_section_4') : null;

    return $content;
}

function homepage_widget_sections()
{
    $html=null;
    $recent_or_random=0;
    $tours=0;
    $featured=0;
    $popular_tags=0;
    $about=0;
    $meta=0;
    $cta=0;

    foreach (array(homepage_widget_1(),homepage_widget_2(),homepage_widget_3(),homepage_widget_4()) as $setting) {
        switch ($setting) {
                case 'featured':
                    $html.= ($featured==0) ? '<section id="featured-stories">'.rl_display_random_featured_item(true, 3).'</section>' : null;
                    $featured++;
                    break;
                case 'tours':
                    $html.= ($tours==0) ? '<section id="home-tours">'.rl_display_homepage_tours().'</section>' : null;
                    $tours++;
                    break;
                case 'recent_or_random':
                    $html.= ($recent_or_random==0) ? '<section id="home-item-list">'.rl_home_item_list().'</section>' : null;
                    $recent_or_random++;
                    break;
                case 'popular_tags':
                    $html.= ($popular_tags==0) ? '<section id="home-popular-tags">'.rl_home_popular_tags().'</section>' : null;
                    $popular_tags++;
                    break;
                case 'about':
                    $html.= ($about==0) ? '<section id="about">'.rl_home_about().'</section>	' : null;
                    $about++;
                    break;
                case 'cta':
                    $html.= ($cta==0) ? '<section id="cta">'.rl_home_cta().'</section>	' : null;
                    $cta++;
                    break;
                case 'custom_meta_img':
                    $html.= ($meta==0) ? '<section id="custom-meta-img" aria-hidden="true"><img src="'.rl_seo_pageimg_custom().'" alt="" class="homepage-brand-image"></section>	' : null;
                    $meta++;
                    break;
                default:
                    $html.=null;
            }
    }

    return $html;
}


/*
** Get recent/random items for use in mobile slideshow on homepage
*/
function rl_random_or_recent($mode='recent', $num=6)
{
    switch ($mode) {

    case 'random':
        $items=get_records('Item', array('hasImage'=>true,'sort_field' => 'random', 'sort_dir' => 'd','public'=>true), $num);;
        $param="Random";
        break;
    case 'recent':
        $items=get_records('Item', array('hasImage'=>true,'sort_field' => 'added', 'sort_dir' => 'd','public'=>true), $num);
        $param="Recent";
        break;

    }
    set_loop_records('items', $items);
    $html='<section id="random-recent">';
    $labelcount='<span>'.total_records('Item').' '.rl_item_label('plural').'</span>';
    $html.='<h3 class="result-type-header">'.ucfirst($mode).' '.rl_item_label('plural').'</h3>';

    if (has_loop_records('items')) {
        $html.='<div class="browse-items flex">';
        foreach (loop('Items') as $item) {
            $item_image=null;
            $description = rl_snippet_expanded($item);
            $tags=tag_string(get_current_record('item'), url('items/browse'));
            $titlelink=link_to_item(rl_the_title_expanded($item), array('class'=>'permalink'));
            $hasImage=metadata($item, 'has thumbnail');
            if ($hasImage) {
                preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', item_image('fullsize'), $result);
                $item_image = array_pop($result);
            }

            $html.='<article class="item-result'.($hasImage ? ' has-image' : null).'">';
            $html.=(isset($item_image) ? link_to_item('<span class="item-image" style="background-image:url('.$item_image.');" role="img" aria-label="'.metadata($item, array('Dublin Core', 'Title')).'"></span>', array('title'=>metadata($item, array('Dublin Core','Title')))) : null);
            $html.='<h3>'.$titlelink.'</h3>';
            $html.='<div class="browse-meta-top">'.rl_the_byline($item, false).'</div>';


            if ($description) {
                $html.='<div class="item-description">';
                $html.=strip_tags($description);
                $html.='</div>';
            }

            $html.='</article> ';
        }

        $html.='</div>';
        $html.='<p class="view-more-link"><a class="button" href="/items/browse/">'.__('Browse all %s', $labelcount).'</a></p>';
    } else {
        $html.='<p>'.__('No items are available. Publish some now.').'</p>';
    }
    $html.='</section>';
    return $html;
}
/*
** Icon file for mobile devices
*/
function rl_browser_icon_url()
{
    $apple_icon_logo = get_theme_option('apple_icon_144');

    $logo_img = $apple_icon_logo ? WEB_ROOT.'/files/theme_uploads/'.$apple_icon_logo : img('favicon.png');

    return $logo_img;
}


/*
** Background image
*/
function rl_bg_url()
{
    $bg_image = get_theme_option('bg_img');

    $img_url = $bg_image ? WEB_ROOT.'/files/theme_uploads/'.$bg_image : null;

    return $img_url;
}

/*
** Custom link color - Primary
*/
function rl_link_color()
{
    $color = get_theme_option('link_color');

    if (($color) && (preg_match('/^#[a-f0-9]{6}$/i', $color))) {
        return $color;
    }
}

/*
** Custom link color - Secondary
*/
function rl_secondary_link_color()
{
    $color = get_theme_option('secondary_link_color');

    if (($color) && (preg_match('/^#[a-f0-9]{6}$/i', $color))) {
        return $color;
    }
}
/*
** Custom CSS
*/
function rl_configured_css($vars=null, $output=null)
{
    $vars .= get_theme_option('link_color')
        ? '--link-text:'.get_theme_option('link_color').';'
        : null;
    $vars .= get_theme_option('link_color_hover')
        ? '--link-text-hover:'.get_theme_option('link_color_hover').';'
        : null;
    $vars .= get_theme_option('secondary_link_color')
        ? '--link-text-on-dark:'.get_theme_option('secondary_link_color').';'
        : null;
    $vars .= get_theme_option('secondary_link_color_hover')
        ? '--link-text-on-dark-hover:'.get_theme_option('secondary_link_color_hover').';'
        : null;
    $vars .= get_theme_option('header_footer_color')
        ? '--site-header-bg-color-1:'.get_theme_option('header_footer_color').';'
        : null;
    $vars .= get_theme_option('secondary_header_footer_color')
        ? '--site-header-bg-color-2:'.get_theme_option('secondary_header_footer_color').';'
        : null;
    if ($vars) {
        $output .= ':root {'.$vars.'}';
    }
    if (get_theme_option('custom_css')) {
        $output .= get_theme_option('custom_css');
    }
    return $output;
}


/*
** Which fonts/service to use?
** Adobe/Typekit, FontDeck, Monotype, Google Fonts, or default (null)
*/
function rl_font_config()
{
    if ($tk=get_theme_option('typekit')) {
        $config="typekit: { id: '".$tk."' }";
    } elseif ($fd=get_theme_option('fontdeck')) {
        $config="fontdeck: { id: '".$fd."' }";
    } elseif ($fdc=get_theme_option('fonts_dot_com')) {
        $config="monotype: { projectId: '".$fdc."' }";
    } elseif ($gf=get_theme_option('google_fonts')) {
        $config="google: { families: [".$gf."] }";
    } else {
        $config=null;
    }
    return $config;
}


/*
** Load font service or default (see: fonts/fonts.css)
** Web Font Loader async script
** https://developers.google.com/fonts/docs/webfont_loader
*/
function rl_font_loader()
{
    if (rl_font_config()) { ?>
                        <script>
                        WebFontConfig = {
                            <?php echo rl_font_config(); ?>
                        };
                        (function(d) {
                            var wf = d.createElement('script'),
                                s = d.scripts[0];
                            wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js';
                            wf.async = true;
                            s.parentNode.insertBefore(wf, s);
                        })(document);
                        </script>
                        <?php
} else { ?>
                        <link rel="preconnect" href="https://fonts.googleapis.com">
                        <link href="<?php echo src('fonts.css', 'fonts');?>" media="all" rel="stylesheet">
                        <?php }
}

/*
** Google Analytics
** Theme option: google_analytics
** Accepts G- and UA- measurement IDs
*/
function rl_google_analytics()
{
    $id=get_theme_option('google_analytics');
    if ($id):
    if (substr($id, 0, 2) == 'G-'): ?>
                        <!-- GA -->
                        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $id; ?>"></script>
                        <script>
                        window.dataLayer = window.dataLayer || [];

                        function gtag() {
                            dataLayer.push(arguments);
                        }
                        gtag('js', new Date());
                        gtag('config', '<?php echo $id; ?>', {
                            cookie_flags: 'SameSite=None;Secure'
                        });
                        </script>
                        <?php elseif (substr($id, 0, 3) == 'UA-'): ?>
                        <!-- GA (Legacy) -->
                        <script>
                        var _gaq = _gaq || [];
                        _gaq.push(['_setAccount', '<?php echo $id; ?>']);
                        _gaq.push(['_trackPageview']);
                        (function() {
                            var ga = document.createElement('script');
                            ga.type = 'text/javascript';
                            ga.async = true;
                            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                            var s = document.getElementsByTagName('script')[0];
                            s.parentNode.insertBefore(ga, s);
                        })();
                        </script>
                        <?php endif;
    endif;
}

/*
** About text
*/
function rl_about($text=null)
{
    if (!$text) {
        // If the 'About Text' option has a value, use it. Otherwise, use default text
        $text = get_theme_option('about') ?
            strip_tags(get_theme_option('about'), '<a><em><i><cite><strong><b><u><br><img><video><iframe>') :
            __('%s is powered by <a href="http://omeka.org/">Omeka</a> + <a href="http://curatescape.org/">Curatescape</a>, a humanities-centered web and mobile framework available for both Android and iOS devices.', option('site_title'));
    }
    return $text;
}

/*
**
*/
function rl_license()
{
    $cc_license=get_theme_option('cc_license');
    $cc_version=get_theme_option('cc_version');
    $cc_jurisdiction=get_theme_option('cc_jurisdiction');
    $cc_readable=array(
        '1'=>'1.0',
        '2'=>'2.0',
        '2-5'=>'2.5',
        '3'=>'3.0',
        '4'=>'4.0',
        'by'=>'Attribution',
        'by-sa'=>'Attribution-ShareAlike',
        'by-nd'=>'Attribution-NoDerivs',
        'by-nc'=>'Attribution-NonCommercial',
        'by-nc-sa'=>'Attribution-NonCommercial-ShareAlike',
        'by-nc-nd'=>'Attribution-NonCommercial-NoDerivs'
    );
    $cc_jurisdiction_readable=array(
        'intl'=>'International',
        'ca'=>'Canada',
        'au'=>'Australia',
        'uk'=>'United Kingdom (England and Whales)',
        'us'=>'United States'
    );
    if ($cc_license != 'none') {
        return __('This work is licensed by '.rl_owner_link().' under a <a rel="license" href="http://creativecommons.org/licenses/'.$cc_license.'/'.$cc_readable[$cc_version].'/'.($cc_jurisdiction !== 'intl' ? $cc_jurisdiction : null).'">Creative Commons '.$cc_readable[$cc_license].' '.$cc_readable[$cc_version].' '.$cc_jurisdiction_readable[$cc_jurisdiction].' License</a>.');
    } else {
        return __('&copy; %1$s %2$s', date('Y'), rl_owner_link());
    }
}


/*
** Edit item link
*/
function link_to_item_edit($item=null, $pre=null, $post=null)
{
    if (is_allowed($item, 'edit')) {
        return $pre.'<a class="edit" href="'. html_escape(url('admin/items/edit/')).metadata('item', 'ID').'">'.__('Edit Item').'</a>'.$post;
    }
}

/*
** File item link
*/
function link_to_file_edit($file=null, $pre=null, $post=null)
{
    if (is_allowed($file, 'edit')) {
        return $pre.'<a class="edit" href="'. html_escape(url('admin/files/edit/')).metadata('file', 'ID').'">'.__('Edit File Details').'</a>'.$post;
    }
}

/*
** Display notice to admins if item is private
*/
function rl_item_is_private($item=null)
{
    if (is_allowed($item, 'edit') && ($item->public)==0) {
        return '<span class="item-is-private">Private</span>';
    } else {
        return null;
    }
}


/*
** iOS Smart Banner
** Shown not more than once per day
*/
function rl_ios_smart_banner()
{
    // show the iOS Smart Banner once per day if the app ID is set
    $appID = (get_theme_option('ios_app_id')) ? get_theme_option('ios_app_id') : false;
    if ($appID != false) {
        $AppBanner = 'Curatescape_AppBanner_'.$appID;
        $numericID=str_replace('id', '', $appID);
        if (!isset($_COOKIE[$AppBanner])) {
            echo '<meta name="apple-itunes-app" content="app-id='.$numericID.'">';
            setcookie($AppBanner, true, time()+86400); // 1 day
        }
    }
}

/*
** Adjust color brightness
** via: https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php#11951022
*/
function adjustBrightness($hex, $steps)
{
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0, min(255, $color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

/*
** https://stackoverflow.com/questions/5501427/php-filesize-mb-kb-conversion
*/
function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' kB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

?>