<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        html,body{margin:0;padding:0;background:#fff}
        .acal-wrap{padding:16px}
        .admin-bar .acal-wrap{padding-top:48px}
    </style>
</head>
<body class="acal-front-management">
<?php $this->render_calendar_page(); ?>
<?php wp_footer(); ?>
</body>
</html>
