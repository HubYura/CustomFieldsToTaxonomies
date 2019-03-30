<?php 
$term = get_queried_object();
$title = get_metadata('term', $term->term_id, 'cf_custom_title', 1 );
if(!empty($title))
	echo "<h1 class=\"trail-title\">$title</h1>";