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

$posts = $cn->eQuery( "SELECT * FROM wp_posts2 WHERE post_author IN (6009,1,19450,3048,20151,18040,41,5984,5298,6430) OR post_type = 'download'" ); // 6009,1,19450,3048,20151,18040,41,5984,5298,6430 - IDs of authors who have created posts that we feel are important enough to be imported.

while ( $data = $cn->fetchArray( $posts ) ) {

	$post_type = $data['post_type'];

	if ( in_array( $post_type, array( 'edd_discount', 'edd_log', 'edd_license_log' ) ) ) {

		// We don't need to import EDD data to the live site.
		continue;
	}

	$post_author = $data['post_author'];
	$post_title  = $data['post_title'];
	$post_name   = $data['post_name'];

	echo '<br/><br/>____________________________________________________________________________<br/>';
	echo 'Checking if POST exists<br/>';

	$a = $cn->eQuery( "SELECT ID, post_type, post_content, post_excerpt FROM wp_posts WHERE post_author = '" . $post_author . "' AND post_name = '" . $post_name . "'" );

	if ( $cn->numRows( $a ) ) {

		// data has been found, so we see if we can update stale content.

		$b = $cn->fetchArray( $a );

		$post_types_to_update = array( 'custom_css', 'customize_changeset', 'html-form', 'elementor_snippet', 'download' );

		if ( in_array( $post_type, $post_types_to_update ) && ( $b['post_content'] !== $data['post_content'] || $b['post_excerpt'] !== $data['post_excerpt'] ) ) {

			echo 'Found stale content for Post Type: ' . $post_type . ' AND ID: ' . $b['ID'] . '<br/>';

			$c = $cn->eQuery( "UPDATE wp_posts SET post_content = '" . $cn->escape_( $data['post_content'] ) . "', post_excerpt = '" . $cn->escape_( $data['post_excerpt'] ) . "' WHERE ID = '" . $b['ID'] . "'" );

			if ( $c ) {
				echo 'Successfully update post content<br/>';
			} else {
				echo 'ERROR: Update post content unsuccessful<br/>';
			}
		} else {
			echo 'Skipping ... Post data found for Post Name: ' . $post_name . '<br/>';
		}

		continue;
	}

	if ( 'download' === $data['post_type'] ) {
		continue;
	}

	$a = $cn->eQuery( 'INSERT INTO wp_posts(post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count) VALUES( ' . $data['post_author'] . ", '" . $data['post_date'] . "', '" . $data['post_date_gmt'] . "', '" . $cn->escape_( $data['post_content'] ) . "', '" . $cn->escape_( $data['post_title'] ) . "', '" . $cn->escape_( $data['post_excerpt'] ) . "', '" . $cn->escape_( $data['post_status'] ) . "', '" . $cn->escape_( $data['comment_status'] ) . "', '" . $cn->escape_( $data['ping_status'] ) . "', '" . $cn->escape_( $data['post_password'] ) . "', '" . $cn->escape_( $data['post_name'] ) . "', '" . $cn->escape_( $data['to_ping'] ) . "', '" . $data['pinged'] . "', '" . $cn->escape_( $data['post_modified'] ) . "', '" . $cn->escape_( $data['post_modified_gmt'] ) . "', '" . $cn->escape_( $data['post_content_filtered'] ) . "', " . $data['post_parent'] . ", '" . $cn->escape_( $data['guid'] ) . "', " . $data['menu_order'] . ", '" . $cn->escape_( $data['post_type'] ) . "','" . $cn->escape_( $data['post_mime_type'] ) . "'," . $data['comment_count'] . ')' );

	if ( $a ) {

		$post_id   = $cn->insertid();
		$post_type = $data['post_type'];

		echo 'created post. Former ID: ' . $data['ID'] . '. New ID: ' . $post_id;

		$cn->eQuery( "INSERT INTO tyche_post_sync ( post_type, type, old_id, new_id ) VALUES ( 'wp_post', '" . $post_type . "', '" . $data['ID'] . "', '" . $post_id . "') " );

		$post_meta = $cn->eQuery( 'SELECT * FROM wp_postmeta2 WHERE post_id = ' . $data['ID'] );

		if ( $cn->numRows( $post_meta ) > 0 ) {

			echo 'Postmeta has been found for ' . $data['ID'] . '<br/>';

			while ( $meta = $cn->fetchArray( $post_meta ) ) {

				$b = $cn->eQuery( "INSERT INTO wp_postmeta( post_id, meta_key, meta_value ) VALUES ( '" . $post_id . "', '" . $cn->escape_( $meta['meta_key'] ) . "', '" . $cn->escape_( $meta['meta_value'] ) . "')" );

				$meta_id = $cn->insertid();

				if ( $b ) {
					$cn->eQuery( "INSERT INTO tyche_post_sync ( post_type, type, old_id, new_id ) VALUES ( 'post_meta', '" . $post_type . "', '" . $meta['meta_id'] . "', '" . $meta_id . "') " );
				} else {
					echo 'Error while inserting meta for ID: ' . $meta['meta_id'];
				}
			}
		} else {
			echo 'No postmeta found for ' . $data['ID'] . '<br/>';
		}
	} else {
		echo 'Error encountered for Post ID ' . $data['ID'] . '<br/>';
	}
}

echo 'End of script';
