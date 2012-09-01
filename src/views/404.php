<?php

function FourOFour($message) {
		?>
	<h1>Page not found</h1>
	
	<div>The page you requested does not exist.</div>
	<div class="stats-key">
		<div class="vertical-gap"></div>
			<?php
			echo $message;
			?>
		<div style="clear: both;"></div>
	
	</div>
	<?php
}
?>