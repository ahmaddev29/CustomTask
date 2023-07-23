<?php 
    $meta = get_post_meta( $post->ID );
    $first_question_text = get_post_meta( $post->ID, 'faqqy_question_first', true );
    $first_answer_test = get_post_meta( $post->ID, 'faqqy_answer_first', true );
    $second_question_text = get_post_meta( $post->ID, 'faqqy_question_second', true );
    $second_answer_text = get_post_meta( $post->ID, 'faqqy_answer_second', true );
    //var_dump( $link_text, $link_url );
?>

<table class="form-table faqqy-metabox"> 
    <tr>
        <th>
            <label for="faqqy_question_first">Question</label>
        </th>
        <td>
            <input 
                type="text" 
                name="faqqy_question_first" 
                id="faqqy_question_first" 
                class="regular-text question-text"
                value="<?php echo ( isset( $first_question_text ) ) ? esc_html( $first_question_text ) : ''; ?>"
                required
            >
        </td>
    </tr>
    <tr>
        <th>
            <label for="faqqy_answer_first">Answer</label>
        </th>
        <td>
            <textarea 
                cols="40"
                rows="5" 
                name="faqqy_answer_first" 
                id="faqqy_answer_first" 
                class="regular-text answer-text"
                required
            ><?php echo ( isset( $first_answer_test ) ) ? esc_html( $first_answer_test ) : ''; ?></textarea>
        </td>
    </tr>
    <tr>
        <th>
            <label for="faqqy_question_second">Question</label>
        </th>
        <td>
            <input 
                type="text" 
                name="faqqy_question_second" 
                id="faqqy_question_second" 
                class="regular-text question-text"
                value="<?php echo ( isset( $second_question_text ) ) ? esc_html( $second_question_text ) : ''; ?>"
                required
            >
        </td>
    </tr>   
    <tr>
        <th>
            <label for="faqqy_answer_second">Answer</label>
        </th>
        <td>
            <textarea 
                cols="40"
                rows="5" 
                name="faqqy_answer_second" 
                id="faqqy_answer_second" 
                class="regular-text answer-text"
                required
            ><?php echo ( isset( $second_answer_text ) ) ? esc_html( $second_answer_text ) : ''; ?></textarea>
        </td>
    </tr>            
</table>