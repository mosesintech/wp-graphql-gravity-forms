<?php
/**
 * Test Time type.
 *
 * @package Tests\WPGraphQL\GF
 */

use Tests\WPGraphQL\GF\TestCase\FormFieldTestCase;
use Tests\WPGraphQL\GF\TestCase\FormFieldTestCaseInterface;


/**
 * Class -TimeFieldTest.
 */
class TimeFieldTest  extends FormFieldTestCase implements FormFieldTestCaseInterface {
	/**
	 * Tests the field properties and values.
	 */
	public function testField(): void {
		$this->runTestField();
	}
	/**
	 * Tests submitting the field values as a draft entry with submitGravityFormsForm.
	 */
	public function testSubmitDraft(): void {
		$this->runTestSubmitDraft();
	}
	/**
	 * Tests submitting the field values as an entry with submitGravityFormsForm.
	 */
	public function testSubmit(): void {
		$this->runTestSubmit();
	}
	/**
	 * Tests updating the field value with updateGravityFormsEntry.
	 */
	public function testUpdate(): void {
		$this->runTestUpdate();
	}
	/**
	 * Tests updating the draft field value with updateGravityFormsEntry.
	 */
	public function testUpdateDraft():void {
		$this->runTestUpdateDraft();
	}
	/**
	 * Sets the correct Field Helper.
	 */
	public function field_helper() {
		return $this->tester->getPropertyHelper( 'TimeField' );
	}

	/**
	 * Generates the form fields from factory. Must be wrappend in an array.
	 */
	public function generate_fields() : array {
		return [ $this->factory->field->create( $this->property_helper->values ) ];
	}

	/**
	 * The value as expected in GraphQL.
	 */
	public function field_value() {
		return [
			'amPm'         => 'am',
			'displayValue' => '08:05 am',
			'hours'        => 8,
			'minutes'      => 5,
		];
	}

	public function field_value_input() {
		return ( $this->field_value() )['displayValue'];
	}

	/**
	 * The value as expected in GraphQL when updating from field_value().
	 */
	public function updated_field_value() {
		return [
			'amPm'         => 'pm',
			'displayValue' => '20:05',
			'hours'        => 8,
			'minutes'      => 5,
		];
	}

	public function updated_field_value_input() {
		return ( $this->updated_field_value() )['displayValue'];
	}


	/**
	 * Thehe value as expected by Gravity Forms.
	 */
	public function value() {
		return [ 'input_' . $this->fields[0]['id'] => $this->field_value['displayValue'] ];
	}

	/**
	 * The GraphQL query string.
	 *
	 * @return string
	 */
	public function field_query() : string {
		return '
			... on TimeField {
				adminLabel
				adminOnly
				allowsPrepopulate
				cssClass
				description
				descriptionPlacement
				errorMessage
				inputName
				inputs {
					autocompleteAttribute
					customLabel
					defaultValue
					id
					label
					placeholder
				}
				isRequired
				label
				noDuplicates
				size
				subLabelPlacement
				timeFormat
				timeValues {
					amPm
					displayValue
					hours
					minutes
				}
				visibility
			}
		';
	}

	/**
	 * SubmitForm mutation string.
	 */
	public function submit_form_mutation() : string {
		return '
			mutation ($formId: Int!, $fieldId: Int!, $value: String!, $draft: Boolean) {
				submitGravityFormsForm(input: {formId: $formId, clientMutationId: "123abc", saveAsDraft: $draft, fieldValues: {id: $fieldId, value: $value}}) {
					errors {
						id
						message
					}
					entryId
					resumeToken
					entry {
						formFields {
							nodes {
								... on TimeField {
									timeValues {
										amPm
										displayValue
										hours
										minutes
									}
								}
							}
						}
					}
				}
			}
		';
	}

	/**
	 * Returns the UpdateEntry mutation string.
	 */
	public function update_entry_mutation() : string {
		return '
			mutation updateGravityFormsEntry( $entryId: Int!, $fieldId: Int!, $value: String! ){
				updateGravityFormsEntry(input: {clientMutationId: "abc123", entryId: $entryId, fieldValues: {id: $fieldId, value: $value} }) {
					errors {
						id
						message
					}
					entry {
						formFields {
							nodes {
								... on TimeField {
									timeValues {
										amPm
										displayValue
										hours
										minutes
									}
								}
							}
						}
					}
				}
			}
		';
	}

	/**
	 * Returns the UpdateDraftEntry mutation string.
	 */
	public function update_draft_entry_mutation() : string {
		return '
			mutation updateGravityFormsDraftEntry( $resumeToken: String!, $fieldId: Int!, $value: String! ){
				updateGravityFormsDraftEntry(input: {clientMutationId: "abc123", resumeToken: $resumeToken, fieldValues: {id: $fieldId, value: $value} }) {
					errors {
						id
						message
					}
					entry {
						formFields {
							nodes {
								... on TimeField {
									timeValues {
										amPm
										displayValue
										hours
										minutes
									}
								}
							}
						}
					}
				}
			}
		';
	}

	/**
	 * The expected WPGraphQL field response.
	 *
	 * @param array $form the current form instance.
	 */
	public function expected_field_response( array $form ) : array {
		return [
			$this->expectedObject(
				'gravityFormsEntry',
				[
					$this->expectedObject(
						'formFields',
						[
							$this->expectedNode(
								'0',
								array_merge_recursive(
									$this->property_helper->getAllActualValues( $form['fields'][0] ),
									[ 'value' => $this->field_value ],
								)
							),
						]
					),
				]
			),
		];
	}

	/**
	 * The expected WPGraphQL mutation response.
	 *
	 * @param string $mutationName .
	 * @param mixed  $value .
	 * @return array
	 */
	public function expected_mutation_response( string $mutationName, $value ) : array {
		return [
			$this->expectedObject(
				$mutationName,
				[
					$this->expectedObject(
						'entry',
						[
							$this->expectedObject(
								'formFields',
								[
									$this->expectedNode(
										'0',
										$this->expectedField( 'value', $value ),
									),
								]
							),
						]
					),
				]
			),
		];
	}

	/**
	 * Checks if values submitted by GraphQL are the same as whats stored on the server.
	 *
	 * @param array $actual_entry .
	 * @param array $form .
	 */
	public function check_saved_values( $actual_entry, $form ) : void {
		$this->assertEquals( $this->field_value['displayValue'], $actual_entry[ $form['fields'][0]->id ], 'Submit mutation entry value not equal' );
	}
}