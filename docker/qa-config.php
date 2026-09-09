<?php

foreach (array('HOST', 'USER', 'NAME') as $key)
	if (getenv('QUESTION2ANSWER_DB_' . $key) === false || getenv('QUESTION2ANSWER_DB_' . $key) === '')
		throw new RuntimeException('Missing QUESTION2ANSWER_DB_' . $key);

$passwordFile = getenv('QUESTION2ANSWER_DB_PASSWORD_FILE');
$password = $passwordFile ? file_get_contents($passwordFile) : getenv('QUESTION2ANSWER_DB_PASSWORD');
if ($password === false || $password === '')
	throw new RuntimeException('Missing Question2Answer database password');

define('QA_MYSQL_HOSTNAME', getenv('QUESTION2ANSWER_DB_HOST'));
define('QA_MYSQL_USERNAME', getenv('QUESTION2ANSWER_DB_USER'));
define('QA_MYSQL_PASSWORD', $passwordFile ? rtrim($password, "\r\n") : $password);
define('QA_MYSQL_DATABASE', getenv('QUESTION2ANSWER_DB_NAME'));
define('QA_MYSQL_TABLE_PREFIX', 'qa_');
define('QA_EXTERNAL_USERS', false);
define('QA_BLOBS_DIRECTORY', '/var/www/html/qa-uploads/');
define('QA_CACHE_DIRECTORY', '/var/lib/q2a/cache/');
define('QA_DEBUG_PERFORMANCE', false);
unset($password, $passwordFile, $key);
