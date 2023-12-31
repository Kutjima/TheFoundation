<?php

/**
 * 
 */
if (!defined('APPLICATION_PATH'))
	define('APPLICATION_PATH', sprintf('%s/application', dirname($_SERVER['SCRIPT_FILENAME'])));

/**
*
*/
spl_autoload_register(function(string $classname) {
    if (file_exists($filename = sprintf('%s/models/%s.php', APPLICATION_PATH, str_replace('\\', '/', $classname))))
        return require_once $filename;

    return false;
});

/**
 * 
 */
function load(string $filename, bool $assoc = JSON_OBJECT_AS_ARRAY): object|array {
	if (str_ends_with($filename, '.json'))
		return json_decode(file_get_contents(sprintf('%s/persistences/%s', APPLICATION_PATH, $filename)), $assoc, 512, JSON_THROW_ON_ERROR);
	elseif (str_ends_with($filename, '.php'))
		return require sprintf('%s/persistences/%s', APPLICATION_PATH, $filename);

	return [];
}

/**
 * 
 */
function snippet(string $filename, array ...$args) {
	$backoffice = '.';

	if (str_contains(debug_backtrace()[0]['file'], '.backoffice'))
		$backoffice = '.backoffice';

	foreach ($args as $arg)
		extract($arg);

	require sprintf('%s/htdocs/%s/.snippets/%s', APPLICATION_PATH, $backoffice, $filename);
}

/**
 * 
 */
foreach([
	__DIR__ . '/src/RouterHttp.php',
	__DIR__ . '/src/RouterHttp/Response.php',
	__DIR__ . '/src/RouterHttp/Response/Template.php',
	__DIR__ . '/src/Request.php',
	__DIR__ . '/src/PDOFactory.php',
	__DIR__ . '/src/Database/Entity.php',
] as $classname)
	require_once $classname;
?>