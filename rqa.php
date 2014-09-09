<?php
/*
Plugin Name: Random QA
Plugin URI: https://github.com/HirczyK/Random-QA
Description: Displays a random question and (its corresponding) answer.
Author: Kevin Hirczy
Author URI: http://nehalist.io
Version: 1.0.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Thanks to @eisfuxlol
// Especially for pointing out that "RAQ" is not 
// the correct abbreviation for "Random Question&Answer"...

/*
|----------------------------------------------------------
| Plugin initialization
|----------------------------------------------------------
*/
add_action('init', function() {
    $labels = array(
        'name'                  => __('Random QA', 'rqa'),
        'singular_name'         => __('Questions', 'rqa'),
        'add_new'               => __('Add New', 'rqa'),
        'add_new_item'          => __('Add New Question', 'rqa'),
        'edit_item'             => __('Edit Question', 'rqa'),
        'new_item'              => __('New Question', 'rqa'),
        'all_items'             => __('All Question', 'rqa'),
        'view_item'             => __('View Question', 'rqa'),
        'search_items'          => __('Search Questions', 'rqa'),
        'not_found'             => __('No questions found', 'rqa'),
        'not_found_in_trash'    => __('No questions found in Trash', 'rqa'),
        'menu_name'             => __('Random QA', 'rqa'),
    );
    
    // We're using a custom post type called "rqa" for this plugin
    register_post_type('rqa', array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'exclude_from_search'   => true,
        'query_var'             => true,
        'menu_position'         => 25 // below comments
    ));
});


/*
|----------------------------------------------------------
| Shortcode
|----------------------------------------------------------
*/
add_shortcode('rqa', function($atts) {
    extract(shortcode_atts(array(
        'qlabel' => 'Q:',
        'alabel' => 'A:',
        'none'   => __('No QA found', 'rqa')
    ), $atts, 'rqa'));
    
    $query = new WP_Query(array(
        'post_type' => 'rqa',
        'orderby'   => 'rand'
    ));
    
    if($query->have_posts()) {
        $query->the_post();
        
        $question = get_the_title();
        $content  = get_the_content();
        
        wp_reset_postdata();
        
        return apply_filters('rqa', '<div class="rqa">
            <span class="rqa-question">' . $qlabel . (trim($qlabel != '') ? ' ' : '') . $question . '</span>
            <span class="rqa-answer">' . $alabel . (trim($alabel != '') ? ' ' : '') . $content . '</span>
        </div>');
    } else {
        return $none;
    }
});


/*
|----------------------------------------------------------
| Widget initialization
|----------------------------------------------------------
*/
add_action('widgets_init', function() {
    register_widget('RQA_Widget');
});

class RQA_Widget extends WP_Widget {
    
    public function __construct()
    {
        parent::__construct(false, 'Random QA');
    }

    // Displaying the widget
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        
        echo $args['before_widget'];
        
        if( ! empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        $content = '[rqa';
        $content .= (isset($instance['qlabel']) && (trim($instance['qlabel'])) != '' ? ' qlabel="' . $instance['qlabel'] . '"' : '');
        $content .= (isset($instance['alabel']) && (trim($instance['alabel'])) != '' ? ' alabel="' . $instance['alabel'] . '"' : '');
        $content .= (isset($instance['no_qa_found']) && (trim($instance['no_qa_found'])) != '' ? ' none="' . $instance['no_qa_found'] . '"' : '');
        $content .= ']';
        
        echo wpautop(do_shortcode($content));
        
        echo $args['after_widget'];
    }

    // Admin form
    public function form($instance)
    {
        $title  = (isset($instance['title']) ? $instance['title'] : __('RQA', 'rqa'));
        $qlabel = (isset($instance['qlabel']) ? $instance['qlabel'] : __('Q:', 'rqa'));
        $alabel = (isset($instance['alabel']) ? $instance['alabel'] : __('A:', 'rqa'));
        $none   = (isset($instance['no_qa_found']) ? $instance['no_qa_found'] : __('No QA found', 'rqa'));
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('qlabel'); ?>"><?php _e('Q-Label:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('qlabel'); ?>" name="<?php echo $this->get_field_name('qlabel'); ?>" type="text" value="<?php echo esc_attr($qlabel); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('alabel'); ?>"><?php _e('A-Label:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('alabel'); ?>" name="<?php echo $this->get_field_name('alabel'); ?>" type="text" value="<?php echo esc_attr($alabel); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('no_qa_found'); ?>"><?php _e('No QA found:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('no_qa_found'); ?>" name="<?php echo $this->get_field_name('no_qa_found'); ?>" type="text" value="<?php echo esc_attr($none); ?>" />
        </p>
        <?php
    }

    // Updating the widget
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title']          = ( ! empty($new_instance['title']) ? strip_tags($new_instance['title']) : '');
        $instance['qlabel']         = ( ! empty($new_instance['qlabel']) ? strip_tags($new_instance['qlabel']) : '');
        $instance['alabel']         = ( ! empty($new_instance['title']) ? strip_tags($new_instance['alabel']) : '');
        $instance['no_qa_found']    = ( ! empty($new_instance['no_qa_found']) ? strip_tags($new_instance['no_qa_found']) : '');
        return $instance;
    }

}