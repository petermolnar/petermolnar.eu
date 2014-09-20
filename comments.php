<?php

if ( post_password_required() ) return;

if ( have_comments() ) : ?>
	<!-- comments -->
	<section class="content-comments">
		<div class="content-inner">
			<p><a id="comments" /></a></p>

			<!-- comment-list -->
			<ol class="comment-list">
				<?php
					wp_list_comments( array(
						'style'      => 'ol',
						'short_ping' => false,
						'avatar_size'=> 42,
					) );
				?>
			</ol>
			<!-- end comment-list -->
		</div>
	</section>
	<!-- comments end -->

<?php endif;


