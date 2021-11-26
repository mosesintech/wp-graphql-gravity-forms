<?php
/**
 * Gravity Forms field.
 *
 * @see https://docs.gravityforms.com/field-object/
 * @see https://docs.gravityforms.com/gf_field/
 *
 * @package WPGraphQL\GF\Type\WPObject\FormField
 * @since   0.7.0
 */

namespace WPGraphQL\GF\Type\WPObject\FormField;

use WPGraphQL\GF\Type\WPObject\AbstractObject;

use WPGraphQL\GF\Type\WPInterface\FormField;
use WPGraphQL\Registry\TypeRegistry;

/**
 * Class - AbstractFormField
 */
abstract class AbstractFormField extends AbstractObject {
	/**
	 * Type registered in Gravity Forms.
	 *
	 * @var string
	 */
	public static $gf_type;

	/**
	 * {@inheritDoc}
	 *
	 * @var boolean
	 */
	public static bool $should_load_eagerly = true;

	/**
	 * {@inheritDoc}
	 */
	public static function register( TypeRegistry $type_registry = null ) : void {
		register_graphql_object_type(
			static::$type,
			self::prepare_config(
				[
					'description'     => static::get_description(),
					'interfaces'      => [ FormField::$type ],
					'fields'          => static::get_fields(),
					'eagerlyLoadType' => static::$should_load_eagerly,
				]
			)
		);
	}
}