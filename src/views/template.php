<?php
/* @var $this \Mouf\Mvc\Splash\Controllers\DefaultSplashTemplate */
?>
<html>
    <head>
        <title><?php echo $this->getTitle(); ?></title>
        <style>
            body {
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1 {
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
        </style>
    </head>
    <body>
        <?php echo $this->content->toHtml(); ?>
    </body>
</html>