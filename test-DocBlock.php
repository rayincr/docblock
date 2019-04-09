<?php
require 'DocBlock.class.php';
define('TEST_FILE', __DIR__.DIRECTORY_SEPARATOR.'test-data.txt');
define('TEST_DATA', file_get_contents(TEST_FILE));


echo "\n==== TEST #1 ======================================================================\n\n";
echo
	"This section demonstrates the following methods:\n"
	." • ::getDocBlocksForFile()\n"
	." • ->getSummary()\n"
	." • ->getDescription()\n"
	." • ->getTags()\n"
	."\n\n"
;

$filename = __DIR__.DIRECTORY_SEPARATOR.'DocBlock.class.php';
$docblocks = DocBlock::getDocBlocksForFile($filename);
echo count($docblocks)." DocBlocks found in $filename. Here's the first one:\n\n";
echo "SUMMARY:\n";
echo $docblocks[0]->getSummary()."\n\n";
echo "DESCRIPTION:\n";
echo $docblocks[0]->getDescription()."\n\n";
echo "TAGS:\n";
print_r($docblocks[0]->getTags());



echo "\n\n\n==== TEST #2 ======================================================================\n\n";
echo
	"This section demonstrates the following methods:\n"
	." • __construct()\n"
	." • ->setSummary()\n"
	." • ->setDescription()\n"
	." • ->setTags()\n"
	." • ->getBlockText()\n"
	." • ->getParams()\n"
	." • ->getReturns()\n"
	." • ->getOtherTags()\n"
	."\n\n"
;

echo "\n\n-- \$docblock = new DocBlock() --\n\n";
$docblock = new DocBlock();
print_r($docblock);

echo "\n\n-- \$docblock->setSummary() --\n\n";
$docblock->setSummary('A short, short, short summary');
echo '$docblock->getSummary(): '.$docblock->getSummary()."\n";

echo "\n\n-- \$docblock->setDescription() --\n\n";
$docblock->setDescription('This is the description, which is usually a lot longer than the summary.');
echo '$docblock->getDescription(): '.$docblock->getDescription()."\n";

echo "\n\n-- \$docblock->setTags() --\n\n";
$tags = array(
	'@param   string $name  The name of the user',
	'@param   string $email The email address of the user',
	'@return  bool   TRUE or FALSE, depending on whether the user exists',
	'@author  Ray Morgan <user@example.com>',
	'@version 1.0',
	'@todo    Extend this method to wash my clothes and my dishes'
);
$docblock->setTags($tags);
print_r($docblock->getTags());

echo "\n\n-- \$docblock->getParams() --\n\n";
print_r($docblock->getParams());

echo "\n\n-- \$docblock->getReturns() --\n\n";
print_r($docblock->getReturns());

echo "\n\n-- \$docblock->getOtherTags() --\n\n";
print_r($docblock->getOtherTags());

echo "\n\n-- \$docblock->getBlockText() --\n\n";
echo $docblock->getBlockText();



echo "\n\n\n==== TEST #3 ======================================================================\n\n";
echo "\n\n-- DocBlock::getAllTodos() --\n\n";
print_r(DocBlock::getAllTodos());
echo "\n\n-- DocBlock::getAllDeprecations() --\n\n";
print_r(DocBlock::getAllDeprecations());



echo "\n\n\n==== TEST #4 ======================================================================\n\n";
echo "\n\n-- DocBlock::getDocumentationStats() --\n\n";
$stats = DocBlock::getDocumentationStats();
print_r($stats);
$percent = 100*(array_sum($stats)/count($stats));
echo "\nDocumentation is ".number_format($percent,1)."% complete\n\n";



echo "\n\n\n==== END ====\n";

