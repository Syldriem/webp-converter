<?php
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

	// Turning each attachment into a webp
	foreach ($attachments_to_convert as $att) {
        create_webp($att, $att["type"]);
	}
}

?>
