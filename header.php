<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/icon.png" type="image/png">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="barraZoom" data-bg-zoom-url="<?php echo is_single() ? esc_url(get_post_meta(get_the_ID(), 'bg_zoom_image', true)) : ''; ?>" style="background-image: url('<?php echo is_single() && get_post_meta(get_the_ID(), 'bg_zoom_image', true) ? esc_url(get_post_meta(get_the_ID(), 'bg_zoom_image', true)) : esc_url(get_template_directory_uri()) . '/assets/img/bg-zoom.jpg'; ?>');"></div>
<div id="easterEggTrigger" style="position: fixed; bottom: 0; left: 0; width: 60px; height: 60px; z-index: 9999;"></div>

<div class="container-principal">