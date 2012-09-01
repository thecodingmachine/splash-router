<?php

function FiveOO(ApplicationException $exception, $debug_mode=false) {
		?>
	<div>
	<h1 class="admindeo">An error occured<?php //eMsg("error.500.title"); ?></h1>
	</div>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>

		<div style="padding:20px"><?php
			echo $exception->getI18Title();
		?></div>
		<div class="vertical-gap"></div>
		<div class="vertical-gap"></div>
	<div class="stats-key">
		<div class="vertical-gap"></div>
			<?php
			echo $exception->getI18Message();
			?>
		<div style="clear: both;"></div>
	<div class="vertical-gap"></div>

		</div>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>
	<?php
if ($debug_mode) {
	?>
	<div class="stats-key">
		<div class="vertical-gap"></div>
			<?php
			echo ExceptionUtils::getHtmlForException($exception);
			while (method_exists($exception,"getInnerException") && $exception->getInnerException() !=null) {
				$exception = $exception->getInnerException();
				echo "<br/>Caused By: <br/>";
				echo ExceptionUtils::getHtmlForException($exception);
			}
			?>
		<div style="clear: both;"></div>
	<div class="vertical-gap"></div>

		</div>
		<?php  } ?>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>
	<?php
}

function UnhandledException(Exception $exception, $debug_mode) {
		?>
	<div>
	<h1 class="admindeo">An error occured<?php //eMsg("error.500.title"); ?></h1>
	</div>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>

		<div style="padding:20px">An error occured in the application. Please try again, or contact an administrator.
		<?php
			//eMsg("error.500.text");
		?></div>
		<div class="vertical-gap"></div>
		<div class="vertical-gap"></div>
	<?php
if ($debug_mode) {
	?>
	<div class="stats-key">
		<div class="vertical-gap"></div>
			<?php
			echo nl2br($exception->getMessage());
			?>
		<div style="clear: both;"></div>
	<div class="vertical-gap"></div>

		</div>


	<div class="stats-key">
		<div class="vertical-gap"></div>
			<?php
			echo ExceptionUtils::getHtmlForException($exception);
			?>
		<div style="clear: both;"></div>
	<div class="vertical-gap"></div>

		</div>
<?php  } ?>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>
	<div class="vertical-gap"></div>
	<?php
}
?>