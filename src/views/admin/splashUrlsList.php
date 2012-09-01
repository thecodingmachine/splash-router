<h1>Splash Apache Configuration</h1>

<p>This page let's you see all the URLs that are managed by Splash.</p>

<table class='grid'>
	<tr>
		<th>URL</th>
		<th>Controller</th>
		<th>Title</th>
		<th>Action</th>
	</tr>
	<?php 
	$i=0;
	foreach ($this->splashUrlsList as $splashUrl) { 
		$i++;
	/* @var $splashUrl SplashRoute */
	?>
	<tr class="<?php echo (($i%2)?"odd":"even") ?>">
		<td title="<?php echo plainstring_to_htmlprotected($splashUrl->comment); ?>"><?php echo ROOT_URL.$splashUrl->url ?></td>
		<td><?php echo '<a href="'.ROOT_URL.'mouf/instance/?name='.plainstring_to_htmlprotected($splashUrl->controllerInstanceName).'&selfedit='.$this->selfedit.'">'.$splashUrl->controllerInstanceName.'</a>'; ?></td>
		<td><?php echo plainstring_to_htmlprotected($splashUrl->title) ?></td>
		<td><?php echo $splashUrl->methodName ?></td>
	</tr>
<?php } ?>
</table>