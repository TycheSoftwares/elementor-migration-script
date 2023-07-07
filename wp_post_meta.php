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

$posts = $cn->eQuery( "SELECT * FROM wp_posts WHERE ID >= 616229  OR post_type = 'download'" ); // 616229 is the last ID in the wp_posts table.

while ( $data = $cn->fetchArray( $posts ) ) {

	$post_id = $data['ID'];

	$meta = $cn->eQuery( "SELECT * FROM wp_postmeta WHERE post_id = '" . $post_id . "'" );

	while ( $metadata = $cn->fetchArray( $meta ) ) {

		$meta_id    = $metadata['meta_id'];
		$meta_key   = $metadata['meta_key'];
		$meta_value = $metadata['meta_value'];

		$a = $cn->eQuery( 'SELECT * FROM tyche_post_sync WHERE old_id != 0' );

		while ( $b = $cn->fetchArray( $a ) ) {

			if ( '' === $b['old_id'] || 0 === (int) $b['old_id'] || strlen( $b['old_id'] ) < 5 ) {
				// echo "skipping - invalid old_id: " . $b['old_id'] . "<br/>";
				continue;
			}

			if ( in_array( $meta_key, array( 'wpil_links_inbound_internal_count_data', 'wpil_links_outbound_internal_count_data', 'wpil_add_links' ) ) ) {
				// echo "skipping - invalid internal_count_data<br/>";
				continue;
			}

			if ( 'download' === $data['post_type'] && '_elementor_data' !== $meta_key ) {
				// echo "skipping - download post_type<br/>";
				continue;
			}

			$position = strpos( $meta_value, $b['old_id'] );
			if ( false !== $position ) {

				if ( '' !== substr( $meta_value, ( $position - 1 ), 1 ) && '' !== substr( $meta_value, ( $position + strlen( $b['old_id'] ) ), 1 ) ) {

					$start = ( $position - 4 ) <= 0 ? 0 : ( $position - 4 );

					if ( is_numeric( substr( $meta_value, ( $position - 1 ), 1 ) ) ||
					is_numeric( substr( $meta_value, ( $position + strlen( $b['old_id'] ) ), 1 ) ) ||
					'age-' === substr( $meta_value, ( $position - 4 ), 4 )
					) {

						// echo "skipping - invalid meta_value: " . $meta_value . "<br/>";

						/*
						echo "ID " . $meta_id ." detected is not valid<br/>";
						echo substr( $meta_value, $start, 15 )."<br/>";
						echo "skipping<br/>";
						echo "<br/>
						...............................................
						</br><br/>";
						*/
						continue;
					}
				}

				echo '<br/><br/> _____________________________________________________________________________<br/>';

				$meta_value = str_replace( $b['old_id'], $b['new_id'], $meta_value );
				echo 'found an old record for meta ID: ' . $meta_id . '<br/>';

				$c = $cn->eQuery( "UPDATE wp_postmeta SET meta_value = '" . $cn->escape_( $meta_value ) . "' WHERE meta_id = '" . $meta_id . "'" );

				if ( $c ) {
					echo 'saved new meta ID from ' . $b['old_id'] . ' to ' . $b['new_id'] . '<br/>';
					echo 'meta_key: ' . $meta_key . '<br/>';
					echo 'found here: <br/><br/>';
					echo substr( $metadata['meta_value'], $start, 15 );
				} else {
					echo 'Error encountered while saving for option ID: ' . $meta_id;
				}

				echo '<br/>
                ...............................................
                </br><br/>';
			}
		}
	}
}

echo 'End of script';
