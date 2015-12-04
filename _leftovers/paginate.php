<?php
	global $wp_query;
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;

	$pargs = array(
		'format'	 => 'page/%#%',
		'current'	=> $current,
		'end_size'   => 1,
		'mid_size'   => 2,
		'prev_next'  => True,
		'prev_text'  => __('«'),
		'next_text'  => __('»'),
		'type'	   => 'list',
		'total'	  => $wp_query->max_num_pages,
	);
	echo paginate_links( $pargs );
