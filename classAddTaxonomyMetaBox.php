<?php
/*
 * Бекенда для додавання налаштувань на сторінку редагування елементів таксономій
 * https://github.com/HubYura/CustomFieldsToTaxonomies.git
 */
class addTaxonomyMetaBox {
    private $opt;
    private $prefix;

    function __construct( $option ) {
        $this->opt    = (object) $option;
        $this->prefix = $this->opt->id .'_'; // префікс налаштувань

        foreach( $this->opt->taxonomy as $taxonomy ){
            add_action( $taxonomy . '_edit_form_fields', array( &$this, 'fill'), 10, 2 ); // хук додавання полів
        }

        // встановимо таблицю в $ wpdb, якщо її немає
        global $wpdb;
        if( ! isset( $wpdb->termmeta ) ) $wpdb->termmeta = $wpdb->prefix .'termmeta';

        add_action('edit_term', array( &$this, 'save'), 10, 1 ); // хук збереження значень полів
    }

    function fill( $term, $taxonomy ){

        foreach( $this->opt->args as $param ){
            $def   = array('id'=>'', 'title'=>'', 'type'=>'', 'desc'=>'', 'std'=>'', 'args'=>array() );
            $param = (object) array_merge( $def, $param );

            $meta_key   = $this->prefix . $param->id;
            $meta_value = get_metadata('term', $term->term_id, $meta_key, true ) ?: $param->std;

            echo '<tr class ="form-field">';
                echo '<th scope="row"><label for="'. $meta_key .'">'. $param->title .'</label></th>';
                echo '<td>';

                // select
        if( $param->type == 'wp_editor' ){
          wp_editor( $meta_value, $meta_key, array(
            'wpautop' => 1,
            'media_buttons' => false,
            'textarea_name' => $meta_key, // потрібно вказувати!
            'textarea_rows' => 10,
            //'tabindex'      => null,
            //'editor_css'    => '',
            //'editor_class'  => '',
            'teeny'         => 0,
            'dfw'           => 0,
            'tinymce'       => 1,
            'quicktags'     => 1,
            //'drag_drop_upload' => false
          ) );
        }
        // select
                elseif( $param->type == 'select' ){
                    echo '<select name="'. $meta_key .'" id="'. $meta_key .'">
                            <option value="">...</option>';

                            foreach( $param->args as $val => $name ){
                                echo '<option value="'. $val .'" '. selected( $meta_value, $val, 0 ) .'>'. $name .'</option>';
                            }
                    echo '</select>';
                    if( $param->desc ) echo '<p class="description">' . $param->desc . '</p>';
                }
                // checkbox
                elseif( $param->type == 'checkbox' ){
                    echo '
                        <label>
                            <input type="hidden" name="'. $meta_key .'" value="">
                            <input name="'. $meta_key .'" type="'. $param->type .'" id="'. $meta_key .'" '. checked( $meta_value, 'on', 0) .'>
                            '. $param->desc .'
                        </label>
                    ';
                }
                // textarea
                elseif( $param->type == 'textarea' ){
                    echo '<textarea name="'. $meta_key .'" type="'. $param->type .'" id="'. $meta_key .'" value="'. $meta_value .'" class="large-text">'. esc_html( $meta_value ) .'</textarea>';                    
                    if( $param->desc ) echo '<p class="description">' . $param->desc . '</p>';
                }
                // text
                else{
                    echo '<input name="'. $meta_key .'" type="'. $param->type .'" id="'. $meta_key .'" value="'. $meta_value .'" class="regular-text">';

                    if( $param->desc ) echo '<p class="description">' . $param->desc . '</p>';
                }
                echo '</td>';
            echo '</tr>';         
        }

    }

    function save( $term_id ){
        foreach( $this->opt->args as $field ){
            $meta_key = $this->prefix . $field['id'];
            if( ! isset($_POST[ $meta_key ]) ) continue;

            if( $meta_value = trim($_POST[ $meta_key ]) ){
                update_metadata('term', $term_id, $meta_key, $meta_value, '');
            }
            else {
                delete_metadata('term', $term_id, $meta_key, '', false );
            }
        }
    }

}

add_action('init', 'register_additional_term_fields');
function register_additional_term_fields(){ 
    new addTaxonomyMetaBox( array(
        'id'       => 'cf', // id грає роль префікса назв полів
        'taxonomy' => array('post_tag'), // назви таксономій, для яких потрібно додати нижче перераховані поля
        'args'     => array(
            array(
                'id'    => 'custom_title', // атрибути name і id без префікса, вийде "cf_custom_title"
                'title' => 'Додатковий заголовок',
                'type'  => 'text',
                'desc'  => 'Альтернативний заголовок для міток',
                'std'   => '', // за замовчуванням
            )
        )
    ) );
}