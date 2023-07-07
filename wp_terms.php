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

$terms = $cn->eQuery( 'SELECT * FROM wp_terms2 WHERE term_id > 716' );

while ( $data = $cn->fetchArray( $terms ) ) {

	$a = $cn->eQuery( "INSERT INTO wp_terms(name,slug,term_group) VALUES('" . $data['name'] . "','" . $data['slug'] . "','" . $data['term_group'] . "')" );

	if ( $a ) {

		$post_id   = $cn->insertid();
		$post_type = 'wp_term';

		echo '<br/><br/>-----------------------------------------------------------------------<br/>
        created term. Former ID: ' . $data['term_id'] . '. New ID: ' . $post_id . '<br/>';

		$b = $cn->eQuery( "INSERT INTO tyche_post_sync ( post_type, type, old_id, new_id ) VALUES ( 'wp_term', '" . $post_type . "', '" . $data['term_id'] . "', '" . $post_id . "') " );

		if ( $b ) {
			echo 'Saved ID: ' . $data['term_id'] . ' to sync table<br/>';
		} else {
			echo 'ERROR saving ID: ' . $data['term_id'] . ' to sync table<br/>';
		}

		$b            = $cn->fetchArray( $cn->eQuery( "SELECT * FROM wp_term_taxonomy2 WHERE term_taxonomy_id = '" . $data['term_id'] . "'" ) );
		$_taxonomy    = $b['taxonomy'];
		$_description = $b['description'];
		$_parent      = $b['parent'];
		$_count       = $b['count'];

		$b = $cn->eQuery( "INSERT INTO wp_term_taxonomy(term_taxonomy_id, term_id,taxonomy,description,parent,count) VALUES('" . $post_id . "','" . $post_id . "','" . $_taxonomy . "','" . $_description . "','" . $_parent . "','" . $_count . "')" );

		if ( $b ) {
			echo 'Saved Taxonomy Entry for ID: ' . $data['term_id'] . '<br/>';
		} else {
			echo 'ERROR saving Taxonomy Entry for ID: ' . $data['term_id'] . '<br/>';
		}

		$post_meta = $cn->eQuery( 'SELECT * FROM wp_termmeta2 WHERE term_id = ' . $data['term_id'] );

		if ( $cn->numRows( $post_meta ) > 0 ) {

			echo 'Term meta has been found for ' . $data['term_id'] . '<br/>';

			while ( $meta = $cn->fetchArray( $post_meta ) ) {

				$b = $cn->eQuery( "INSERT INTO wp_termmeta( term_id, meta_key, meta_value ) VALUES ( '" . $post_id . "', '" . $cn->escape_( $meta['meta_key'] ) . "', '" . $cn->escape_( $meta['meta_value'] ) . "')" );

				$meta_id = $cn->insertid();

				if ( $b ) {
					echo 'Term meta has been saved.<br/>';
					$cn->eQuery( "INSERT INTO tyche_post_sync ( post_type, type, old_id, new_id ) VALUES ( 'wp_termmeta', '" . $post_type . "', '" . $meta['meta_id'] . "', '" . $meta_id . "') " );
				} else {
					echo 'Error while inserting meta for ID: ' . $meta['meta_id'] . '<br/>';
				}
			}
		} else {
			echo 'No term meta found for ' . $data['term_id'] . '<br/>';
		}
	} else {
		echo 'Error encountered for Term ID ' . $data['term_id'] . '<br/>';
	}
}
