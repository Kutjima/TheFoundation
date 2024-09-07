<?php

/**
 * 
 */
if (!defined('APPLICATION_PATH')) {
	if (!empty($_SERVER['THEFOUNDATION_SOURCE_DIRECTORY']))
		define('APPLICATION_PATH', $_SERVER['THEFOUNDATION_SOURCE_DIRECTORY'] . '/application');
	else
		define('APPLICATION_PATH', sprintf('%s/application', dirname($_SERVER['SCRIPT_FILENAME'])));
}

/**
 *
 */
spl_autoload_register(function (string $classname) {
	if (file_exists($filename = APPLICATION_PATH . '/models/' . str_replace('\\', '/', $classname) . '.php'))
		return require_once $filename;

	return false;
});

/**
 * 
 */
function load_static(string $filename, bool $assoc = JSON_OBJECT_AS_ARRAY): object|array
{
	if (str_ends_with($filename, '.json'))
		return json_decode(file_get_contents(sprintf('%s/statics/%s', APPLICATION_PATH, $filename)), $assoc, 512, JSON_THROW_ON_ERROR);
	elseif (str_ends_with($filename, '.php'))
		return require sprintf('%s/statics/%s', APPLICATION_PATH, $filename);

	return [];
}

/**
 * 
 */
function snippet(string $filename, array ...$args)
{
	foreach ($args as $arg)
		extract($arg);

	require sprintf('%s/htdocs/.snippets/%s', APPLICATION_PATH, $filename);
}

/**
 * 
 */
function backoffice_snippet(string $filename, array ...$args)
{
	foreach ($args as $arg)
		extract($arg);

	require sprintf('%s/htdocs/.backoffice/.snippets/%s', APPLICATION_PATH, $filename);
}

/**
 * 
 */
foreach (
	[
		__DIR__ . '/src/RouterHttp.php',
		__DIR__ . '/src/RouterHttp/Response.php',
		__DIR__ . '/src/RouterHttp/Response/Template/Form.php',
		__DIR__ . '/src/RouterHttp/Response/Template/Table.php',
		__DIR__ . '/src/RouterHttp/Response/Template.php',
		__DIR__ . '/src/Request.php',
		__DIR__ . '/src/PDOFactory.php',
		__DIR__ . '/src/Database/Entity.php',
	] as $classname
)
	require_once $classname;
