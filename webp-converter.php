<?php
/*
 * Plugin name: webp-converter
 * Description: Converts all the images in the Media Library to WebP.
 * Version:     0.0.1
 * Author:      Elliot Down
 * Author URI:  https://github.com/Syldriem
 */

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

function convert_media_lib() {
	// Getting all attachments
	$posts = get_posts([
	'post_mime_type' => 'image',
	'post_type' => 'attachment',
	'post_status' => 'inherit',
	'posts_per_page' => -1,
	]);

	$attachments_to_convert = [];
	$processed_names = [];
	// Looping through all attachments
	// and creating new array with correct keys
	foreach ($posts as $post) {
		$obj = [
			"url" => $post->guid,
			"type" => $post->post_mime_type,
			"file" => get_attached_file($post->ID),
			"name" => $post->post_title,
		];

		$key = $obj["name"];

		// check if we have added an image with same name
		if (isset($processed_names[$key])) {
			// if we have, check if its a webp image
			if ($processed_names[$key]["type"] === "image/webp") {
				// if it is, remove it
				$index = array_search($processed_names[$key], $attachments_to_convert);
				if ($index !== false) {
					unset($attachments_to_convert[$index]);
				}
			// if its not a webp, check if current img is a webp and if processed is not a webp
			} elseif ($obj["type"] === "image/webp" && $processed_names[$key]["type"] !== "image/webp") {
				// if we have added a non-webp already and this is a webp of the same name
				// then we don't need to add it.
				$index = array_search($processed_names[$key], $attachments_to_convert);
				if ($index !== false) {
					unset($attachments_to_convert[$index]);
				}
			// so if we havent added a webp nor added a non-webp and gotten a webp with same name later
			// then that means we have added a non-webp and gotten another non-webp of the same name, which is fine.
			} else {
				array_push($attachments_to_convert, $obj);
			}
		// we haven't added this img yet.
		} else {
			$processed_names[$key] = $obj;
			array_push($attachments_to_convert, $obj);
		}
}

	// Turning each attachment into a promise
	$promises = [];
	foreach ($attachments_to_convert as $att) {
		$promises[] = process_images_async($att);
	}

	// Waiting for all promises to settle
	Utils::settle($promises)->wait();
}

function process_images_async($attachment)
{
	$promise = Coroutine::of(function () use ($attachment) {
		try {
			$value = yield (new FulfilledPromise($file = create_webp($attachment, $attachment["type"])));
		} catch (Exception $e) {
			var_export("Error converting " . $e->getMessage() . " to webp.\n");
		}
		yield $value;
	});
	return $promise;
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
