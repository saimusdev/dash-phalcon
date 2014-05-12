#!/usr/bin/php --
<?php
require_once('simple_html_dom.php');

if (count($argv) != 3) {
	echo "usage: " . $argv[0] . "docset api_folder" . PHP_EOL;
	die;
}
// Name of the final docset
define('DOCSET_NAME', $argv[1]);
define('API_FOLDER', $argv[2]);

// Excluded files in the parsing process
$excluded_files = ['.', '..', 'index.html', '.DS_Store']; 
$excluded_extensions = ['txt','js','css','png','svg','png','jpg'];	
	

define('CLASSN', 'Class');	
define('CONSTANT', 'Constant');
define('METHOD', 'Method');


//  CREATE THE DATABASE...
$sqlite = new PDO("sqlite:" . DOCSET_NAME . "/Contents/Resources/docSet.dsidx");
$create_table = 'CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT);';
$stmt = $sqlite->exec($create_table);
$create_index = 'CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path);';
$stmt = $sqlite->exec($create_index);

// FILL THE DATABASE && CLEAN THE HTML FILES (FOR BETTER VISUALIZATION)
$iter = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator(API_FOLDER, 
			RecursiveDirectoryIterator::SKIP_DOTS),
	    RecursiveIteratorIterator::SELF_FIRST,
	    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

$paths = [API_FOLDER];
foreach ($iter as $key => $file) {
    if ($file->isFile() && 
			!in_array($file->getFilename(), $excluded_extensions) && 
			!in_array($file->getExtension(), $excluded_extensions)) {
		echo "Parsing " . $file . "..." . PHP_EOL;
        parseFile($file, $sqlite);
    }
}

/**
* parseFile parses the HTML documentation file
* @param string $pFile fileName to be processed
*/
function parseFile($file, $sqlite)
{	
	$html = file_get_html($file);
	if ($html) {
		// Open the SQLite connection
		$sqlite->setAttribute(PDO::ATTR_ERRMODE,
								PDO::ERRMODE_EXCEPTION);
		// Search the Class
		$class = $html->find('h1 strong', 0);
		searchFor($sqlite, array($class), CLASSN, basename($file));
		
		// Search the Constants
		$constants = $html->find('div[id=constants] p strong');
		if (count($constants) > 0) {
			searchFor($sqlite, $constants, CONSTANT, basename($file));
		}
		unset($constants);
		
		// Search the Methods			
		$methods = $html->find('div[id=methods] p strong');
		if (count($methods) > 0) {
			searchFor($sqlite, $methods, METHOD, basename($file));
		}
		unset($methods);
		
		$sqlite = null;	// Close the SQLite connection
		
		rewriteHtml($html, $file);
	}
}

/**
 * searchFor 
 * @param object $pSqlite SQLite handler
 * @param array $pData name of the class, methods, constants
 * @param string $pType CLASS, CONSTANT, METHOD
 * @param string $fileName The HTML file which will be used by Dash to display the documentation
 */
function searchFor($pSqlite, $pData, $pType, $fileName)
{
	$items = [];
	
	foreach ($pData as $item) {
		if($item) {
			array_push($items, $item->plaintext);
			$item->outertext = '<a name="//apple_ref/cpp/' . $pType . '/' .
			$item->plaintext .'" class="dashAnchor">'.$item->innertext.'</a>';
		}
	}
	insert($pSqlite, $items, $pType, $fileName);
}

/**
 * insert inserts into the Dash SQLite database the required data
 * @param object $pSqlite SQLite handler
 * @param array $pData name of the class, methods, constants
 * @param string $pType CLASS, CONSTANT, METHOD
 * @param string $fileName destination file where the new documentation must be written
 */

function insert($pSqlite, $pData, $pType, $fileName)
{

	$insert = 'INSERT OR IGNORE INTO searchIndex (name, type, path) VALUES (:name, :type, :path)';
	$stmt = $pSqlite->prepare($insert);
	
	foreach ($pData as $data) {
		$stmt->bindValue(':name', $data);
		$stmt->bindValue(':type', $pType);
		$stmt->bindValue(':path', 'api/' . $fileName);
		$stmt->execute();
	}		
}

/**
 * rewriteHtml removes menu, first colum of array from the Phalcon documentation in order to have something more readable in Dash viewer
 * @param object $pHtml the HTML DOM
 * @param string $pFilename destination file where the new documentation must be written
 */

function rewriteHtml($pHtml, $pFileName)
{	
	if($menuBar = $pHtml->find('div[class=size-wrap]', 0)) {
		$menuBar->innertext = 
			'<div class="header clear-fix" style="padding-bottom: 0;">
				<a class="header-logo" href="http://phalconphp.com"><span class="logo-text">Phalcon</span></a>
			</div>' . PHP_EOL;
	}
	if($headerLine = $pHtml->find('div[class=header-line]', 0)) {
		$headerLine->innertext = '';
		$headerLine->outertext = '';
		$headerLine->width = '0px';
		$headerLine->height = '0px';
	}
	if($tableContents = $pHtml->find('td[class=primary-box]', 0)) {
		$tableContents->innertext = '';
		$tableContents->outertext = '';
		$tableContents->width = '0px';
		$tableContents->height = '0px';
	}
	if($otherFormats = $pHtml->find('div[id=other-formats]', 0)) {
		$otherFormats->innertext = '';
		$otherFormats->outertext = '';
		$otherFormats->width = '0px';
		$otherFormats->height = '0px';
	}
	if($related = $pHtml->find('div[class=related]', 0)) {
		$related->innertext = '';
		$related->outertext = '';
		$related->width = '0px';
		$related->height = '0px';
	}
	if($footer = $pHtml->find('div[id=footer]', 0)) {
		$footer->innertext = '';
		$footer->outertext = '';
		$footer->width = '0px';
		$footer->height = '0px';
	}
	
	file_put_contents($pFileName, $pHtml);	
}
?>
