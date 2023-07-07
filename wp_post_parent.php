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

$sync = $cn->eQuery( 'SELECT * FROM tyche_post_sync' );

while ( $data = $cn->fetchArray( $sync ) ) {

	if ( '' === $data['old_id'] || 0 === (int) $data['old_id'] || strlen( $data['old_id'] ) < 5 ) {
		continue;
	}

	$a = $cn->eQuery( "SELECT ID FROM wp_posts WHERE post_parent = '" . $data['old_id'] . "'" );

	$check = $cn->numRows( $a );

	if ( $check > 0 ) {

		while ( $b = $cn->fetchArray( $a ) ) {

			echo 'found post_parent data that needs to be updated. Post ID: ' . $b['ID'] . '. <br/>';

			$c = $cn->eQuery( "UPDATE wp_posts SET post_parent = '" . $data['new_id'] . "' WHERE ID = '" . $b['ID'] . "'" );

			if ( $c ) {
				echo 'For ID: ' . $b['ID'] . '. Updated Post Parent value from ' . $data['old_id'] . ' to ' . $data['new_id'] . '<br/>';
			} else {
				echo 'Error encountered while updating Post ID: ' . $b['ID'] . '<br/>';
			}

			echo '<br/>
            ...............................................
            </br><br/>';
		}
	}
}

echo 'end of script';
