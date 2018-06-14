<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 4/19/18
 * Time: 3:48 PM
 */

class Brizy_Public_AssetProxy extends Brizy_Public_AbstractProxy {

	const ENDPOINT = 'brizy';

	/**
	 * @return string
	 */
	protected function get_endpoint_key() {
		return self::ENDPOINT;
	}

	public function process_query() {
		global $wp_query;

		// Check if user is not querying API
		if ( ! isset( $wp_query->query_vars[ self::ENDPOINT ] ) || ! is_string( $wp_query->query_vars[ self::ENDPOINT ] ) ) {
			return;
		}

		$asset_path = "/" . ltrim( $wp_query->query_vars[ self::ENDPOINT ], "/" );
		$asset_url  = $this->url_builder->external_asset_url( $asset_path );

		$tmp_asset_url = $this->url_builder->editor_asset_path( $asset_path );
		$new_path      = $this->url_builder->upload_path( $tmp_asset_url );

		if ( ! file_exists( $new_path ) ) {
			$store_result = $this->store_file( $asset_url, $new_path );

			if ( ! $store_result ) {
				global $wp_query;
				$wp_query->set_404();

				return;
			}
		}

		if ( file_exists( $new_path ) ) {

			$content = file_get_contents( $new_path );

			// send headers
			$headers                   = array();
			$headers['Content-Type']   = $this->get_mime( $new_path, 1 );
			$headers['Content-Length'] = strlen( $content );
			$headers['Cache-Control']  = 'max-age=600';

			foreach ( $headers as $key => $val ) {
				if ( is_array( $val ) ) {
					$val = implode( ', ', $val );
				}

				header( "{$key}: {$val}" );
			}
			// send file content
			echo $content;
			exit;
		}


		global $wp_query;
		$wp_query->set_404();

		return;

	}

}