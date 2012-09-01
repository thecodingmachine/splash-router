<?php
$msg['controller.annotation.var.missingclosebracket.title'] = "An error was detected in @Var annotation.";
$msg['controller.annotation.var.missingclosebracket.text']="<p class=\"small\" style=\"color: red;\">An error was detected in @Var annotation. Missing closing bracket in: '{0}'.</p>";

$msg['controller.annotation.var.unabletofindvalidator.title'] = "An error was detected in @param annotation.";
$msg['controller.annotation.var.unabletofindvalidator.text']="<p class=\"small\" style=\"color: red;\">An error was detected in @param annotation. Unable to find Validator '{0}'. Please check that this class exist and was included in your project.</p>";

$msg['controller.annotation.var.urlorigintakesanint.title'] = "An error was detected in @Var annotation.";
$msg['controller.annotation.var.urlorigintakesanint.text']="<p class=\"small\" style=\"color: red;\">An error was detected in @Var annotation. In the origin, a 'url' origin was specified. The parameter for 'url' must be an integer. Origin specified: {0}</p>";

$msg['controller.annotation.var.incorrectcommand.title'] = "An error was detected in @Var annotation.";
$msg['controller.annotation.var.incorrectcommand.text'] = "<p class=\"small\" style=\"color: red;\">An error was detected in @Var annotation. In the 'origin' parameter, you can specify either request / session or url. '{0}' was passed as a command.</p>";

$msg['controller.annotation.var.validationexception.title'] = "Validator error.";
$msg['controller.annotation.var.validationexception.text'] = "An error was detected in a validator.";

$msg['controller.annotation.var.validationexception.debug.title'] = "Validator error.";
$msg['controller.annotation.var.validationexception.debug.text'] = "An error was detected in the validator \"<i>{0}</i>\" for the argument \"<i>{1}</i>\" with value \"<i>{2}</i>\".";

$msg['error.500.title']="An error occured in the application.";
$msg['error.500.text']="We are sorry, an error occured in the application. Please <a href='".ROOT_URL."'>click here</a> to go back to the home page.";

$msg['404.back.on.tracks']="Go to <a href='".ROOT_URL."'>Home Page</a> to be back on tracks!";
$msg['404.wrong.class']="The URL seems to contain an error.";
$msg['404.wrong.file']="The URL seems to contain an error. ";
$msg['404.wrong.method']="The URL seems to contain an error. ";
$msg['404.wrong.url']="The URL seems to contain an error.";



$msg['controller.404.no.action'] = "Note for developers: the controller '{0}' has been found, and the function '{1}' exists. However, the function '{1}' does not have a @Action annotation. Therefore, it cannot be accessed.";
$msg['controller.incorrect.parameter.title'] = "Incorrect parameters passed in URL.";
$msg['controller.incorrect.parameter.text'] = "Incorrect parameters passed in URL: The URL maps to action '{0}->{1}'. This action expects parameter '{2}' to be specified."; 
$msg['controller.annotation.var.validation.error'] = "Incorrect parameters passed in URL: The parameter {1} should map validator {0}, but it doesn't. Value passed: '{2}'.";
$msg['controller.annotation.var.validation.error.title'] = "Incorrect parameters passed in URL";

$msg['subscribe.tab.first'] = "Acc&egrave;s";
$msg['subscribe.tab.second'] = "Infos perso";
$msg['subscribe.tab.third'] = "Le petit plus";
?>