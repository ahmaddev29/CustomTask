<?php

if( ! class_exists( 'Faqqy_Post_Type' ) ){
    class Faqqy_Post_Type{
        function __construct(){

            add_action( 'init', array( $this, 'create_post_type' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
            
        }

        public function create_post_type(){

		$labels = array(
			'name'                => _x( 'FAQs', 'Post Type General Name', 'faqqy' ),
			'singular_name'       => _x( 'FAQ', 'Post Type Singular Name', 'faqqy' ),
			'menu_name'           => __( 'FAQs', 'faqqy' ),
			'parent_item_colon'   => __( 'Parent Item:', 'faqqy' ),
			'all_items'           => __( 'All Items', 'faqqy' ),
			'view_item'           => __( 'View Item', 'faqqy' ),
			'add_new_item'        => __( 'Add New FAQ Item', 'faqqy' ),
			'add_new'             => __( 'Add New', 'faqqy' ),
			'edit_item'           => __( 'Edit Item', 'faqqy' ),
			'update_item'         => __( 'Update Item', 'faqqy' ),
			'search_items'        => __( 'Search Item', 'faqqy' ),
			'not_found'           => __( 'Not found', 'faqqy' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'faqqy' ),
		);
		$args = array(		
            'label' => __( 'FAQ', 'faqqy' ),
            'description' => __( 'Professor for Homepage', 'faqqy' ),	
			'labels' => $labels,
            'menu_icon' => 'dashicons-media-text',
			'supports' => array( 'title', 'editor', 'excerpt', ),
			'taxonomies' => array(),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
			'menu_position' => 5,
            'can_export' => true,
			'has_archive' => false,
            'exclude_from_search' => false,
            'show_in_rest' => true,
            'publicly_queryable' => true,
		);
		register_post_type( 'faqqy', $args );

        }

        public function add_meta_boxes(){
            add_meta_box(
                'faqqy_meta_box',
                'FAQs',
                array( $this, 'add_inner_meta_boxes' ),
                'faqqy',
                'normal',
                'high'
            );
        }

        public function add_inner_meta_boxes( $post ){
            require_once( FAQQY_PATH . 'views/faqqy_metabox.php' );
        }

        public function save_post( $post_id ){
            if( isset( $_POST['action'] ) && $_POST['action'] == 'editpost' ){

                $old_first_question_text = get_post_meta( $post_id, 'faqqy_question_first', true );
                $new_first_question_text = $_POST['faqqy_question_first'];
                $old_first_answer_text = get_post_meta( $post_id, 'faqqy_answer_first', true );
                $new_first_answer_text = $_POST['faqqy_answer_first'];

                $old_second_question_text = get_post_meta( $post_id, 'faqqy_question_second', true );
                $new_second_question_text = $_POST['faqqy_question_second'];
                $old_second_answer_text = get_post_meta( $post_id, 'faqqy_answer_second', true );
                $new_second_answer_text = $_POST['faqqy_answer_second'];

                update_post_meta( $post_id, 'faqqy_question_first', sanitize_text_field($new_first_question_text), $old_first_question_text);
                update_post_meta( $post_id, 'faqqy_answer_first', sanitize_textarea_field($new_first_answer_text), $old_first_answer_text );

                update_post_meta( $post_id, 'faqqy_question_second', sanitize_text_field($new_second_question_text), $old_second_question_text );
                update_post_meta( $post_id, 'faqqy_answer_second', sanitize_textarea_field($new_second_answer_text), $old_second_answer_text );
                
            }
        }

    }
}