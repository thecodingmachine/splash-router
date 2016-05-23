<?php
use Mouf\Mvc\Splash\Controllers\HttpErrorsController;
use Mouf\Mvc\Splash\Utils\ExceptionUtils;

/* @var $this HttpErrorsController */
?>
<h1>An error occured</h1>

<div>An error occured in the application. Please try again, or contact an administrator.</div>
<?php
if ($this->debugMode) {
    echo '<div>'.nl2br($this->exception->getMessage()).'</div>';

    echo '<div>'.ExceptionUtils::getHtmlForException($this->exception).'</div>';
} ?>
