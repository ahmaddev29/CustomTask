<?php
/**
 * The template for displaying all single posts
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
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
									 
                                 get_template_part( 'template-parts/content', 'single' );
										 
								 endwhile;

							 else:
						 ?>
							 <p><?php esc_html_e('Nothing to display', 'flaggy'); ?>.</p>
						 <?php endif; ?>
						 </div>
		
 <?php get_footer(); ?>