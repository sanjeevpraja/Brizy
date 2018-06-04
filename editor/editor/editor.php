<?php

class Brizy_Editor_Editor_Editor {
	/**
	 * @var Brizy_Editor_Post
	 */
	private $post;

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * @var Brizy_Editor_UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * @param Brizy_Editor_Project $project
	 * @param Brizy_Editor_Post $post
	 *
	 * @return Brizy_Editor_Editor_Editor
	 */
	public static function get( Brizy_Editor_Project $project, Brizy_Editor_Post $post = null ) {
		return new self( $project, $post );
	}

	/**
	 * Brizy_Editor_Editor_Editor constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 * @param Brizy_Editor_Post $post
	 */
	public function __construct( Brizy_Editor_Project $project, Brizy_Editor_Post $post = null ) {
		$this->post       = $post;
		$this->project    = $project;
		$this->urlBuilder = new Brizy_Editor_UrlBuilder( $project, $post );
	}

	/**
	 * @return Brizy_Editor_Post
	 * @throws Exception
	 */
	protected function get_post() {

		if ( ! ( $this->post instanceof Brizy_Editor_Post ) ) {
			$class = get_class( $this );
			throw new Exception( "The class {$class} must be initialize with all parameters" );
		}

		return $this->post;
	}

	/**
	 * @throws Exception
	 */
	public function config() {

		global $wp_registered_sidebars;

		$wp_post_id          = null;
		$preview_post_link   = null;
		$change_template_url = null;
		$templates           = null;

		if ( ! is_null( $this->post ) ) {
			$wp_post_id = $this->post->get_wp_post()->ID;

			$preview_post_link = get_preview_post_link( $this->post->get_wp_post(), array(
				'preview_id'    => $wp_post_id,
				'preview_nonce' => wp_create_nonce( 'post_preview_' . $wp_post_id )
			) );

			$change_template_url = admin_url( 'admin-post.php?post=' . $this->get_post()->get_id() . '&action=_brizy_change_template' );
			$templates           = $this->post->get_templates();
		}

		$config = array(
			'hosts'           => array(
				'api'     => Brizy_Config::EDITOR_HOST_API,
				'base'    => Brizy_Config::EDITOR_HOST_BASE,
				'origin'  => Brizy_Config::EDITOR_HOST_ORIGIN,
				'primary' => Brizy_Config::EDITOR_HOST_PRIMARY,
			),
			'project'         => $this->project->get_id(),
			'projectLanguage' => array(
				'id'      => 7,
				'variant' => array(
					'id'   => 7,
					'name' => 'A',
				),
			),
			'serverTimestamp' => time(),
			'urls'            => array(
				'api'                 => home_url( '/wp-json/v1' ),
				'base'                => Brizy_Config::EDITOR_BASE_URL,
				'integration'         => Brizy_Config::EDITOR_INTEGRATION_URL,
				'image'               => Brizy_Config::MEDIA_IMAGE_URL,
				'origin'              => Brizy_Config::EDITOR_ORIGIN_URL,
				'pagePreview'         => $preview_post_link,
				'pluginSettings'      => admin_url( 'admin.php?page=' . Brizy_Admin_Settings::menu_slug() ),
				'change_template_url' => $change_template_url,
				'backToWordpress'     => get_edit_post_link( $wp_post_id, null ),
				'assets'              => $this->urlBuilder->editor_asset_url(),
				'pageAssets'          => $this->urlBuilder->page_asset_url(),
				'blockThumbnails'     => $this->urlBuilder->external_asset_url( 'template/img-block-thumbs' ),
				'templateIcons'       => $this->urlBuilder->proxy_url( 'template/icons' ),
				'site'                => site_url()
			),
			'user'            => $this->project->get_id(),
			'wp'              => array(
				'permalink'       => get_permalink( $wp_post_id ),
				'page'            => $wp_post_id,
				'pageAttachments' => array( 'images' => $this->get_page_attachments() ),
				'templates'       => $templates,
				'api'             => array(
					'hash'             => wp_create_nonce( Brizy_Editor_API::nonce ),
					'url'              => admin_url( 'admin-ajax.php' ),
					'globals'          => array(
						'set' => Brizy_Editor_API::AJAX_SET_GLOBALS,
						'get' => Brizy_Editor_API::AJAX_GET_GLOBALS,
					),
					'media'            => Brizy_Editor_API::AJAX_MEDIA,
					'ping'             => Brizy_Editor_API::AJAX_PING,
					'getPage'          => Brizy_Editor_API::AJAX_GET,
					'updatePage'       => Brizy_Editor_API::AJAX_UPDATE,
					'getSidebars'      => Brizy_Editor_API::AJAX_SIDEBARS,
					'buildContent'     => Brizy_Editor_API::AJAX_BUILD,
					'sidebarContent'   => Brizy_Editor_API::AJAX_SIDEBAR_CONTENT,
					'shortcodeContent' => Brizy_Editor_API::AJAX_SHORTCODE_CONTENT,
					'shortcodeList'    => Brizy_Editor_API::AJAX_SHORTCODE_LIST,
					'getTemplates'     => Brizy_Editor_API::AJAX_GET_TEMPLATES,
					'getInternalLinks' => Brizy_Editor_API::AJAX_GET_INTERNAL_LINKS,
					'getMenus'         => Brizy_Editor_API::AJAX_GET_MENU_LIST,
					'updatePost'       => Brizy_Editor_API::AJAX_SAVE_TRIGGER,
					'savePage'         => Brizy_Editor_API::AJAX_SAVE_TRIGGER,
					'getTerms'         => Brizy_Editor_API::AJAX_GET_TERMS,
					'downloadMedia'    => Brizy_Editor_API::AJAX_DOWNLOAD_MEDIA,
				),
				'plugins'         => array(
					'dummy'       => true,
					'woocommerce' => $this->get_woocomerce_plugin_info(),
				),
				'hasSidebars' => count( $wp_registered_sidebars ) > 0,
				'l10n'        => Brizy_Languages_Texts::get_editor_texts()
			),
			'applications'    => array(
				'form' => array(
					'iframeUrl' => $this->urlBuilder->application_form_url(),
					'apiUrl'    => Brizy_Config::BRIZY_APPLICATION_INTEGRATION_URL,
					'wpApiUrl'  => admin_url( 'admin-ajax.php' ),
					'submitUrl' => admin_url( 'admin-ajax.php' )."?action=brizy_submit_form"
				)
			)
		);

		return $config;
	}

	private function get_page_attachments() {

		$posts = get_posts(array(

		));



		$page_upload_path = $this->urlBuilder->upload_path();
		$attachment_path  = $page_upload_path . $this->urlBuilder->page_asset_path();

		$attachments     = glob( $attachment_path . "/*.*" );
		$attachment_data = array();


		foreach ( $attachments as $file ) {

			$basename                     = basename( $file );
			$attachment_data[ $basename ] = true;
//			$fpath     = ltrim( str_replace( $attachment_path, '', $file ), "/" );
//			$file_data = explode( '/', $fpath );
//			if ( count( $file_data ) >= 2 ) {
//				$attachment_data[ $basename ][ $file_data[0] ] = true;
//			}
		}

		return (object) $attachment_data;
	}

	private function get_woocomerce_plugin_info() {
		if ( function_exists( 'wc' ) && defined( 'WC_PLUGIN_FILE' ) ) {
			return array( 'version' => WooCommerce::instance()->version );
		}

		return null;
	}
}
