<?php 

if( ! class_exists('Faqqy_Shortcode')){
    class Faqqy_Shortcode{
        public function __construct(){
            add_shortcode( 'faqqy', array( $this, 'add_shortcode' ) );
        }

        public function add_shortcode( $atts = array() ){

            $atts = array_change_key_case( (array) $atts, CASE_LOWER );

            extract( shortcode_atts(
                array(
                    'id' => '',
                ),
                $atts
            ));

            if( !empty( $id ) ){
                $id = array_map( 'absint', explode( ',', $id ) );
            }

            ob_start();
            require( FAQQY_PATH . 'views/faqqy_shortcode.php' );
            return ob_get_clean();
        }
    }
}