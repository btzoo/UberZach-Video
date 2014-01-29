<?

# Always start a session
session_start();

function login($username, $password) {
	global $PAM_SERVICE;
	$username = preg_replace('/\W/', '_', $username);

	# Set the PAM service name
	ini_set('pam.servicename', $PAM_SERVICE);

	if (pam_auth($username, $password)) {
		$_SESSION['USER'] = $username;
		session_regenerate_id(true);
	} else {
		logout();
	}

	# Redirect, using the provided target if available
	login_redirect();
}

function login_redirect() {
	global $MAIN_PAGE;
	$url = $MAIN_PAGE;
	if (isset($_GET['dest'])) {
		$url = $_GET['dest'];
	}
	header('Location: ' . $url);
}

function logout() {
	global $MAIN_PAGE;
	unset($_SESSION['USER']);
	session_regenerate_id(true);
	header('Location: ' . $MAIN_PAGE);
	exit();
}

function username() {
	return $_SESSION['USER'];
}

function authenticated() {
	return isset($_SESSION['USER']);
}

function require_authentication() {
	global $LOGIN_PAGE;
	if (!authenticated()) {

		# Provide the current URL for post-auth redirect, if possible
		$dest = '';
		if (!preg_match('/' . preg_quote($LOGIN_PAGE) . '/', $_SERVER['PHP_SELF'])) {
			$dest = $_SERVER['PHP_SELF'];
			if ($_GET['series']) {
				$dest .= '?series=' . $_GET['series'];
			}
		}

		$url = $LOGIN_PAGE;
		if (strlen($dest)) {
			$url .= '?dest=' . urlencode($dest);
		}

		header('Location: ' . $url);
		exit();
	}
}

function die_if_not_authenticated() {
	if (!authenticated) {
		die('Failure: Auth');
	}
}

?>
