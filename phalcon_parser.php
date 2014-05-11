#!/usr/bin/php
<?php
require_once('simple_html_dom.php');

if (count($argv) != 3) {
	echo "usage: " . $argv[0] . "docset api_folder";
	die;
}

// Name of the final docset
define('DOCSET_NAME', $argv[1]);
define('API_FOLDER', $argv[2]);

echo "API FOLDER: " . API_FOLDER . PHP_EOL;
echo "DOCSET NAME: " . DOCSET_NAME . PHP_EOL;

// Excluded files in the parsing process
$excluded_files = ['.', '..', 'index.html', '.DS_Store']; 
$excluded_extensions = ['js','css','png','svg','png','jpg'];	
	

define('CLASSN', 'Class');	
define('CONSTANT', 'Constant');
define('METHOD', 'Method');


//  CREATE THE DATABASE...
$sqlite = new PDO("sqlite:" . DOCSET_NAME . ".docset/Contents/Resources/docSet.dsidx");
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

foreach ($iter as $path => $p) {
    if ($p->isFile() && 
			!in_array($p->getFilename(), $excluded_extensions) && 
			!in_array($p->getExtension(), $excluded_extensions)) {
		shell_exec("echo -e \"$(tput setaf 2)-->Parsing" . $p . "...!$(tput sgr0)\"");
        parseFile($p, $sqlite);
    }
}

shell_exec("echo -e \"$(tput setaf 2)--> FINISHED!$(tput sgr0)\"");

/**
* parseFile parses the HTML documentation file
* @param string $pFile fileName to be processed
*/

function parseFile($pFile, $sqlite)
{
	echo '--> Scanning File: ' . $pFile . PHP_EOL;
	
	$html = file_get_html($pFile);
					
	if ($html) {
		// Open the SQLite connection
		$sqlite->setAttribute(	PDO::ATTR_ERRMODE,
								PDO::ERRMODE_EXCEPTION);
		// Search the Class
		$class = $html->find('h1 strong', 0);
		searchFor($sqlite, array($class), CLASSN, $pFile);
		
		// Search the Constants
		$constants = $html->find('div[id=constants] p strong');
		if (count($constants) > 0) {
			searchFor($sqlite, $constants, CONSTANT, $pFile);
		}
		unset($constants);
		
		// Search the Methods			
		$methods = $html->find('div[id=methods] p strong');
		if (count($methods) > 0) {
			searchFor($sqlite, $methods, METHOD, $pFile);
		}
		unset($methods);
		
		// Rewrite the HTML documentation 
		rewriteHtml($html, $pFile);
		
		shell_exec("echo -e \"$(tput setaf 2)--> Closing the database$(tput sgr0)\"");
		$sqlite = null;	// Close the SQLite connection
	}
}

/**
 * searchFor 
 * @param object $pSqlite SQLite handler
 * @param array $pData name of the class, methods, constants
 * @param string $pType CLASS, CONSTANT, METHOD
 * @param string $pFile The HTML file which will be used by Dash to display the documentation
 */
function searchFor($pSqlite, $pData, $pType, $pFile)
{
	$items = array();
	
	foreach ($pData as $item) {
		array_push($items, $item->plaintext);
		$item->outertext = '<a name="//apple_ref/cpp/' . $pType . '/'. $item->plaintext .'" class="dashAnchor">'.$item->innertext.'</a>';
	}
	
	insert($pSqlite, $items, $pType, $pFile);
}

/**
 * insert inserts into the Dash SQLite database the required data
 * @param object $pSqlite SQLite handler
 * @param array $pData name of the class, methods, constants
 * @param string $pType CLASS, CONSTANT, METHOD
 * @param string $pFile destination file where the new documentation must be written
 */

function insert($pSqlite, $pData, $pType, $pFile)
{

	$insert = 'INSERT OR IGNORE INTO searchIndex (name, type, path) VALUES (:name, :type, :path)';
	$stmt = $pSqlite->prepare($insert);
	
	foreach ($pData as $data) {
		$stmt->bindValue(':name', $data);
		$stmt->bindValue(':type', $pType);
		$stmt->bindValue(':path', 'api/'.$pFile);
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
	$menuBar = $pHtml->find('div[class=size-wrap]', 0);
	$menuBar->innertext = '
		<div class="header clear-fix">
			<a class="header-logo" href="http://phalconphp.com"><span class="logo-text">Phalcon</span></a>
		</div>' . PHP_EOL;
	
	$pHtml->find('div[class=header-line]', 0)->outertext = '';

	$pHtml->find('div[class=related]', 0)->outertext = '';
	
	$pHtml->find('div[id=other-formats]', 0)->outertext = '';

	$pHtml->find('div[id=other-formats]', 0)->outertext = '';
	
	$pHtml->find('div[class=donate-wrap]', 0)->outertext = '';
	
	$pHtml->find('div[id=social-link]', 0)->outertext = '';
	
	$tdFirstCol = $pHtml->find('td[class=primary-box]', 0);
	$tdFirstCol->outertext = '';
	$tdFirstCol->width = '0px';

	file_put_contents($pFileName, $pHtml);	
}
?>
	
?>