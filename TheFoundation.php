<?php

/**
*
*/
spl_autoload_register(function(string $classname) {
    if (file_exists($filename = APPLICATION_PATH . 'packages/' . str_replace('\\', '/', $classname) . '.php'))
        return require_once $filename;

    return false;
});

/**
 * 
 */
function jump(string $filename, bool $assoc = JSON_OBJECT_AS_ARRAY): object|array {
	if (str_ends_with($filename, '.json'))
		return json_decode(file_get_contents(sprintf('%s/schema/%s', APPLICATION_PATH, $filename)), $assoc, 512, JSON_THROW_ON_ERROR);
	elseif (str_ends_with($filename, '.php'))
		return require(sprintf('%s/schema/%s', APPLICATION_PATH, $filename));
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