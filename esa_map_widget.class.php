<?php
/**
 * @package 	wordpress-storytelling
 * @subpackage	widget for esa map
 * @link 		
 * @author 		Philipp Franck
 *
 *
 */
class esa_map_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'ESA Map',
			'description' => 'Displays a map of all posts with embedded content which have geographic coordinates.',
		);
		parent::__construct('esa_map_widget', 'ESA Map', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args, $instance) {
        if (!esa_get_settings('modules', 'map', 'activate')) {
            return;
        }
		$h = (isset($instance['height']) and $instance['height'] > 0) ? " style='height:{$instance['height']}px'" : '';
		echo "<div id='{$args['widget_id']}' class='esa_items_overview_map' data-display='{$instance['display']}' $h>&nbsp;</div>";
		//echo esa_debug($instance);
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form($instance) {
		// outputs the options form on admin
		$height = (isset($instance['height'])) ? $instance['height'] : '';
        $display = (isset($instance['display'])) ? $instance['display'] : '';
        $display_radio = array(
            'wrapper' => 'Map data objects only',
            'embedded' => 'Map posts, containing data objects only',
            'both' => 'Map Both'
        );
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height:' ); ?></label> 
				<input
                        class="widefat"
                        id="<?php echo $this->get_field_id( 'height' ); ?>"
                        name="<?php echo $this->get_field_name( 'height' ); ?>"
                        type="number"
                        value="<?php echo esc_attr($height); ?>"
                >
                <br>
                <?php foreach ($display_radio as $radio_name => $radio_label) { ?>
                    <input
                        class="widefat"
                        id="<?php echo $this->get_field_id( "display-$radio_name" ) ; ?>"
                        name="<?php echo $this->get_field_name( 'display' ); ?>"
                        type="radio"
                        value="<?php echo $radio_name; ?>"
                        <?php echo ($radio_name == $display) ? 'checked' : ''; ?>
                    >
                    <label for="<?php echo $this->get_field_id( "display-$radio_name" ) ; ?>"><?php echo $radio_label; ?></label>
                    <br>
                <?php } ?>
            </p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['height'] = (!empty($new_instance['height'])) ? strip_tags($new_instance['height']) : '';
		$instance['display'] = (!empty($new_instance['display'])) ? strip_tags($new_instance['display']) : '';
		return $instance;
	}
}
?>