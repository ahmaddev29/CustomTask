<?php
/**
 * The template for displaying the footer
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Flaggy
 */

?>

<footer class="footer">
<div class = "footer-left">
    <p><?php echo get_field('address', 'option') ?></p>
    <p><?php echo get_field('number', 'option') ?></p>
</div>

<div class = "footer-right">
<?php dynamic_sidebar('footer-1'); ?>
</div>
</footer>