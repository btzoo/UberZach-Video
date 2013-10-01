<?

require 'config.php';

# Find all shows under the given path
function allShows($base)	{
	$retval = array();

	# Look for series folders
	$tv_dir = opendir($base);
	if ($tv_dir === FALSE) {
		die('Unable to opendir(): ' . htmlspecialchars($path) . "\n");
	}
	while (false !== ($show = readdir($tv_dir))) {

		# Skip junk
		if (isJunk($show)) {
			continue;
		}

		# We only care about directories
		$show_path = $base . '/' . $show;
		if (!is_dir($show_path)) {
			continue;
		}

		# Record the show title and look for season folders
		$retval[ $show ] = findSeasons($show_path);
	}
	closedir($tv_dir);
	
	return $retval;
}

# Read and parse all of the series-level exists, content, and *.webloc files
function readFlags($path) {
	global $EXISTS_FILES;
	global $CONTENT_FILES;
	$flags = array();

	# Look for all the exists files
	foreach ($EXISTS_FILES as $name) {
		$flags[ $name ] = false;
		if (file_exists($path . '/' . $name)) {
			$flags[ $name ] = true;
		}
	}

	# Read all the content files
	foreach ($CONTENT_FILES as $name) {
		$flags[ $name ] = false;
		$file = $path . '/' . $name;
		if (is_readable($file)) {
			$flags[ $name ] = trim(file_get_contents($file));
		}
	}

	# Read the TVDB IDs from the *.webloc file
	$webloc = findWebloc($path);
	if ($webloc !== false) {
		$flags = array_merge($flags, readWebloc($webloc));
	}

	return $flags;
}

?>
