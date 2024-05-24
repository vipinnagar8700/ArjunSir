<?php

use OTGS\Toolset\Twig\Environment;

/**
 * Returns the HTML container (using Twig) with the fields edit inputs
 *
 * @since m2m
 */
class Types_Viewmodel_Fields_Edit_Container {


	/**
	 * Twig environment
	 *
	 * @var Environment
	 * @since m2m
	 */
	private $twig;


	/**
	 * Twig context
	 *
	 * @var array
	 * @since m2m
	 */
	private $context;


	/**
	 * Fields array
	 *
	 * @var Toolset_Field_Definition[]|Toolset_Field_Instance[] Fields definitions.
	 * @since m2m
	 */
	private $fields;


	/**
	 * Template name
	 *
	 * @var string
	 * @since m2m
	 */
	private $template_name;


	/**
	 * Viewmodel for getting formatted data
	 *
	 * @var Types_Viewmodel_Field_Input|null
	 * @since m2m
	 */
	protected $viewmodel;


	/**
	 * Constructor
	 *
	 * @param Toolset_Field_Definition[]|Toolset_Field_Instance[] $fields Array of fields.
	 * @param Environment $twig Twig environment.
	 * @param array $context Initial Twig context.
	 * @param string $template_name Template path.
	 * @param Types_Viewmodel_Field_Input $viewmodel Viewmodel for getting formatted data.
	 */
	public function __construct( $fields, $twig, $context, $template_name, $viewmodel = null ) {
		$this->fields = toolset_ensarr( $fields );
		$this->twig = $twig;
		$this->context = wp_parse_args(
			toolset_ensarr( $context ),
			[ 'id' => 'field-container-' . wp_rand( 1, 1000 ) ]
		);
		$this->template_name = $template_name;
		$this->viewmodel = $viewmodel;
	}


	/**
	 * Gets the viewmodel
	 *
	 * @return Types_Viewmodel_Field_Input
	 * @since m2m
	 */
	private function get_view_model() {
		if ( ! $this->viewmodel ) {
			$this->viewmodel = new Types_Viewmodel_Field_Input( $this->fields );
		}
		return $this->viewmodel;
	}


	/** @noinspection PhpDocMissingThrowsInspection */
	/**
	 * Returns the HTML cointainer for the fields inputs
	 * Renders the output using Twig
	 *
	 * @return string
	 * @since m2m
	 */
	public function to_html() {
		$viewmodel = $this->get_view_model();
		$context = array_merge( $this->context, array(
			'fields' => $viewmodel->get_fields_data( Toolset_Field_Renderer_Purpose::INPUT ),
			'wpnonce' => $this->context['nonce'],
			'nonce' => $this->context['nonce'],
		) );

		/** @noinspection PhpUnhandledExceptionInspection */
		return $this->twig->render( $this->template_name, $context );
	}
}
