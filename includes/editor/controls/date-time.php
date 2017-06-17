<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A Date/Time Picker control.
 *
 * @param string $default           A date in mysql format (YYYY-mm-dd HH:ii)
 *                                  Default empty
 * @param array  $picker_options    The picker config. @see http://mugifly.github.io/jquery-simple-datetimepicker/jquery.simple-dtpicker.html
 *                                  Default empty array
 * @since 1.0.0
 */
class Control_Date_Time extends Base_Control {

	public function get_type() {
		return 'date_time';
	}

	function get_default_settings() {
		return [
			'picker_options' => [],
			'label_block' => true,
		];
	}

	public function content_template() {
		?>
		<div class="builder-control-field">
			<label class="builder-control-title">{{{ data.label }}}</label>
			<div class="builder-control-input-wrapper">
				<input class="builder-date-time-picker" type="text" data-setting="{{ data.name }}">
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="builder-control-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}