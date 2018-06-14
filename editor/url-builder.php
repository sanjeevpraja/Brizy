<?php

class Brizy_Editor_UrlBuilder {

	/**
	 * @var Brizy_Editor_Project
	 */
	protected $project;

	/**
	 * @var Brizy_Editor_Post
	 */
	protected $post;

	/**
	 * @var array
	 */
	protected $upload_dir;

	/**
	 * Brizy_Editor_UrlBuilder constructor.
	 *
	 * @param Brizy_Editor_Project|null $project
	 * @param Brizy_Editor_Post|null $post
	 */
	public function __construct( $project = null, $post = null ) {

		$this->project    = $project;
		$this->post       = $post;
		$this->upload_dir = wp_upload_dir( null, true );

//		if ( $wp_rewrite->permalink_structure == "" ) {
//			$site_url = get_site_url();
//			$this->upload_dir['url']     = str_replace( $site_url, $site_url . "/index.php", $this->upload_dir['url'] );
//			$this->upload_dir['baseurl'] = str_replace( $site_url, $site_url . "/index.php", $this->upload_dir['baseurl'] );
//		}
	}

	public function application_form_url() {
		return sprintf( Brizy_Config::BRIZY_APPLICATION_FORM_URL, Brizy_Config::BRIZY_APPLICATION_FORM_ID, urlencode( $this->multipass_url() ) );
	}
	/**
	 * @param $post
	 */
	public function set_post( $post ) {
		$this->post = $post;
	}

	public function multipass_url() {
		return admin_url( 'admin-ajax.php' ) . "?action=brizy_multipass_create&client_id=" . Brizy_Config::BRIZY_APPLICATION_FORM_ID;
	}

	/**
	 * @param string $end_point
	 *
	 * @return string
	 */
	public function proxy_url( $end_point = '' ) {
		return site_url() . '?brizy=' . $end_point;
	}

	/**
	 * @param string $end_point
	 *
	 * @return string
	 */
	public function media_proxy_url( $end_point = '' ) {

		$end_point = ltrim( $end_point, "/" );

		return $this->proxy_url( "/media/" . $end_point );
	}

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public function upload_path( $path = null ) {
		return $this->upload_dir['basedir'] . $path;
	}

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public function upload_url( $path = null ) {
		return $this->upload_dir['baseurl'] . $path;
	}

	/**
	 * This will return the relative path to the upload dir.
	 * ex: /brizy/pages/3/x.jpg
	 *
	 * @param null $path
	 * @param null $post_id
	 *
	 * @return string
	 */
	public function page_asset_path( $path = null, $post_id = null ) {

		if ( is_null( $post_id ) ) {
			$post_id = $this->post->get_id();
		}

		if ( $path ) {
			$path = "/" . ltrim( $path, "/" );
		}

		return sprintf( Brizy_Config::LOCAL_PAGE_ASSET_STATIC_URL . $path, $post_id );
	}

	/**
	 * @param null $path
	 * @param null $post_id
	 *
	 * @return string
	 */
	public function page_asset_url( $path = null, $post_id = null ) {

		return $this->upload_url( $this->page_asset_path( $path, $post_id ) );
	}

	/**
	 * This will return the relative path to the upload dir.
	 * ex: /brizy/editor/9.0.3/x.jpg
	 *
	 * @param null $path
	 * @param null $template_version
	 *
	 * @return string
	 */
	public function editor_asset_path( $path = null, $template_version = null ) {

		if ( is_null( $template_version ) ) {
			$template_version = BRIZY_EDITOR_VERSION;
		}

		if ( $path ) {
			$path = "/" . ltrim( $path, "/" );
		}

		return sprintf( Brizy_Config::BRIZY_WP_EDITOR_ASSET_PATH . $path, $template_version );
	}

	public function editor_asset_url() {
		return rtrim( BRIZY_PLUGIN_URL, "/" ) . '/public/editor-build/' . BRIZY_EDITOR_VERSION;
	}

	/**
	 * This will return the relative path to the upload dir.
	 * ex: /brizy/media/media_file.jpg
	 *
	 * @param null $path
	 *
	 * @return string
	 */
	public function media_asset_path( $path = null ) {
		return Brizy_Config::LOCAL_PAGE_MEDIA_STATIC_URL . $path;
	}

	/**
	 * @param null $path
	 *
	 * @return string
	 */
	public function external_media_url( $path = null ) {
		$path = "/" . ltrim( $path, "/" );

		return Brizy_Config::MEDIA_IMAGE_URL . $path;
	}

	/**
	 * @param null $path
	 * @param null $template_version
	 * @param null $template_slug
	 *
	 * @return string
	 */
	public function external_asset_url( $path = null, $template_version = null, $template_slug = null ) {

		if ( is_null( $template_slug ) ) {
			$template_slug = $this->project->get_template_slug();
		}
		if ( is_null( $template_version ) ) {
			$template_version = BRIZY_EDITOR_VERSION;
		}

		if ( $path ) {
			$path = "/" . ltrim( $path, "/" );
		}

		return sprintf( Brizy_Config::S3_ASSET_URL . $path, $template_slug, $template_version );
	}
}

