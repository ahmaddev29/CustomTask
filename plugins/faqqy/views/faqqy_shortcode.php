<?php 
    
    $args = array(
        'post_type' => 'faqqy',
        'post_status'   => 'publish',
        'post__in'  => $id,
    );

    $my_query = new WP_Query( $args );

    $my_query = new WP_Query( $args );

    if( $my_query->have_posts() ):
        while( $my_query->have_posts() ) : $my_query->the_post();

        $first_question_text = get_post_meta( get_the_ID(), 'faqqy_question_first', true );
        $first_answer_test = get_post_meta( get_the_ID(), 'faqqy_answer_first', true );
        $second_question_text = get_post_meta( get_the_ID(), 'faqqy_question_second', true );
        $second_answer_text = get_post_meta( get_the_ID(), 'faqqy_answer_second', true );

?>


<h2><?php the_title(); ?></h2>
<p><?php the_content(); ?></p>
<div>
	<details open>
		<summary>
        <?php echo esc_html(  $first_question_text ); ?>
		</summary>
		<div>
        <?php echo esc_html( $first_answer_test ); ?>
		</div>
	</details>
	<details>
		<summary>
        <?php echo esc_html( $second_question_text ); ?>
		</summary>
		<div>
        <?php echo esc_html( $second_answer_text ); ?>
		</div>
	</details>
</div>
<?php
        endwhile; 
        wp_reset_postdata();
    endif; 
    ?>

