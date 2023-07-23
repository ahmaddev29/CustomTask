<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Flaggy
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="header">
    <img src="<?php echo get_field('logo', 'option') ?>" alt="flaggy logo" class="logo">


    <nav class="user-nav">
        <?php
            wp_nav_menu(array(
            'theme_location' => 'flaggy_main_menu'
            ));
        ?>
            
    </nav>

    <div class="right-side">
        <a href="#" class="btn-right">Login</a>
    </div>
</header>

