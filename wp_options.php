<?php
require_once 'db.php';

$server   = 'localhost';
$database = '';
$username = '';
$password = '';

$cn = new Database( $server, $username, $password, $database );
$cn->displayError( true );

if ( ! $cn->connect() ) {
	echo '<strong>:: error connecting to the database ::</strong>';
	exit;
}

$options = $cn->eQuery( "SELECT * FROM `wp_options` WHERE `option_name` LIKE '%elementor%'" );

while ( $data = $cn->fetchArray( $options ) ) {

	$option_value = $data['option_value'];
	$option_id    = $data['option_id'];

	$a = $cn->eQuery( 'SELECT * FROM tyche_post_sync' );

	while ( $b = $cn->fetchArray( $a ) ) {

		if ( '' === $b['old_id'] || 0 === (int) $b['old_id'] || strlen( $b['old_id'] ) < 5 ) {
			continue;
		}

		$position = strpos( $option_value, $b['old_id'] );
		$start    = ( $position - 4 ) <= 0 ? 0 : ( $position - 4 );

		if ( false !== $position ) {

			if ( '' !== substr( $option_value, ( $position - 1 ), 1 ) && '' !== substr( $option_value, ( $position + strlen( $b['old_id'] ) ), 1 ) ) {

				echo 'invalid';

				if ( is_numeric( substr( $option_value, ( $position - 1 ), 1 ) ) || is_numeric( substr( $option_value, ( $position + strlen( $b['old_id'] ) ), 1 ) ) ) {

					echo 'ID ' . $option_id . ' detected is not valid<br/>';
					echo substr( $data['option_value'], $start, 15 ) . '<br/>';
					echo 'skipping<br/>';
					echo '<br/>
				...............................................
				</br><br/>';
					continue;
				}
			}

			echo '<br/><br/>_____________________________________________________________________________<br/>';

			$option_value = str_replace( $b['old_id'], $b['new_id'], $option_value );
			$start        = ( $position - 4 ) <= 0 ? 0 : ( $position - 4 );
			
			echo 'found an old record for option ID: ' . $option_id . '<br/>';

			$c = $cn->eQuery( "UPDATE wp_options SET option_value = '" . $cn->escape_( $option_value ) . "' WHERE option_id = '" . $option_id . "'" );

			if ( $c ) {
				echo 'saved new option ID from ' . $b['old_id'] . ' to ' . $b['new_id'] . '<br/>';
				echo 'found here: <br/><br/>';
				echo substr( $data['option_value'], $start, 15 );
			} else {
				echo 'Error encountered while saving for option ID: ' . $option_id;
			}

			echo '<br/>
			...............................................
			</br><br/>';
		}
	}
}
