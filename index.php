<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secret.json');

if (!array_key_exists("package", $_GET)) {
	throw new Exception("You must specify an Android package as an URL parameter, e.g. ?package=com.example.app");
}

$package = $_GET['package'];

if (!preg_match('/^[a-zA-Z0-9.]+$/', $package)) {
	throw new Exception("Package name is invalid");
}

$curl = curl_init("https://play.google.com/store/apps/details?id=".$package);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$html = curl_exec($curl);

$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ( $status != 200 ) {
	throw new Exception("Couldn't load app details page");
}

libxml_use_internal_errors(true);

$doc = new DOMDocument();
$doc->loadHTML($html);

$xpath = new DOMXPath($doc);
$title = $xpath->query('//title')->item(0)->textContent;

$title = htmlspecialchars($title, ENT_XML1, 'UTF-8');
$title = str_replace(" - Apps on Google Play", "", $title);

$icon = $xpath->query('//img[@alt="Cover art"]')->item(0)->getAttribute("src");

if (substr($icon, 0, 2) == "//") {
	$icon = "https:".$icon;
}

# Why do I have to set scopes? Doesn't the service normally do this for me automatically?
$client->setScopes("https://www.googleapis.com/auth/androidpublisher");

$service = new Google_Service_AndroidPublisher($client);

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
<title><?= $title ?> Google Play Store Reviews</title>
<id><?= $feed_id ?></id>
<updated><?= $updated->format(DateTime::ATOM) ?></updated>
<icon><?= $icon ?></icon>

<?php

foreach ($reviews as $review) {
	$author = htmlspecialchars($review->getAuthorName(), ENT_XML1, 'UTF-8');
	$solid_star = json_decode('"\u2605"');
	$empty_star = json_decode('"\u2606"');

	foreach ($review->getComments() as $comment) {
		$user_comment = $comment->getUserComment();
		$star_rating = str_repeat($solid_star, $user_comment->starRating) . str_repeat($empty_star, 5 - $user_comment->starRating);
		$raw_content = $user_comment->getText();
		$tab_index = strpos($raw_content, "\t");
		if ($tab_index === false) {
			$title = $star_rating;
			$content = htmlspecialchars($raw_content, ENT_XML1, 'UTF-8');
		} else {
			$raw_title = substr($raw_content, 0, $tab_index);
			$title = $star_rating . ": " . htmlspecialchars($raw_title, ENT_XML1, 'UTF-8');
			$raw_content = substr($raw_content, $tab_index+1);
			$content = htmlspecialchars($raw_content, ENT_XML1, 'UTF-8');
		}
		$updated = new DateTime("@".$user_comment->getLastModified()->getSeconds());
		?>

<entry>
	<id><?= $review->getReviewId() ?></id>
	<title><?= $title ?></title>
	<updated><?= $updated->format(DateTime::ATOM) ?></updated>
	<author><name><?= $author ?></name></author>
	<content type="text">by <?= "$author\n" ?><?= $content ?></content>
	<link href="https://play.google.com/apps/publish/#ReviewDetailsPlace:p=<?= $package?>&amp;reviewid=<?=$review->getReviewId()?>" />
</entry>
		<?php
	}
}

?>
</feed>