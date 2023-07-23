<?php 

/*
Template Name: Home Page
*/

get_header(); 
?>

<div class='main-content'>
<p> <?php echo get_field('heading'); ?>  </p>
<p> <?php echo get_field('content'); ?>  </p>
</div>


<?php get_footer(); ?>