	<?php global $petermolnareu_theme; ?>
	<div class="clear">&nbsp;</div>
	</section>

	<footer class="content-footer aligncenter">
		Molnár Péter © 1999-<?php echo date('Y'); ?> <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_GB" title="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License"><img alt="All photographies licenced under Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License" src="<?php echo $petermolnareu_theme->image_dir; ?>by-nc-sa.png" /></a>
		<?php wp_footer(); ?>
<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://37.139.0.159/piwik//";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 1]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
    g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();

</script>
<noscript><p><img src="http://37.139.0.159/piwik/piwik.php?idsite=1" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Code -->

	</footer>

	<?php
		if ( is_single() && comments_open()) {
			comments_template( );
		}
	?>

</body>
</html>
