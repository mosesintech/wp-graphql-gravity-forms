<?php
/**
 * Enum Type - SignatureBorderWidthEnum
 *
 * @package WPGraphQL\GF\Type\Enum,
 * @since   0.4.0
 */

namespace WPGraphQL\GF\Type\Enum;

/**
 * Class - SignatureBorderWidthEnum
 */
class SignatureBorderWidthEnum extends AbstractEnum {
	/**
	 * Type registered in WPGraphQL.
	 *
	 * @var string
	 */
	public static string $type = 'SignatureBorderWidthEnum';

	// Individual elements.
	const NONE   = '0';
	const SMALL  = '1';
	const MEDIUM = '2';
	const LARGE  = '3';

	/**
	 * {@inheritDoc}
	 */
	public static function get_description() : string {
		return __( 'Width of the border around the signature area.', 'wp-graphql-gravity-forms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values() : array {
		return [
			'NONE'   => [
				'description' => __( 'No border width.', 'wp-graphql-gravity-forms' ),
				'value'       => self::NONE,
			],
			'SMALL'  => [
				'description' => __( 'A small border width', 'wp-graphql-gravity-forms' ),
				'value'       => self::SMALL,
			],
			'MEDIUM' => [
				'description' => __( 'A medium border width', 'wp-graphql-gravity-forms' ),
				'value'       => self::MEDIUM,
			],
			'LARGE'  => [
				'description' => __( 'A large border width', 'wp-graphql-gravity-forms' ),
				'value'       => self::LARGE,
			],
		];
	}
}