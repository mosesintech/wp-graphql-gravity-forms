<?php
/**
 * GraphQL Interface for a FormField with the `default_value_setting` setting.
 *
 * @package WPGraphQL\GF\Type\Interface\FieldSetting
 * @since  @todo
 */

namespace WPGraphQL\GF\Type\WPInterface\FieldSetting;

/**
 * Class - FieldWithLabel
 */
class FieldWithLabel extends AbstractFieldSetting {
	/**
	 * Type registered in WPGraphQL.
	 *
	 * @var string
	 */
	public static string $type = 'GfFieldWithLabelSetting';

	/**
	 * The name of GF Field Setting
	 *
	 * @var string
	 */
	public static string $field_setting = 'label_setting';

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields() : array {
		return [
			'label' => [
				'type'        => 'String',
				'description' => __( 'Field label that will be displayed on the form and on the admin pages.', 'wp-graphql-gravity-forms' ),
			],
		];
	}
}
