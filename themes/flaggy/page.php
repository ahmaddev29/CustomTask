<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Flaggy
 */

get_header();

?>

<div class="main-content">
						 <?php 
							 // If there are any posts
							 if( have_posts() ):
 
								 // Load posts loop
								 while( have_posts() ): the_post();
									 
                                 get_template_part( 'template-parts/content', 'page' );
										 
								 endwhile;

							 else:
						 ?>
							 <p><?php esc_html_e('Nothing to display', 'flaggy'); ?>.</p>
						 <?php endif; ?>
						 </div>
		
 <?php get_footer(); ?>