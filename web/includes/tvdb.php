<?

$TVDB_DOWNLOAD_TIME  = 0;
$TVDB_DOWNLOAD_COUNT = 0;

# Parse and input URL for TVDB ID and LID
function parseTVDBURL($url) {
	$retval = array(
		'tvdb-id'  => false,
		'tvdb-lid' => false,
	);

        # Accept both the raw and encoded verisons of the URL
	if (preg_match('/(?:\?|\&(?:amp;)?)(?:series)?id=(\d+)/', $url, $matches)) {
		$retval['tvdb-id'] = $matches[1];
	}
	if (preg_match('/(?:\?|\&(?:amp;)?)lid=(\d+)/', $url, $matches)) {
		$retval['tvdb-lid'] = $matches[1];
	}

	return $retval;
}

# Build a series URL from the ID and optional LID
function TVDBURL($id, $lid) {
	global $TVDB_URL;
	$url = false;

	if (!$lid) {
		$lid = $TVDB_LANG_ID;
	}
	if ($id) {
		$url = $TVDB_URL . '&id=' . $id . '&lid=' . $lid;
		$url = adjustProtocol($url);
	}

	return $url;
}

function getTVDBPage($id, $lid) {

	# If TVDB is not enabled return FALSE
	global $ENABLE_TVDB;
	if (!$ENABLE_TVDB) {
		return false;
	}

	# Sleep between downloads to avoid TVDB bans
	global $TVDB_DELAY;
	global $TVDB_DELAY_COUNT;
	global $TVDB_DOWNLOAD_TIME;
	global $TVDB_DOWNLOAD_COUNT;
	if ($TVDB_DOWNLOAD_COUNT >= $TVDB_DELAY_COUNT && time() - $TVDB_DOWNLOAD_TIME < $TVDB_DELAY) {
		sleep(rand($TVDB_DELAY, 2 * $TVDB_DELAY));
	}
	$TVDB_DOWNLOAD_COUNT++;
	$TVDB_DOWNLOAD_TIME = time();

	# Download with a timeout and forced IPv4 resolution
	global $TVDB_TIMEOUT;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,            TVDBURL($id, $lid));
	curl_setopt($ch, CURLOPT_TIMEOUT,        $TVDB_TIMEOUT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_IPRESOLVE,      CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER,    true);
	$body = curl_exec($ch);
	if ($err = curl_error($ch)) {
		echo '<!-- cURL Error: ' . $err . "-->\n";
	}
	curl_close($ch);

	return $body;
}

# Plain-text title of a TVDB entity (or FALSE on failure)
function getTVDBTitle($id, $lid) {
	$retval = false;

	# If TVDB is not enabled return FALSE
	global $ENABLE_TVDB;
	if (!$ENABLE_TVDB) {
		return false;
	}

	$page = getTVDBPage($id, $lid);
	if ($page) {
		if (preg_match('/\<h1\>([^\>]+)\<\/h1\>/i', $page, $matches)) {
			$retval = $matches[1];
		}
	}

	return $retval;
}

# Get the seasons of a TVDB entity
function getTVDBSeasons($id, $lid) {
	$retval = false;

	# If TVDB is not enabled return a fake season list
	global $ENABLE_TVDB;
	if (!$ENABLE_TVDB) {
		return array(1 => true);
	}

	# Download and parse the series page for a list of seasons
	$page = getTVDBPage($id, $lid);
	if ($page) {
		if (preg_match_all('/class=\"seasonlink\"[^\>]*\>([^\<]+)\<\/a\>/i', $page, $matches)) {
			$retval = array();
			foreach ($matches[1] as $val) {
				if (preg_match('/^\d+$/', $val)) {
					$retval[ $val ] = true;
				} else if ($val == 'Specials') {
					$retval[0] = true;
				}
			}
		}
	}

	return $retval;
}

?>
