
<?php $author_email =  get_the_author_meta ( 'user_email' , $author ); ?>
<hr />
	<p class="small">
		<?php printf(__('Want to leave a comment or get in touch? Reply with your own blog using <a href="http://indiewebcamp.com/webmention">Webmentions</a>, send an <a href="mailto:%s">email</a>, or poke me on social media.', 'petermolnareu'), $author_email); ?>
	</p>
