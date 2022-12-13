<?php
/**
 * Nelio A/B Testing helper functions to ease development.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the experiment whose ID is the given ID.
 *
 * @param integer $experiment_id The ID of the experiment.
 *
 * @return Nelio_AB_Testing_Experiment|WP_Error The experiment with the given
 *               ID or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment( $experiment_id ) {

	return Nelio_AB_Testing_Experiment::get_experiment( $experiment_id );

}//end nab_get_experiment()

/**
 * Returns the experiment results for the experiment whose ID is the given ID.
 *
 * @param integer $experiment_id The ID of the experiment.
 *
 * @return Nelio_AB_Testing_Experiment_Results|WP_Error The results for the experiment with the given
 *               ID or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment_results( $experiment_id ) {

	return Nelio_AB_Testing_Experiment_Results::get_experiment_results( $experiment_id );

}//end nab_get_experiment_results()

/**
 * Creates a new experiment with the given type.
 *
 * @param string $experiment_type The type of the experiment.
 *
 * @return Nelio_AB_Testing_Experiment|WP_Error The experiment with the given
 *               type or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_create_experiment( $experiment_type ) {

	return Nelio_AB_Testing_Experiment::create_experiment( $experiment_type );

}//end nab_create_experiment()

/**
 * Returns the list of ids of running split testing experiments.
 *
 * @return array the list of ids of running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_all_experiment_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p
					WHERE p.post_type = %s",
				'nab_experiment'
			)
		)
	);

}//end nab_get_all_experiment_ids()

/**
 * Returns a list of IDs with the corresponding running split testing experiments.
 *
 * @return array a list of IDs with the corresponding running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_experiments() {

	$helper = Nelio_AB_Testing_Experiment_Helper::instance();
	return $helper->get_running_experiments();

}//end nab_get_running_experiments()

/**
 * Returns the list of ids of running split testing experiments.
 *
 * @return array the list of ids of running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_experiment_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m
					WHERE
						p.post_type = %s AND p.post_status = %s AND
						p.ID = m.post_id AND
						m.meta_key = %s AND m.meta_value != %s",
				'nab_experiment',
				'nab_running',
				'_nab_experiment_type',
				'nab/heatmap'
			)
		)
	);

}//end nab_get_running_experiment_ids()

/**
 * Returns the list of running nab/heatmap experiments.
 *
 * @return array the list of running nab/heatmap experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_heatmaps() {

	$helper = Nelio_AB_Testing_Experiment_Helper::instance();
	return $helper->get_running_heatmaps();

}//end nab_get_running_heatmaps()

/**
 * Returns a list of IDs corresponding to running heatmaps.
 *
 * @return array a list of IDs corresponding to running heatmaps.
 *
 * @since 5.0.0
 */
function nab_get_running_heatmap_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m
					WHERE
						p.post_type = %s AND p.post_status = %s AND
						p.ID = m.post_id AND
						m.meta_key = %s AND m.meta_value = %s",
				'nab_experiment',
				'nab_running',
				'_nab_experiment_type',
				'nab/heatmap'
			)
		)
	);

}//end nab_get_running_heatmap_ids()

/**
 * Returns whether there are running experiments (split tests and heatmaps).
 *
 * @return boolean true if there are running experiments, false otherwise.
 *
 * @since 5.0.0
 */
function nab_are_there_experiments_running() {

	global $wpdb;

	$running_exps = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status = %s",
			'nab_experiment',
			'nab_running'
		)
	);

	return $running_exps > 0;

}//end nab_are_there_experiments_running()

/**
 * Returns the amount of quota.
 *
 * @return integer the quota.
 *
 * @since 5.0.0
 */
function nab_get_quota() {
	$request  = new WP_REST_Request( 'GET', '/nab/v1/site' );
	$response = rest_do_request( $request );
	$data     = $response->get_data();
	return array(
		'quota'           => $data['quota'],
		'quota_extra'     => $data['quotaExtra'],
		'quota_per_month' => $data['quotaPerMonth'],
	);
}//end nab_get_quota()

/**
 * Returns the role of the given user.
 *
 * @param integer|WP_User $user the user.
 *
 * @return string the role of the given user. Defaults to current user.
 *
 * @since 5.0.0
 */
function nab_get_user_role( $user = 0 ) {

	if ( empty( $user ) ) {
		$user = get_current_user_id();
	}//end if

	if ( is_numeric( $user ) ) {
		$user = get_user_by( 'id', absint( $user ) );
	}//end if

	if ( ! $user ) {
		return 'subscriber';
	} elseif ( $user->has_cap( 'manage_options' ) ) {
		return 'administrator';
	} elseif ( $user->has_cap( 'edit_others_posts' ) ) {
		return 'editor';
	} elseif ( $user->has_cap( 'publish_posts' ) ) {
		return 'author';
	} elseif ( $user->has_cap( 'edit_posts' ) ) {
		return 'contributor';
	} else {
		return 'subscriber';
	}//end if

}//end nab_get_user_role()

/**
 * Returns the role of the current user.
 *
 * @return string the role of the current user.
 *
 * @since 5.0.0
 */
function nab_get_current_user_role() {
	return nab_get_user_role();
}//end nab_get_current_user_role()

/**
 * Checks whether the current user can behave as the specified role or not.
 *
 * @param string $req_role The (minimum) required role.
 * @param string $mode     Optional. If `exactly`, the current user must have
 *                         this very role. If `or-above`, that role or above
 *                         would return true. Default: `or-above`.
 *
 * @return boolean whether the user can behave as the specified role or not.
 *
 * @since 5.0.0
 */
function nab_is_current_user( $req_role, $mode = 'or-above' ) {

	$role = nab_get_current_user_role();

	// If the required role is the user's role, return true.
	if ( $role === $req_role ) {
		return true;
	}//end if

	// If the required role is not the user's role, we have to look for
	// "subsumed" roles when the mode is not "exactly".
	if ( 'or-above' === $mode ) {

		switch ( $role ) {

			case 'administrator':
				return true;

			case 'editor':
				return in_array( $req_role, array( 'author', 'contributor', 'subscriber' ), true );

			case 'author':
				return in_array( $req_role, array( 'contributor', 'subscriber' ), true );

			case 'contributor':
				return in_array( $req_role, array( 'subscriber' ), true );

		}//end switch
	}//end if

	return false;

}//end nab_is_current_user()

/**
 * Returns whether the current request should be split tested or not.
 *
 * If it’s split tested, hooks for loading alternative content and tracking events will be set. Otherwise, the public facet of Nelio A/B Testing will be disabled.
 *
 * @return boolean whether the current request should be split tested or not.
 *
 * @since 5.0.0
 */
function nab_is_split_testing_disabled() {

	/**
	 * Whether the current request should be excluded from split testing or not.
	 *
	 * If it’s split tested, hooks for loading alternative content and tracking events will be set.
	 * Otherwise, the public facet of Nelio A/B Testing will be disabled.
	 *
	 * **Notice.** Our plugin uses JavaScript to load alternative content. Be careful when limiting tests
	 * in PHP, as it’s possible that your cache or CDN ends up caching these limitations and, as a result,
	 * none of your visitors are tested.
	 *
	 * @return boolean whether the current request should be excluded from split testing or not. Default: `false`.
	 *
	 * @since 5.0.0
	 */
	return apply_filters( 'nab_disable_split_testing', false );

}//end nab_is_split_testing_disabled()

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * @return boolean Whether this site is a staging site or not.
 *
 * @since 5.0.0
 */
function nab_is_staging() {

	/**
	 * List of URLs (or keywords) used to identify a staging site.
	 *
	 * If `nab_home_url` matches one of the given values, the current site will
	 * be considered as a staging site.
	 *
	 * @param array $urls list of staging URLs (or fragments). Default: `[ 'staging' ]`.
	 *
	 * @since 5.0.0
	 */
	$staging_urls = apply_filters( 'nab_staging_urls', array( 'staging' ) );
	foreach ( $staging_urls as $staging_url ) {
		if ( strpos( nab_home_url(), $staging_url ) !== false ) {
			return true;
		}//end if
	}//end foreach

	return false;

}//end nab_is_staging()

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 5.0.0
 */
function nab_get_timezone() {

	$timezone_string = get_option( 'timezone_string', '' );
	if ( ! empty( $timezone_string ) ) {

		if ( 'UTC' === $timezone_string ) {
			return '+00:00';
		} else {
			return $timezone_string;
		}//end if
	}//end if

	$utc_offset = get_option( 'gmt_offset', 0 );

	if ( $utc_offset < 0 ) {
		$utc_offset_no_dec = '' . absint( $utc_offset );
		$result            = sprintf( '-%02d', absint( $utc_offset_no_dec ) );
	} else {
		$utc_offset_no_dec = '' . absint( $utc_offset );
		$result            = sprintf( '+%02d', absint( $utc_offset_no_dec ) );
	}//end if

	if ( $utc_offset === $utc_offset_no_dec ) {
		$result .= ':00';
	} else {
		$result .= ':30';
	}//end if

	return $result;

}//end nab_get_timezone()

/**
 * Registers a script loading the dependencies automatically.
 *
 * @param string  $handle    the script handle name.
 * @param string  $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param boolean $footer    whether the script should be included in the footer or not.
 *
 * @since 5.0.0
 */
function nab_register_script_with_auto_deps( $handle, $file_name, $footer ) {

	$asset = array(
		'dependencies' => array(),
		'version'      => nelioab()->plugin_version,
	);

	if ( file_exists( nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php" ) ) {
		// phpcs:ignore
		$asset = include nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	}//end if

	// NOTE. Add regenerator-runtime to all our scripts to make sure AsyncPaginate works.
	if ( is_wp_version_compatible( '5.8' ) ) {
		$asset['dependencies'] = array_merge( $asset['dependencies'], array( 'regenerator-runtime' ) );
	}//end if

	wp_register_script(
		$handle,
		nelioab()->plugin_url . "/assets/dist/js/$file_name.js",
		$asset['dependencies'],
		$asset['version'],
		$footer
	);

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( $handle, 'nelio-ab-testing' );
	}//end if

}//end nab_register_script_with_auto_deps()

/**
 * Enqueues a script loading the dependencies automatically.
 *
 * @param string  $handle    the script handle name.
 * @param string  $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param boolean $footer    whether the script should be included in the footer or not.
 *
 * @since 5.0.0
 */
function nab_enqueue_script_with_auto_deps( $handle, $file_name, $footer ) {

	nab_register_script_with_auto_deps( $handle, $file_name, $footer );
	wp_enqueue_script( $handle );

}//end nab_enqueue_script_with_auto_deps()

/**
 * This function creates a new type of experiment that affects a WordPress post.
 *
 * A WordPress post can either be a blog post, a page, or any other registered
 * custom post type. The specific post type it affects is specified in one of
 * the given arguments.
 *
 * @param string $name A string that uniquely identifies an experiment.
 * @param array  $args List of arguments to create the new post experiment.
 *
 * @see Nelio_AB_Testing_Experiment_Type_Manager::register_post_experiment()
 *
 * @since 5.0.0
 */
function nab_register_post_experiment_type( $name, $args ) {

	$manager = Nelio_AB_Testing_Experiment_Type_Manager::instance();
	$manager->register_post_experiment_type( $name, $args );

}//end nab_register_post_experiment_type()

/**
 * This function creates a new type of experiment that affects multiple pages
 * in WordPress.
 *
 * @param string $name A string that uniquely identifies an experiment.
 * @param array  $args List of arguments to create the new post experiment.
 *
 * @see Nelio_AB_Testing_Experiment_Type_Manager::register_post_experiment()
 *
 * @since 5.0.0
 */
function nab_register_global_experiment_type( $name, $args ) {

	$manager = Nelio_AB_Testing_Experiment_Type_Manager::instance();
	$manager->register_global_experiment_type( $name, $args );

}//end nab_register_global_experiment_type()

/**
 * This function creates a new type of conversion actions.
 *
 * @param string $name A string that uniquely identifies the conversion action.
 * @param array  $args List of arguments to create the new conversion action type.
 *
 * @see Nelio_AB_Testing_Conversion_Action_Type_Manager::register_conversion_action()
 *
 * @since 5.0.0
 */
function nab_register_conversion_action_type( $name, $args = array() ) {

	$manager = Nelio_AB_Testing_Conversion_Action_Type_Manager::instance();
	$manager->register_type( $name, $args );

}//end nab_register_conversion_action_type()

/**
 * This function returns the two-letter locale used in WordPress.
 *
 * @return string the two-letter locale used in WordPress.
 *
 * @since 5.0.0
 */
function nab_get_language() {

	// Language of the blog.
	$lang = get_option( 'WPLANG' );
	$lang = ! empty( $lang ) ? $lang : 'en_US';

	// Convert into a two-char string.
	if ( strpos( $lang, '_' ) > 0 ) {
		$lang = substr( $lang, 0, strpos( $lang, '_' ) );
	}//end if

	return $lang;

}//end nab_get_language()

/**
 * Returns the home URL.
 *
 * @param string $path Optional. Path relative to the home URL.
 *
 * @return string Returns the home URL.
 *
 * @since 5.0.16
 */
function nab_home_url( $path = '' ) {

	$path = preg_replace( '/^\/*/', '', $path );
	if ( ! empty( $path ) ) {
		$path = '/' . $path;
	}//end if

	/**
	 * Filters the home URL.
	 *
	 * @param string $url  Home URL using the given path.
	 * @param string $path Path relative to the home URL.
	 *
	 * @since 5.0.16
	 */
	return apply_filters( 'nab_home_url', home_url( $path ), $path );

}//end nab_home_url()

/**
 * Adds extra attributes to a script tag.
 *
 * @param string $tag A script opening tag.
 *
 * @return string unique ID.
 *
 * @since 5.0.22
 */
function nab_add_extra_script_attributes( $tag ) {

	/**
	 * Filters the attributes that should be added to a <script> tag.
	 *
	 * @param array $attributes an array where keys and values are the attribute names and values.
	 *
	 * @since 5.0.22
	 */
	$attributes = apply_filters( 'nab_add_extra_script_attributes', array() );
	$attributes = array_reduce(
		array_keys( $attributes ),
		function( $res, $key ) use ( $attributes ) {
			return $res . sprintf( ' %s="%s"', $key, esc_attr( $attributes[ $key ] ) );
		},
		''
	);

	return str_replace( '<script', '<script' . $attributes, $tag );
}//end nab_add_extra_script_attributes()

/**
 * Prints the given inline script.
 *
 * @param string $handler Script handler.
 * @param string $script  Inline script name, a URL, or the content of the script itself.
 * @param string $before  JS code to add before the script.
 * @param string $after   JS code to add after the script.
 *
 * @since 5.2.0
 */
function nab_print_inline_script( $handler, $script, $before = '', $after = '' ) {
	$is_file = file_exists( nelioab()->plugin_path . "/assets/dist/js/{$script}.js" );
	$is_url  = ! $is_file && empty( $before ) && empty( $after ) && filter_var( $script, FILTER_VALIDATE_URL );

	$content = '';
	if ( $is_file ) {
		// phpcs:ignore
		$content = file_get_contents( nelioab()->plugin_path . "/assets/dist/js/{$script}.js" );
	} elseif ( ! $is_url ) {
		$content = $script;
	}//end if

	/**
	 * Filters the attributes that should be added to a <script> tag.
	 *
	 * @param array $attributes an array where keys and values are the attribute names and values.
	 *
	 * @since 5.0.22
	 */
	$attributes = apply_filters( 'nab_add_extra_script_attributes', array() );
	$attributes = array_reduce(
		array_keys( $attributes ),
		function( $res, $key ) use ( $attributes ) {
			return $res . sprintf( ' %s="%s"', $key, esc_attr( $attributes[ $key ] ) );
		},
		''
	);

	echo nab_add_extra_script_attributes( // phpcs:ignore
		sprintf(
			'<script type="text/javascript" id="%s"%s>',
			esc_attr( $handler ),
			$is_url ? sprintf( ' src="%s"', esc_url( $script ) ) : ''
		)
	);

	$content = "{$before}{$content}{$after}";
	if ( ! empty( $content ) ) {
		printf(
			'/*%s*//* <![CDATA[ */%s/* ]]> */',
			esc_attr( $handler ),
			$content // phpcs:ignore
		);
	}//end if

	echo "</script>\n";
}//end nab_print_inline_script()

/**
 * Generates a unique ID.
 *
 * @return string unique ID.
 *
 * @since 5.0.0
 */
function nab_uuid() {

	$data    = random_bytes( 16 );
	$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
	$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );

}//end nab_uuid()

/**
 * Returns the post ID of a given URL.
 *
 * @param string $url a URL.
 *
 * @return int post ID or 0 on failure
 *
 * @since 5.2.6
 */
function nab_url_to_postid( $url ) {
	if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
		return wpcom_vip_url_to_postid( $url );
	}//end if

	// phpcs:ignore
	return url_to_postid( $url );
}//end nab_url_to_postid()

/**
 * Logs something on the screen if request contains “nablog”.
 *
 * @param any     $log what to log.
 * @param boolean $pre whether to wrap log in `<pre>` or not (i.e. HTML comment). Default: `false`.
 *
 * @since 5.3.4
 */
function nablog( $log, $pre = false ) {
	// phpcs:disable
	if ( ! isset( $_GET['nablog'] ) ) {
		return;
	}//end if
	echo $pre ? '<pre>' : "\n<!-- [NABLOG]\n";
	print_r( $log );
	echo $pre ? '</pre>' : "\n-->\n";
	// phpcs:enable
}//end nablog()

/**
 * Returns the queried object ID.
 *
 * @return int queried object ID.
 *
 * @since 5.2.9
 */
function nab_get_queried_object_id() {
	$id = get_queried_object_id();
	if ( $id ) {
		return $id;
	}//end if

	$id = absint( get_query_var( 'page_id' ) );
	if ( $id ) {
		return $id;
	}//end if

	$id = absint( get_query_var( 'p' ) );
	if ( $id ) {
		return $id;
	}//end if

	$type = get_query_var( 'post_type' );
	$name = get_query_var( 'name' );
	if ( ! empty( $type ) && ! empty( $name ) ) {
		if ( function_exists( 'wpcom_vip_get_page_by_path' ) ) {
			$post = wpcom_vip_get_page_by_path( $name, OBJECT, $type );
		} else {
			// phpcs:ignore
			$post = get_page_by_path( $name, OBJECT, $type );
		}//end if
		if ( ! empty( $post ) ) {
			return $post->ID;
		}//end if
	} elseif ( ! empty( $name ) ) {
		if ( function_exists( 'wpcom_vip_get_page_by_path' ) ) {
			$post = wpcom_vip_get_page_by_path( $name, OBJECT );
		} else {
			// phpcs:ignore
			$post = get_page_by_path( $name, OBJECT );
		}//end if
		if ( ! empty( $post ) ) {
			return $post->ID;
		}//end if
	}//end if

	global $wpdb;
	if ( ! empty( $type ) && ! empty( $name ) ) {
		$key = "nab/{$type}/$name";
		$id  = wp_cache_get( $key );
		if ( $id ) {
			return $id;
		}//end if

		$id = absint(
			// phpcs:ignore
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND p.post_name = %s",
					$type,
					$name
				)
			)
		);
		wp_cache_set( $key, $id );

		if ( $id ) {
			return $id;
		}//end if
	} elseif ( ! empty( $name ) ) {
		$key = "nab/nab-unknown/$name";
		$id  = wp_cache_get( $key );
		if ( $id ) {
			return $id;
		}//end if

		$id = absint(
			// phpcs:ignore
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts p WHERE p.post_name = %s",
					$name
				)
			)
		);
		wp_cache_set( $key, $id );

		if ( $id ) {
			return $id;
		}//end if
	}//end if

	return 0;
}//end nab_get_queried_object_id()
