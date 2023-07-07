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

$records = $cn->eQuery( "SELECT * FROM tyche_post_sync WHERE old_id != 0 AND old_id != ''" );

while ( $dt = $cn->fetchArray( $records ) ) {

	$old_id = $dt['old_id'];
	$new_id = $dt['new_id'];

	$terms = $cn->eQuery( "SELECT * FROM wp_term_relationships2 WHERE object_id = '" . $old_id . "'" );

	if ( $cn->numRows( $terms ) > 0 ) {

		while ( $data = $cn->fetchArray( $terms ) ) {

			$object_id        = $data['object_id'];
			$term_taxonomy_id = $data['term_taxonomy_id'];
			$term_order       = $data['term_order'];
			$new_object_id    = $new_id;
			$taxonomy_id      = $term_taxonomy_id;

			echo '<br/><br/>_____________________________________________________________________________<br/>';

			echo 'Found Sync entry for Object ID: ' . $object_id . '. New Object ID: ' . $new_object_id . '<br/>';

			$c = $cn->eQuery( "SELECT * FROM tyche_post_sync WHERE old_id = '" . $term_taxonomy_id . "' AND post_type = 'wp_term'" );

			if ( $cn->numRows( $c ) > 0 ) {
				$d           = $cn->fetchArray( $c );
				$taxonomy_id = $d['new_id'];
				echo 'Found Sync entry for Taxonomy ID: ' . $term_taxonomy_id . '. New Taxonomy ID: ' . $taxonomy_id . '<br/>';
			}

			echo 'checking if wp_relationship data exists for object_id: ' . $new_object_id . '<br/>';

			$check = $cn->numRows( $cn->eQuery( "SELECT object_id FROM wp_term_relationships WHERE object_id = '" . $new_object_id . "' AND term_taxonomy_id = '" . $term_taxonomy_id . "'" ) );

			if ( $check > 0 ) {

				echo 'Yes, it exists<br/>';

				if ( $taxonomy_id !== $term_taxonomy_id ) {

					echo 'Now UPDATING wp_term_relationships entry for existing object id: ' . $new_object_id . '.<br/>';

					$e = $cn->eQuery( "UPDATE wp_term_relationships SET term_taxonomy_id = '" . $taxonomy_id . "', term_order = '" . $term_order . "' WHERE object_id = '" . $new_object_id . "' AND term_taxonomy_id = '" . $term_taxonomy_id . "'" );

					if ( $e ) {
						echo 'UPDATED existing Taxonomy Relationship data.<br/>';
					} else {
						echo 'Error encountered while updating.<br/>';
					}
				} else {
					echo 'No need to update as term_taxonomy_id has not changed.<br/>';
				}
			} else {

				echo 'No, it does not exist<br/>';

				echo 'Now creatng wp_term_relationships entry.<br/>';

				$e = $cn->eQuery( "INSERT INTO wp_term_relationships(object_id,term_taxonomy_id,term_order) VALUES('" . $new_object_id . "','" . $taxonomy_id . "','" . $term_order . "')" );

				if ( $e ) {
					echo 'saved new Taxonomy Relationship data.<br/>';
				} else {
					echo 'Error encountered while saving.<br/>';
				}
			}
		}
	}
}

echo 'End of scrit';
