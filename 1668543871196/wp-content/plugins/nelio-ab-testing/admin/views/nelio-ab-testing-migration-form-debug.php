<?php
/**
 * Displays a simple UI for debugging an experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div id="nab-migration-form-debug" style="display:none;">
	<div style="background:#fcfcfc; margin:1em 0 2em; padding:1em; border-radius:5px; display:inline-block">

		<h2 style="margin:0 0 1em;">Migration Form</h2>

		<table>
		<?php
			$html = '<tr><td style="padding-right:1em">%2$s</td><td><input name="%1$s" type="text" value="%3$s" /></td></tr>' . "\n";

			$default_params = array(
				'experimentKey'  => array(),
				'credentials'    => array(),
				'status'         => '',
				'migrateClicks'  => false,
				'ttl'            => 'finished' === $experiment->get_status() ? 0 : 900,
				'startDate'      => $experiment->get_start_date(),
				'endDate'        => $experiment->get_end_date(),
				'timezone'       => nab_get_timezone(),
				'alternativeIds' => array(),
				'goalIds'        => array(),
			);

			$default_credentials = array(
				'customerId'         => '',
				'registrationNumber' => '',
				'siteId'             => '',
				'siteUrl'            => get_option( 'siteurl' ),
			);

			$default_experiment_key = array(
				'kind' => '',
				'id'   => '',
			);

			$params         = get_post_meta( $experiment_id, '_nab_result_migration_params', true );
			$params         = ! empty( $params ) ? $params : array( 'params' => array() );
			$params         = wp_parse_args( $params['params'], $default_params );
			$credentials    = wp_parse_args( $params['credentials'], $default_credentials );
			$experiment_key = wp_parse_args( $params['experimentKey'], $default_experiment_key );

			$field = 'customerId';
			$label = 'Customer ID';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( $credentials[ $field ] ) ); // phpcs:ignore

			$field = 'registrationNumber';
			$label = 'Registration Number';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( $credentials[ $field ] ) ); // phpcs:ignore

			$field = 'siteId';
			$label = 'Site ID';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( $credentials[ $field ] ) ); // phpcs:ignore

			echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

			$field = 'kind';
			$label = 'Experiment Kind';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( $experiment_key[ $field ] ) ); // phpcs:ignore

			$field = 'id';
			$label = 'Experiment ID';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( $experiment_key[ $field ] ) ); // phpcs:ignore

			$field = 'alternativeIds';
			$label = 'Alternative IDs';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( implode( ',', $params[ $field ] ) ) ); // phpcs:ignore

			$field = 'goalIds';
			$label = 'Goal IDs';
			printf( $html, esc_attr( $field ), esc_html( $label ), esc_attr( implode( ',', $params[ $field ] ) ) ); // phpcs:ignore

			echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

			$field = 'migrateClicks';
			$label = 'Migrate clicks';
			printf(
				'<tr><td></td><td><input name="%1$s" type="checkbox" %3$s /> %2$s</td></tr>',
				esc_attr( $field ),
				esc_html( $label ),
				checked( $params[ $field ], true, false )
			);

			?>
		</table>

		<p id="nab-validation-errors" style="color:#a00"></p>
		<div style="text-align:right">
			<br>
			<input id="nab-migrate" class="button" type="button" value="Migrate" />
		</div>

	</div>
</div><!-- .migration-form-debug -->

<script type="text/javascript">
(function() {

	const button = document.getElementById( 'nab-migrate' );

	const fields = {
		customerId: document.querySelector( 'input[name="customerId"]' ),
		registrationNumber: document.querySelector( 'input[name="registrationNumber"]' ),
		siteId: document.querySelector( 'input[name="siteId"]' ),
		expKind: document.querySelector( 'input[name="kind"]' ),
		expId: document.querySelector( 'input[name="id"]' ),
		alts: document.querySelector( 'input[name="alternativeIds"]' ),
		goals: document.querySelector( 'input[name="goalIds"]' ),
		migrateClicks: document.querySelector( 'input[name="migrateClicks"]' ),
	};

	const validators = [
		[ () => /^[0-9]{16}$/.test( fields.customerId.value ), 'Invalid customer ID.' ],
		[ () => /^....-.....-....-.....-....$/.test( fields.registrationNumber.value ), 'Invalid registration number.' ],
		[ () => /^[0-9]{16}$/.test( fields.siteId.value ), 'Invalid site ID.' ],
		[ () => 10 < fields.expKind.value.length, 'Experiment kind doesn’t look right.' ],
		[ () => /^[0-9]{16}$/.test( fields.expId.value ), 'Invalid experiment ID.' ],
		[ () => fields.alts.value.split( ',' ).length === <?php echo wp_json_encode( count( $experiment->get_alternatives() ) ); ?>, 'Alternative count doesn’t match the expected value.' ],
		[ () => fields.goals.value.split( ',' ).length === <?php echo wp_json_encode( count( $experiment->get_goals() ) ); ?>, 'Goal count doesn’t match the expected value.' ],
		[ () => fields.goals.value.split( ',' ).reduce( ( acc, goal ) => acc && /^[0-9]{16}$/.test( goal.trim() ), true ), 'Invalid goal IDs.' ],
	];

	const debounce = ( fn, delay ) => {
		let timeout;
		return () => {
			if ( timeout ) {
				clearTimeout( timeout );
			}//end if
			timeout = setTimeout( () => {
				fn();
			}, delay );
		};
	};

	const validate = debounce( () => {

		const validation = validators.reduce(
			( { isValid, reason }, [ fn, newReason ] ) => {
				if ( ! isValid || fn() ) {
					return { isValid, reason };
				}
				return { isValid: false, reason: newReason };
			},
			{ isValid: true, reason: '' }
		);
		document.getElementById( 'nab-validation-errors' ).innerHTML = validation.reason && '\u26D4 ' + validation.reason;
		button.disabled = ! validation.isValid;
	}, 500 );

	[ ...document.querySelectorAll( '#nab-migration-form-debug input[type="text"]' ) ].forEach( ( x ) => {
		x.addEventListener( 'keyup', validate );
		x.addEventListener( 'change', validate );
	} );
	validate();

	button.addEventListener( 'click', () => {
		[ ...document.querySelectorAll( '#nab-migration-form-debug input' ) ].forEach( ( x ) => x.disabled = true );
		button.value = 'Migrating…';
		wp.apiFetch( {
			path: '/nab/v1/experiment/<?php echo wp_json_encode( $experiment_id ); ?>/debug/migrate-results',
			method: 'PUT',
			data: {
				...<?php echo wp_json_encode( $params ); ?>,
				credentials: {
					customerId: fields.customerId.value,
					registrationNumber: fields.registrationNumber.value,
					siteId: fields.siteId.value,
					siteUrl: <?php echo wp_json_encode( $credentials['siteUrl'] ); ?>,
				},
				experimentKey: {
					kind: fields.expKind.value,
					key: fields.expId.value,
				},
				alternativeIds: fields.alts.value.split( ',' ).map( ( x ) => x.trim() ),
				goalIds: fields.goals.value.split( ',' ).map( ( x ) => x.trim() ),
				migrateClicks: fields.migrateClicks.checked,
			},
		} ).then(
			() => button.value = 'Done!',
			( error ) => {
				[ ...document.querySelectorAll( '#nab-migration-form-debug input' ) ].forEach( ( x ) => x.disabled = false );
				button.value = 'Migrate';
				alert( 'Error: ' + error.code );
			}
		);
	} );

})();
</script>
