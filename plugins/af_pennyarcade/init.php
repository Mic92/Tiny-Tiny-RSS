<?php
class Af_PennyArcade extends Plugin {

	private $link;
	private $host;

	function about() {
		return array(1.0,
			"Strip unnecessary stuff from PA feeds",
			"fox");
	}

	function init($host) {
		$this->link = $host->get_link();
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
	}

	function hook_article_filter($article) {
		$owner_uid = $article["owner_uid"];

		if (strpos($article["link"], "penny-arcade.com") !== FALSE && strpos($article["title"], "Comic:") !== FALSE &&
				strpos($article["guid"], "pennyarcade,$owner_uid:") === FALSE) {

			$doc = new DOMDocument();
			@$doc->loadHTML(fetch_file_contents($article["link"]));

			$basenode = false;

			if ($doc) {
				$xpath = new DOMXPath($doc);
				$entries = $xpath->query('(//img[@src])'); // we might also check for img[@class='strip'] I guess...

				$matches = array();

				foreach ($entries as $entry) {

					if (preg_match("/(http:\/\/art.penny-arcade.com\/.*)/i", $entry->getAttribute("src"), $matches)) {

						$basenode = $entry;
						break;
					}
				}

				if ($basenode) {
					$article["content"] = $doc->saveXML($basenode, LIBXML_NOEMPTYTAG);

					// we need to update guid with owner_uid because our local article is different from the one
					// other users with this plugin disabled might get
					$article["guid"] = "pennyarcade,$owner_uid:" . $article["guid"];
				}
			}
		}

		return $article;
	}
}
?>
