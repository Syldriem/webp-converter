<?php
/*
 * Plugin name: webp-converter
 * Description: Converts all the images in the Media Library to WebP and automatically converts images on upload. Navigate to Settings > WebP Converter to convert all images.
 * Version:     0.0.1
 * Author:      Elliot Down
 * Author URI:  https://github.com/Syldriem
 */

require_once('webp-menu.php');

add_action('admin_menu', 'webp_plugin_menu');

function on_handle_upload($file)
{
	$type = $file['type'];
	// return if image is webp
	// otherwise convert to webp
	// if not image, upload file
	switch ($type) {
		case $type === 'image/png':
		case $type === 'image/jpeg':
			return(create_webp($file, $type));
		case $type === 'image/webp':
			return $file;
		default:
			return $file;
	}
}
add_filter('wp_handle_upload', 'on_handle_upload');

function create_webp($file, $type)
{
	set_time_limit(300);

	$info = new SplFileInfo($file['file']);
	$mime = $info->getExtension();
	$name = $info->getBasename("." . $mime);

	// return if image is webp
	// otherwise convert to webp
	// return null if not an image or conversion failed
	switch ($type) {
		case $type === 'image/png':
		case $type === 'image/jpeg':
			return(convert_to_webp($type, $mime, $file, $name));
		case $type === 'image/webp':
			return $file;
		default:
			return null;
	}
}

function convert_to_webp($type, $mime, $file, $name)
{
	$img = $type === 'image/png' ? imagecreatefrompng($file['file']) : imagecreatefromjpeg($file['file']);
	if ($img) {
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
		// creates a webp image
		if (imagewebp($img, str_replace(".$mime", ".webp", $file['file']), 80)) {
			imagedestroy($img);
			// creates webp image sub-sizes
			wp_create_image_subsizes(str_replace(".$mime", ".webp", $file['file']), 0);
			// creates a webp attachment
			$newfile['file'] = str_replace(".$mime", ".webp", $file['file']);
			$newfile['url'] = str_replace(".$mime", ".webp", $file['url']);
			$newfile['type'] = str_replace("$type", "image/webp", $file['type']);
			// inserts attachment to db
			wp_insert_attachment(
				array(
					'guid' => $newfile['url'],
					'post_title' => $name,
					'post_mime_type' => $newfile['type'],
				),
				$newfile['file'],
				0
			);
			// returns original file
			return $file;
		}
	}
}

function delete_webp($file)
{
	$delete = apply_filters( 'delete_webp', $file );
	if ( ! empty( $delete ) ) {
        @unlink( $delete );
	}
	$info = new SplFileInfo($file['file']);
	$mime = $info->getExtension();
	if ($mime === 'png') {
		unlink(str_replace(".png", ".webp", $file));
	} elseif ($mime === 'jpeg') {
		unlink(str_replace(".jpeg", ".webp", $file));
	} elseif ($mime === 'jpg') {
		unlink(str_replace(".jpg", ".webp", $file));
	}
}
?>
