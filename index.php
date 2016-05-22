<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secret.json');

# Why do I have to set scopes? Doesn't the service normally do this for me automatically?
$client->setScopes("https://www.googleapis.com/auth/androidpublisher");

$service = new Google_Service_AndroidPublisher($client);

$package = $_GET['package'];

if (!preg_match('/^[a-zA-Z0-9.]+$/', $package)) {
	throw new Exception("Package name is invalid");
}

$reviews = $service->reviews->listReviews($package)->getReviews();

$feed_id = "http://example.com/php-google-play-store-reviews-rss-feed/".$_SERVER['HTTP_HOST']."/".$package;

date_default_timezone_set('America/Los_Angeles');

if (!count($reviews)) {
	$updated = new DateTime();
} else {
	$updated = new DateTime("@".$reviews[0]->getComments()[0]->getUserComment()->getLastModified()->getSeconds());
}

Header( "Content-Type: application/atom+xml; charset=utf-8");


echo '<?xml version="1.0" encoding="utf-8"?>';
?>

<feed xmlns="http://www.w3.org/2005/Atom">
<title>Google Play Store Reviews for <?= $package ?></title>
<id><?= $feed_id ?></id>
<updated><?= $updated->format(DateTime::ATOM) ?></updated>

<?php

foreach ($reviews as $review) {
	$author = $review->getAuthorName();
	$solid_star = json_decode('"\u2605"');
	$empty_star = json_decode('"\u2606"');

	foreach ($review->getComments() as $comment) {
		$user_comment = $comment->getUserComment();
		$star_rating = str_repeat($solid_star, $user_comment->starRating) . str_repeat($empty_star, 5 - $user_comment->starRating);
		$raw_content = $user_comment->getText();
		$tab_index = strpos($raw_content, "\t");
		if ($tab_index === false) {
			$title = $star_rating;
			$content = $raw_content;
		} else {
			$title = $star_rating . ": " . substr($raw_content, 0, $tab_index);
			$content = substr($raw_content, $tab_index+1);
		}
		$updated = new DateTime("@".$user_comment->getLastModified()->getSeconds());
		?>

<entry>
	<id><?= $review->getReviewId() ?></id>
	<title><?= $title ?></title>
	<updated><?= $updated->format(DateTime::ATOM) ?></updated>
	<author><name><?= $review->getAuthorName() ?></name></author>
	<content type="text"><?= $content ?></content>
	<link href="https://play.google.com/apps/publish/#ReviewDetailsPlace:p=<?= $package?>&amp;reviewid=<?=$review->getReviewId()?>" />
</entry>
		<?php
	}
}

?>
</feed>