<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Widget_Heading extends Widget_Base {

	public function get_name() {
		return 'heading';
	}

	public function get_title() {
		return __( 'Heading', 'builder' );
	}

	public function get_icon() {
		return 'eicon-type-tool';
	}

	protected function _register_controls() {
		$this->start_controls_section(
			'section_title',
			[
				'label' => __( 'Title', 'builder' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => __( 'Title', 'builder' ),
				'type' => Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Enter your title', 'builder' ),
				'default' => __( 'This is heading element', 'builder' ),
			]
		);

		$this->add_control(
			'link',
			[
				'label' => __( 'Link', 'builder' ),
				'type' => Controls_Manager::URL,
				'placeholder' => 'http://your-link.com',
				'default' => [
					'url' => '',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'size',
			[
				'label' => __( 'Size', 'builder' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => __( 'Default', 'builder' ),
					'small' => __( 'Small', 'builder' ),
					'medium' => __( 'Medium', 'builder' ),
					'large' => __( 'Large', 'builder' ),
					'xl' => __( 'XL', 'builder' ),
					'xxl' => __( 'XXL', 'builder' ),
				],
			]
		);

		$this->add_control(
			'header_size',
			[
				'label' => __( 'HTML Tag', 'builder' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'h1' => __( 'H1', 'builder' ),
					'h2' => __( 'H2', 'builder' ),
					'h3' => __( 'H3', 'builder' ),
					'h4' => __( 'H4', 'builder' ),
					'h5' => __( 'H5', 'builder' ),
					'h6' => __( 'H6', 'builder' ),
					'div' => __( 'div', 'builder' ),
					'span' => __( 'span', 'builder' ),
					'p' => __( 'p', 'builder' ),
				],
				'default' => 'h2',
			]
		);

		$this->add_responsive_control(
			'max_width',
			[
				'label' => _x( 'Max width', 'Size Control', 'builder' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range' => [
					'px' => [
                        'min' => 10,
						'max' => 2000,
					],
				],
				'responsive' => true,
				'selectors' => [
					'{{WRAPPER}} .builder-widget-container .builder-heading-wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label' => __( 'Alignment', 'builder' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'builder' ),
						'icon' => 'fa fa-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'builder' ),
						'icon' => 'fa fa-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'builder' ),
						'icon' => 'fa fa-align-right',
					],
					'justify' => [
						'title' => __( 'Justified', 'builder' ),
						'icon' => 'fa fa-align-justify',
					],
				],
				'default' => '',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'view',
			[
				'label' => __( 'View', 'builder' ),
				'type' => Controls_Manager::HIDDEN,
				'default' => 'traditional',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Title', 'builder' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => __( 'Text Color', 'builder' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				    'type' => Scheme_Color::get_type(),
				    'value' => Scheme_Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .builder-heading-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography',
				'scheme' => Scheme_Typography::TYPOGRAPHY_1,
				'selector' => '{{WRAPPER}} .builder-heading-title',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings();

		if ( empty( $settings['title'] ) )
			return;

		$this->add_render_attribute( 'heading', 'class', 'builder-heading-title' );
        $this->add_render_attribute( 'heading-wrapper', 'class', 'builder-heading-wrapper' );

		if ( ! empty( $settings['size'] ) ) {
			$this->add_render_attribute( 'heading', 'class', 'builder-size-' . $settings['size'] );
		}

        if ( ! empty( $settings['size'] ) ) {
			$this->add_render_attribute( 'heading', 'class', 'builder-size-' . $settings['size'] );
		}

        if ( ! empty( $settings['align'] ) ) {
			$this->add_render_attribute( 'heading-wrapper', 'class', 'builder-align-' . $settings['align'] );
		}

		if ( ! empty( $settings['link']['url'] ) ) {
			$target = $settings['link']['is_external'] ? ' target="_blank"' : '';

			$url = sprintf( '<a href="%s"%s>%s</a>', $settings['link']['url'], $target, $settings['title'] );

			$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', $settings['header_size'], $this->get_render_attribute_string( 'heading' ), $url );
		} else {
			$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', $settings['header_size'], $this->get_render_attribute_string( 'heading' ), $settings['title'] );
		} ?>

        <div <?php echo $this->get_render_attribute_string( 'heading-wrapper' ); ?>>
            <?php echo $title_html; ?>
        </div><?php

	}

	protected function _content_template() {
		?>

		<#
		if ( '' !== settings.title ) {
			var title_html = '<' + settings.header_size  + ' class="builder-heading-title builder-size-' + settings.size + '">' + settings.title + '</' + settings.header_size + '>';
		}

		if ( '' !== settings.link.url ) {
			var title_html = '<' + settings.header_size  + ' class="builder-heading-title builder-size-' + settings.size + '"><a href="' + settings.link.url + '">' + title_html + '</a></' + settings.header_size + '>';
		}

		print( title_html );
        #>
		<?php
	}
}