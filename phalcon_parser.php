#!/usr/bin/php --
<?php
// PHALCON PARSER -- Parses Phalcon Documentation, cleans it and fills a database with keywords

require_once('simple_html_dom.php');

if (count($argv) != 3) {
	echo "usage: " . $argv[0] . "docset api_folder" . PHP_EOL;
	die;
}
// Name of the final docset
define('DOCSET_NAME', $argv[1]);
define('DOCSET_FOLDER', $argv[2]);
define('API_FOLDER', $argv[2] . '/api');

// Excluded files in the parsing process
$excluded_files = ['.', '..', 'index.html', '.DS_Store']; 
$excluded_extensions = ['txt','js','css','ico','png','svg','png','jpg'];	
	
// Things to search for
define('CLASSN', 'Class');	
define('CONSTANT', 'Constant');
define('GUIDE', 'Guide');
define('METHOD', 'Method');
$GLOBALS['numClasses'] = 0;
$GLOBALS['numConstants'] = 0;
$GLOBALS['numGuides'] = 0;
$GLOBALS['numMethods'] = 0;

//  CREATE THE DATABASE...
$sqlite = new PDO("sqlite:" . DOCSET_NAME . "/Contents/Resources/docSet.dsidx");
$create_table = 'CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT);';
$stmt = $sqlite->exec($create_table);
$create_index = 'CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path);';
$stmt = $sqlite->exec($create_index);

$iter = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator(API_FOLDER, 
			RecursiveDirectoryIterator::SKIP_DOTS),
	    RecursiveIteratorIterator::SELF_FIRST,
	    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

// FILL THE DATABASE...
// Search for guides
findGuides($sqlite, DOCSET_FOLDER . "/index.html");

// Search for class names, methods & constants within each class
foreach ($iter as $key => $file) {
    if ($file->isFile() && 
			!in_array($file->getFilename(), $excluded_extensions) && 
			!in_array($file->getExtension(), $excluded_extensions)) {
		echo "Parsing " . $file . PHP_EOL;
        findOther($file, $sqlite);
    }
}

echo exec("echo \"$(tput setaf 2)--> Finished parsing!$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Found: " . $GLOBALS['numClasses'] . " " . CLASSN .  "es$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Found: " . $GLOBALS['numConstants'] . " " . CONSTANT .  "s$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Found: " . $GLOBALS['numGuides'] . " " . GUIDE .  "s$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Found: " . $GLOBALS['numMethods'] . " " . METHOD .  "s$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Finished parsing!$(tput sgr0)\"") . PHP_EOL;
echo exec("echo \"$(tput setaf 2)--> Cleaning the documentation...$(tput sgr0)\"") . PHP_EOL;

$iter = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator(DOCSET_FOLDER, 
			RecursiveDirectoryIterator::SKIP_DOTS),
	    RecursiveIteratorIterator::SELF_FIRST,
	    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

// CLEAN THE HTML FILES (FOR BETTER VISUALIZATION)
foreach ($iter as $key => $file) {
    if ($file->isFile() && 
			!in_array($file->getFilename(), $excluded_extensions) && 
			!in_array($file->getExtension(), $excluded_extensions)) {
		echo "Cleaning " . $file . PHP_EOL;
        rewriteHtml(file_get_html($file), $file);
    }
}
echo exec("echo \"$(tput setaf 2)--> Finished cleaning!$(tput sgr0)\"") . PHP_EOL;

/**
* findGuides parses the index file in search for Guides
* @param string $pFile fileName to be processed
* @param object $pSqlite SQLite handler
*/
function findGuides($sqlite, $fileName)
{	
	$html = file_get_html($fileName);
	if ($html) {
		// Open the SQLite connection
		$sqlite->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		
		// Search the Guides
		$guides = $html->find('li[class=toctree-l1]');
		if (count($guides) > 0) {
			foreach ($guides as $guide) {
				if($guide) {
					if (strpos($guide->innertext,'<a class="reference internal" ' .
						'href="api/index.html">API Indice</a>') !== false) { 
							break;
					}
					$GLOBALS['numGuides']++;
					$anchor = $guide->find('a',0);
					$guideName = $guide->plaintext;
					$guide->outertext = '<a name="//apple_ref/cpp/' . GUIDE . '/' .
						$guideName .'" class="dashAnchor">'.$guide->innertext.'</a>';
					insert($sqlite, array($anchor->plaintext), GUIDE, $anchor->href);
				}
			}
		}
		
		unset($guides);
		$sqlite = null;	// Close the SQLite connection
	}
}

/**
 * parseFile parses the HTML documentation file in search for 
 * Classes, Methods & Constants
 * @param string $pFile fileName to be processed
 * @param object $pSqlite SQLite handler
 */
function findOther($file, $sqlite)
{		
	$html = file_get_html($file);
	if ($html) {
		// Open the SQLite connection
		$sqlite->setAttribute(PDO::ATTR_ERRMODE,
								PDO::ERRMODE_EXCEPTION);
		// Search the Class
		$class = $html->find('h1 strong', 0);
		searchFor($sqlite, array($class), CLASSN, 'api/' . basename($file));
		if ($class) {
			$GLOBALS['numClasses']++;
		}
		
		// Search for Constants
		$constants = $html->find('div[id=constants] p strong');
		if (count($constants) > 0) {
			$GLOBALS['numConstants'] += count($constants);
			searchFor($sqlite, $constants, CONSTANT, 'api/' . basename($file));
		}
		unset($constants);
		
		// Search for Methods			
		$methods = $html->find('div[id=methods] p strong');
		if (count($methods) > 0) {
			$GLOBALS['numMethods'] += count($methods);
			searchFor($sqlite, $methods, METHOD, 'api/' . basename($file));
		}
		unset($methods);
		
		// Save to html in-page anchors created for constants and methods
		file_put_contents($file, $html);
		
		$sqlite = null;	// Close the SQLite connection
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
			$pName = $item->plaintext;
			array_push($items, $item->plaintext);	
			$item->outertext = '<a name="//apple_ref/cpp/' . $pType . '/' .
				$pName .'" id="' . $item->plaintext . '" class="dashAnchor">'.$item->innertext.'</a>';
			
		}
	}
 	if(count($items) != 0) {
 		insert($pSqlite, $items, $pType, $fileName);
 	}
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
		$stmt->bindValue(':path', $fileName . "#" . $data);
		$stmt->execute();
	}		
}

/**
 * rewriteHtml removes menu, first colum of array from the Phalcon documentation in order to have 
 * something more readable in Dash viewer
 * @param object $pHtml the HTML DOM
 * @param string $pFilename destination file where the new documentation must be written
 */

function rewriteHtml($pHtml, $pFileName)
{	
	if($menuBar = $pHtml->find('div[class=size-wrap]', 0)) {
		$menuBar->innertext = '';
		$menuBar->outertext = '';
	}
	if($headerLine = $pHtml->find('div[class=header-line]', 0)) {
		$headerLine->innertext = '';
		$headerLine->outertext = '';
	}
	if($table = $pHtml->find('table[width=90%]', 0)) {
		$table->width='100%';
	}
	if($tableContents = $pHtml->find('td[class=primary-box]', 0)) {
		$tableContents->innertext = '';
		$tableContents->outertext = '';
	}
	if($indexToc = $pHtml->find('div[id=table-of-contents]', 0)) {
		$indexToc->innertext = '';
		$indexToc->outertext = '';
	}
	if($otherFormats = $pHtml->find('div[id=other-formats]', 0)) {
		$otherFormats->innertext = '';
		$otherFormats->outertext = '';
	}
	/* Solved this one with css
	if($related = $pHtml->find('div[class=related]', 0)) {
		$relatedUl = $related->find('ul', 0);
		$related->innertext = '';
		$related->outertext = '';
	}
	*/
	if($footer = $pHtml->find('div[id=footer]', 0)) {
		$footer->innertext = '';
		$footer->outertext = '';
	}
	
	file_put_contents($pFileName, $pHtml);	
}
?>
