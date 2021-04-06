<?php
/**
 * GraphQL Object Type - PostCustomFieldValue
 * Values for an individual Post custom field.
 *
 * @package WPGraphQLGravityForms\Types\Field\FieldValue
 * @since   0.3.0
 */

namespace WPGraphQLGravityForms\Types\Field\FieldValue;

use GF_Field;

/**
 * Class - PostCustomFieldValue
 */
class PostCustomFieldValue extends AbstractFieldValue {
	/**
	 * Type registered in WPGraphQL.
	 *
	 * @var string
	 */
	public static $type = 'PostCustomFieldValue';

	/**
	 * Sets the field type description.
	 *
	 * @since 0.4.0
	 */
	public function get_type_description() : string {
		return __( 'Post custom field value.', 'wp-graphql-gravity-forms' );
	}

	/**
	 * Gets the properties for the Field.
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	public function get_properties() : array {
		return [
			'values' => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The value.', 'wp-graphql-gravity-forms' ),
			],
		];
	}

	/**
	 * Get the field value.
	 *
	 * @param array    $entry Gravity Forms entry.
	 * @param GF_Field $field Gravity Forms field.
	 *
	 * @return array Entry field value.
	 */
	public static function get( array $entry, GF_Field $field ) : array {
		$entry_values = $entry[ $field['id'] ] ?? null;

		if ( empty( $entry_values ) ) {
			return [ 'values' => null ];
		}

		if ( is_string( $entry_values ) ) {
			$entry_values = json_decode( $entry_values );
		}

		return [
			'values' => $entry_values,
		];
	}
}
