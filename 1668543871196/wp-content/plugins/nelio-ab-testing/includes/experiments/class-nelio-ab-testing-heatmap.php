<?php
/**
 * This file defines the class of a heatmap test.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A Heatmap in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Heatmap extends Nelio_AB_Testing_Experiment {

	/**
	 * What this expeirment is tracking: a WordPress post (post_id + post_type) or a URL.
	 *
	 * @var string
	 */
	private $tracking_mode;

	/**
	 * The post ID this expeirment is tracking.
	 *
	 * @var integer
	 */
	private $tracked_post_id;

	/**
	 * The post type this expeirment is tracking.
	 *
	 * @var integer
	 */
	private $tracked_post_type;

	/**
	 * The URL this expeirment is tracking.
	 *
	 * @var integer
	 */
	private $tracked_url;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 *
	 * @since  5.0.0
	 * @access protected
	 */
	protected function __construct( $experiment ) {

		parent::__construct( $experiment );

		$this->tracking_mode     = get_post_meta( $this->ID, '_nab_tracking_mode', true );
		$this->tracked_post_id   = get_post_meta( $this->ID, '_nab_tracked_post_id', true );
		$this->tracked_post_type = get_post_meta( $this->ID, '_nab_tracked_post_type', true );
		$this->tracked_url       = get_post_meta( $this->ID, '_nab_tracked_url', true );

	}//end __construct()

	/**
	 * Returns the tested element of this experiment. If the mode is set to “post,” it returns a post ID. Otherwise, a URL.
	 *
	 * @return integer|string the tested element of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tested_element() {

		if ( 'post' === $this->tracking_mode ) {
			return absint( $this->tracked_post_id );
		}//end if

		return $this->tracked_url;

	}//end get_tested_element()

	/**
	 * Returns the tracking mode of this heatmap.
	 *
	 * @return string the tracking mode of this heatmap.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tracking_mode() {
		return $this->tracking_mode;
	}//end get_tracking_mode()

	/**
	 * Sets the tracking mode of this experiment to the given value.
	 *
	 * @param string $tracking_mode A tracking mode. Either `post` or `url`.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_tracking_mode( $tracking_mode ) {
		$this->tracking_mode = 'post' === $tracking_mode ? 'post' : 'url';
	}//end set_tracking_mode()

	/**
	 * Returns the tracked post id of this heatmap.
	 *
	 * @return string the tracked post id of this heatmap.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tracked_post_id() {
		return absint( $this->tracked_post_id );
	}//end get_tracked_post_id()

	/**
	 * Sets the tracked post id of this experiment to the given value.
	 *
	 * @param integer $tracked_post_id A tracked post id.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_tracked_post_id( $tracked_post_id ) {
		$this->tracked_post_id = absint( $tracked_post_id );
	}//end set_tracked_post_id()

	/**
	 * Returns the tracked post type of this heatmap.
	 *
	 * @return string the tracked post type of this heatmap.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tracked_post_type() {
		return $this->tracked_post_type;
	}//end get_tracked_post_type()

	/**
	 * Sets the tracked post type of this experiment to the given value.
	 *
	 * @param string $tracked_post_type A tracked post type.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_tracked_post_type( $tracked_post_type ) {
		$this->tracked_post_type = $tracked_post_type;
	}//end set_tracked_post_type()

	/**
	 * Returns the tracked url of this heatmap.
	 *
	 * @return string the tracked url of this heatmap.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_tracked_url() {
		return $this->tracked_url;
	}//end get_tracked_url()

	/**
	 * Sets the tracked url of this experiment to the given value.
	 *
	 * @param string $tracked_url A tracked url.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function set_tracked_url( $tracked_url ) {
		$this->tracked_url = $tracked_url;
	}//end set_tracked_url()

	/**
	 * Returns the preview url of this test.
	 *
	 * @return string the preview url of this test.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_preview_url() {

		$url = $this->get_tracked_url();
		if ( 'post' === $this->get_tracking_mode() ) {
			$url = get_permalink( $this->get_tracked_post_id() );
		}//end if

		$experiment_id = $this->get_id();
		$preview_time  = time();
		$secret        = nab_get_api_secret();
		return add_query_arg(
			array(
				'nab-preview'         => true,
				'nab-heatmap-preview' => true,
				'experiment'          => $experiment_id,
				'alternative'         => 'control',
				'timestamp'           => $preview_time,
				'nonce'               => md5( "nab-preview-{$experiment_id}-control-{$preview_time}-{$secret}" ),
			),
			$url
		);

	}//end get_preview_url()

	/**
	 * Returns the heatmap url of this test.
	 *
	 * @return string the heatmap url of this test.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_heatmap_url() {

		$url = $this->get_tracked_url();
		if ( 'post' === $this->get_tracking_mode() ) {
			$url = get_permalink( $this->get_tracked_post_id() );
		}//end if

		$experiment_id = $this->get_id();
		$preview_time  = time();
		$secret        = nab_get_api_secret();
		return add_query_arg(
			array(
				'nab-preview'          => true,
				'nab-heatmap-renderer' => true,
				'experiment'           => $experiment_id,
				'alternative'          => 'control',
				'timestamp'            => $preview_time,
				'nonce'                => md5( "nab-preview-{$experiment_id}-control-{$preview_time}-{$secret}" ),
			),
			$url
		);

	}//end get_heatmap_url()

	/** . @Overrides */
	public function duplicate() {

		$new_heatmap = parent::duplicate();

		$new_heatmap->set_tracking_mode( $this->get_tracking_mode() );
		$new_heatmap->set_tracked_post_id( $this->get_tracked_post_id() );
		$new_heatmap->set_tracked_post_type( $this->get_tracked_post_type() );
		$new_heatmap->set_tracked_url( $this->get_tracked_url() );

		$new_heatmap->save();

		return $new_heatmap;

	}//end duplicate()

	/** . @Overrides */
	public function save() {

		update_post_meta( $this->ID, '_nab_tracking_mode', $this->tracking_mode );
		update_post_meta( $this->ID, '_nab_tracked_post_id', $this->tracked_post_id );
		update_post_meta( $this->ID, '_nab_tracked_post_type', $this->tracked_post_type );
		update_post_meta( $this->ID, '_nab_tracked_url', $this->tracked_url );

		parent::save();

		delete_post_meta( $this->ID, '_nab_alternatives' );
		delete_post_meta( $this->ID, '_nab_goals' );
		delete_post_meta( $this->ID, '_nab_scope' );

		delete_post_meta( $this->ID, '_nab_control_backup' );
		delete_post_meta( $this->ID, '_nab_last_alternative_applied' );

	}//end save()

	/** . @Overrides */
	public function get_alternatives() {
		// Heatmaps don’t have any alternatives, so...
		return array();
	}//end get_alternatives()

	/** . @Overrides */
	public function get_goals() {
		// Heatmaps don’t have any goals, so...
		return array();
	}//end get_goals()

	/** . @Overrides */
	public function get_scope() {
		// Heatmaps don’t have a scope, so...
		return array();
	}//end get_scope()

}//end class
