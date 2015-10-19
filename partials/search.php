<form role="search" method="get" class="search-form" action="<?php echo pmlnr_base::fix_url(get_bloginfo('url')); ?>">
	<i class="icon-search"></i><label for="search"><?php
		switch ($lang) {
			case 'hu':
				_e('Keresés' );
				break;
			default:
				_e('Search' );
				break;
		}
	?></label><br />
	<input type="search" class="search-field" placeholder="🔎 search …" value="" name="s" title="<?php _e('Search for:' ); ?>">
	<input type="submit" class="search-submit" value="<?php _e('OK' ); ?>">
</form>
