<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 4/19/18
 * Time: 3:48 PM
 */

class Brizy_Editor_CropCacheMedia extends Brizy_Editor_Asset_StaticFile {

	const BASIC_CROP_TYPE = 1;
	const ADVANCED_CROP_TYPE = 2;

	/**
	 * @var Brizy_Editor_UrlBuilder
	 */
	private $url_builder;

	/**
	 * @var Brizy_Editor_Post
	 */
	private $post;

	/**
	 * Brizy_Editor_CropCacheMedia constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 * @param Brizy_Editor_Post $post
	 */
	public function __construct( $project, $post ) {

		$this->post        = $post;
		$this->url_builder = new Brizy_Editor_UrlBuilder( $project, $post );
	}

	/**
	 * @param $madia_name
	 *
	 * @return string
	 * @throws Exception
	 */
	public function download_original_image( $madia_name ) {

		// Check if user is querying API
		if ( ! $madia_name ) {
			Brizy_Logger::instance()->error( 'Empty media file provided' );
			throw new InvalidArgumentException( "Invalid media file" );
		}

		$external_asset_url  = $this->url_builder->external_media_url( "iW=5000&iH=any/" . $madia_name );
		$original_asset_path = $this->url_builder->upload_path( $this->url_builder->page_asset_path( $madia_name ) );

		if ( ! file_exists( $original_asset_path ) ) {
			// I assume that the media was already attached.

			if ( ! $this->store_file( $external_asset_url, $original_asset_path ) ) {
				// unable to save the attachment
				Brizy_Logger::instance()->error( 'Unable to store original media file', array( 'source'      => $external_asset_url,
				                                                                               'destination' => $original_asset_path
				) );
				throw new Exception( 'Unable to cache media' );
			}

			// attach to post
			$parent_post_id = $this->post->get_id();
			$attach_to_post = $this->attach_to_post( $original_asset_path, $parent_post_id );
			if ( $attach_to_post === 0 || is_wp_error( $attach_to_post ) ) {
				Brizy_Logger::instance()->error( 'Unable to attach media file', array( 'media'       => $original_asset_path,
				                                                                       'parent_post' => $parent_post_id
				) );
				throw new Exception( 'Unable to attach media' );
			}
		}

		return $original_asset_path;
	}

	/**
	 * @param $original_asset_path
	 * @param $media_filter
	 *
	 * @return string
	 */
	public function crop_media( $original_asset_path, $media_filter ) {

		// Check if user is querying API
		if ( ! file_exists( $original_asset_path ) ) {
			throw new InvalidArgumentException( "Invalid media file" );
		}

		if ( ! $media_filter ) {
			throw new InvalidArgumentException( "Invalid crop filter" );
		}

		$resized_page_asset_path = $this->url_builder->upload_path( $this->url_builder->page_asset_path( $media_filter ) );
		$ext                     = pathinfo( $original_asset_path, PATHINFO_EXTENSION );
		$resized_image_path      = $resized_page_asset_path . "/" . md5( $original_asset_path ) . '.' . $ext;

		// resize image
		if ( $media_filter ) {

			if ( ! file_exists( $resized_image_path ) ) {

				@mkdir( $resized_page_asset_path );

				$imagine = $this->crop( $original_asset_path, $media_filter );

				if ( $imagine ) {
					$imagine->save( $resized_image_path );

					return $resized_image_path;
				}
			}
		}

		return $resized_image_path;
	}

	/**
	 * @param $original_path
	 * @param $resize_params
	 *
	 * @return \Imagine\Gd\Image|\Imagine\Image\ImageInterface|null|static
	 */
	private function crop( $original_path, $resize_params ) {
		$imagine        = $this->getImagine();
		$original_image = $imagine->open( $original_path );
		$new_image      = null;

		$regExAdvanced = "/^iW=[0-9]{1,4}&iH=[0-9]{1,4}&oX=[0-9]{1,4}&oY=[0-9]{1,4}&cW=[0-9]{1,4}&cH=[0-9]{1,4}$/is";
		$regExBasic    = "/^iW=[0-9]{1,4}&iH=([0-9]{1,4}|any|\*{1})$/is";
		if ( preg_match( $regExBasic, $resize_params ) ) {
			$cropType = self::BASIC_CROP_TYPE;
		} elseif ( preg_match( $regExAdvanced, $resize_params ) ) {
			$cropType = self::ADVANCED_CROP_TYPE;
		} else {
			throw new Exception( "Invalid size format." );
		}

		$filter_configuration                 = $this->getFilterOptions( $cropType, $original_path, $resize_params );
		$original_box                         = $original_image->getSize();
		$filter_configuration['originalSize'] = array( $original_box->getWidth(), $original_box->getHeight() );

		if ( $filter_configuration['format'] == "gif" ) {
			// do not resize
			$new_image = $original_image;
		} else {
			if ( $filter_configuration['is_advanced'] === false ) {
				list( $imageWidth, $imageHeight ) = array_values( $filter_configuration['requestedData'] );
				list( $originalWidth, $originalHeight ) = $filter_configuration['originalSize'];
				if ( $imageWidth > $originalWidth && ( $imageHeight == "any" || $imageHeight == "*" ) ) {
					return $original_image;
				}

				return $this->relativeResize( $original_image, $imageWidth, $imageHeight );
			}

			list( $imageWidth, $imageHeight, $offsetX, $offsetY, $cropWidth, $cropHeight ) = array_values( $filter_configuration['requestedData'] );
			$image     = $this->relativeResize( $original_image, $imageWidth, $imageHeight );
			$filter    = new \Imagine\Filter\Basic\Crop( new \Imagine\Image\Point( $offsetX, $offsetY ), new \Imagine\Image\Box( $cropWidth, $cropHeight ) );
			$new_image = $filter->apply( $image );
		}

		return $new_image;
	}

	private function relativeResize( $image, $imageWidth, $imageHeight ) {
		$filter = new \Imagine\Filter\Advanced\RelativeResize( "widen", $imageWidth );
		$image  = $filter->apply( $image );

		return $image;
	}

	private function getImagine() {
		return new Imagine\Gd\Imagine();
	}

	private function getFilterOptions( $cropType, $image_path, $resize_params ) {

		parse_str( strtolower( $resize_params ), $output );
		$configuration           = array();
		$configuration['format'] = pathinfo( basename( $image_path ), PATHINFO_EXTENSION );

		switch ( $cropType ) {
			case self::BASIC_CROP_TYPE:
				$configuration['requestedData']['imageWidth']  = $output['iw'];
				$configuration['requestedData']['imageHeight'] = $output['ih'];
				$configuration['is_advanced']                  = false;
				break;
			case self::ADVANCED_CROP_TYPE:
				$configuration['requestedData']['imageWidth']  = $output['iw'];
				$configuration['requestedData']['imageHeight'] = $output['ih'];
				$configuration['requestedData']['offsetX']     = $output['ox'];
				$configuration['requestedData']['offsetY']     = $output['oy'];
				$configuration['requestedData']['cropWidth']   = $output['cw'];
				$configuration['requestedData']['cropHeight']  = $output['ch'];
				$configuration['is_advanced']                  = true;
				break;
		}

		return $configuration;
	}

}