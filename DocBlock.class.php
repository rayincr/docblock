<?php
require 'TraverseFilesystem.trait.php';

/**
 * Parses source code DocBlocks comments
 *
 * Given a comment block as a string, parses it and returns its component
 * parts.
 *
 * @todo Add methods to construct DocBlock text from input and place it in
 *       the right place in the right file.
 * @todo Add the ability to edit existing DocBlocks.
 */
class DocBlock {

	use TraverseFilesystem;

	private $summary;
	private $description;
	private $tags;



	/**
	 * Constructs a DocBlock object, either empty or populated by DocBlock
	 * text
	 *
	 * @param string $block_text A comment block as a single block of text
	 */
	public function __construct(string $block_text = NULL) {
		$this->setSummary(self::parseSummary($block_text));
		$this->setDescription(self::parseDescription($block_text));
		$this->setTags(self::parseTags($block_text));
	}



	/**
	 * Parses a DocBlock and returns the summary portion
	 *
	 * @param  string $block_text The DocBlock text to be parsed
	 * @return string The summary portion of the DocBlock
	 */
	private static function parseSummary(string $block_text = NULL) {
		$lines = self::cleanLines($block_text);
		$summary = '';
		foreach ($lines as $line) {
			if (preg_match('~^@~',$line)) {break;}
			$summary .= $line.' ';
			if (empty($line)) {break;}
			if (preg_match('~\.$~',$line)) {break;}
		}
		return trim($summary);
	}



	/**
	 * Sets the summary text
	 *
	 * @param string $summary The summary text
	 */
	public function setSummary(string $summary) {
		$this->summary = trim($summary);
	}



	/**
	 * Returns the summary portion of the DocBlock
	 *
	 * @return string The summary portion of the DocBlock
	 */
	public function getSummary() {
		return $this->summary;
	}



	/**
	 * Parses a DocBlock and returns the description portion
	 *
	 * @param  string $block_text The DocBlock text to be parsed
	 * @return string The description portion of the DocBlock
	 */
	private static function parseDescription(string $block_text = NULL) {
		$lines = self::cleanLines($block_text);
		$description = array();
		$capturing = FALSE;
		foreach ($lines as $line) {
			if (preg_match('~^@~',$line)) {break;}
			if ($capturing) {
				$description[] = $line;
				continue;
			}
			if (empty($line)) {$capturing = TRUE;}
			if (preg_match('~\.$~',$line)) {$capturing = TRUE;}
		}
		return trim(join("\n",$description));
	}



	/**
	 * Sets the description text
	 *
	 * @param  string $description The description text
	 */
	public function setDescription(string $description) {
		$this->description = trim($description);
	}



	/**
	 * Returns the description portion of the DocBlock
	 *
	 * @param  bool $formatted If TRUE, the description text will be formatted
	 *                         by adding html &lt;p&gt; tags where appropriate.
	 *                         Two or more consecutive newline characters are
	 *                         interpreted as the end of one paragraph and the
	 *                         beginning of the next.
	 * @return string The description portion of the DocBlock
	 */
	public function getDescription(bool $formatted = FALSE) { // may be multi-line; 2+ line breaks should be interpreted by the client code as paragraph breaks
		if (!$formatted) {
			return $this->description;
		} else if ($this->description) {
			return '<p>'.preg_replace('~\n{2,}~',"</p>\n\n<p>",$this->description).'</p>';
		}
	}



	/**
	 * Parses DocBlock text and returns an array of zero or more tags
	 *
	 * Tags are returned as unparsed strings, so it is assumed that they may
	 * be parser further as/if needed.
	 *
	 * @param  string $block_text The text of the DocBlock
	 * @return array  An array of strings representing the DocBlock's tags
	 */
	private static function parseTags(string $block_text = NULL) {
		$lines = self::cleanLines($block_text);
		$i = -1;
		$tags = array();
		foreach ($lines as $line) {
			if (preg_match('~^@[a-z\-]~i',$line)) {
				$i++;
				$tags[$i] = '';
			}
			if ($i > -1) {
				$tags[$i] .= ' '.$line;
				$tags[$i] = trim(preg_replace('~\s+~',' ',$tags[$i]));
			}
		}
		return $tags;
	}



	/**
	 * Sets the DocBlock objects tags
	 *
	 * Note that this method does not <em>add to</em> any existing tags; it
	 * removes all existing tags and replaces them with those passed in the
	 * <code>$tags</code> param.
	 *
	 * @param  array $tags An array of tags (as strings) to assign to the
	 *                     DocBlock object
	 */
	public function setTags(array $tags) {
		$this->tags = $tags;
	}



	/**
	 * Returns all tags from the DocBlock
	 *
	 * Example:
	 * <pre class="code">
	 * $docblock = new DocBlock($comment_text);
	 * $tags     = $docblock-&gt;getTags();
	 * print_r($tags);
	 * </pre>
	 *
	 * Outputs something like:
	 * <pre class="output">
	 * Array
	 * (
	 *     [0] => @author authorName
	 *     [1] => @copyright authorName date
	 *     [2] => @license URL name of license
	 *     [3] => @package packageName
	 * )
	 * </pre>
	 *
	 * It is up to the client code to parse and process the returned elements
	 * for display, if required.
	 *
	 * @param  string $which The type of tags to return. If not provided, the
	 *                       method returns all tags. The '@' prefix on the
	 *                       tag type is optional.
	 * @return string[] An array of tag entries
	 */
	public function getTags(string $which = NULL) {
		$which = trim($which);
		$which = str_replace('@','',$which);
		$mode = 'WHITELIST'; // unless otherwise indicated
		if ($which) {
			if (!preg_match('~^!?[a-z][a-z\-]+$~i',$which)) { // if not a valid tag type
				return array();
			} else if (preg_match('~^!~',$which)) { // exclude this tag type
				$regex = '~^@'.str_replace('!','',$which).'~';
				$mode = 'BLACKLIST';
			} else { // include this tag type
				$regex = '~^@'.$which.'~';
			}
		} else {
			$regex = '~^@~';
		}
		$tags = array();
		$i = 0;
		foreach ($this->tags as $line) {
			if ($mode == 'BLACKLIST') {
				if (preg_match('~^@~',$line) and !preg_match($regex,$line)) {
					$tags[$i] = $line;
					$i++;
				}
			} else {
				if (preg_match($regex,$line)) {
					$tags[$i] = $line;
					$i++;
				}
			}
		}
		return $tags;
	}



	/**
	 * Returns the text of the DocBlock
	 *
	 * @return string The text of the DocBlock
	 */
	public function getBlockText() {
		$summary = $this->getSummary();
		$description = $this->getDescription();
		$tags = $this->getTags();
		$block_text = "/**\n";
		if ($summary) {
			$block_text .= " * $summary\n *\n";
		}
		if ($description) {
			$description = preg_replace('~\n~',"\n * ",$description);
			$block_text .= " * $description\n *\n";
		}
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				$block_text .= " * $tag\n";
			}
		}
		$block_text .= " */\n";
		return $block_text;
	}



	/**
	 * Returns an array of function arguments
	 *
	 * @return string[] An array of function arguments (with the '@param' label removed)
	 */
	public function getParams() {
		$params = $this->getTags('param');
		foreach ($params as $i => $param) {
			$params[$i] = preg_replace('~^@param\s*~','',$param);
		}
		return $params;
	}



	/**
	 * Returns an array of return values for the function
	 *
	 * Although this returns an array, there is usually only zero or one element in the array.
	 *
	 * @return string[] An array of return value descriptions (with the '@return' label removed)
	 */
	public function getReturns() {
		$returns = $this->getTags('return');
		foreach ($returns as $i => $return) {
			$returns[$i] = preg_replace('~^@return\s*~','',$return);
		}
		return $returns;
	}



	/**
	 * Returns an array of tags, excluding @param and @return tags
	 *
	 * Note: The tag labels ('@_____') are not removed.
	 *
	 * @return string[] An array of tag entries, excluding @param and @return tags
	 */
	public function getOtherTags() {
		$return = array();
		foreach ($this->getTags() as $tag) {
			if (preg_match('~^@param~',$tag)) {continue;}
			if (preg_match('~^@return~',$tag)) {continue;}
			$return[] = $tag;
		}
		return $return;
	}



	/**
	 * Creates HTML for a DocBlock tag
	 *
	 * Produces different HTML depending on the tag type and its expected
	 * structure. If there is not special markup for the specified tag, it is
	 * just returned with the tag label in bold followed by a colon.
	 *
	 * @param  string $tag The tag to be parsed and rendered as HTML
	 * @return string The HTML for displaying the tag. Uses Bootstrap 4 alert markup.
	 * @todo   Finish implementation of @param, @return, and remaining tag types
	 * @todo   Add option on @link type to include a complete HTML <code>a</code tag; e.g., "@link Based on <a href="https://www.example.url">link text</a>"
	 */
	public static function getTagHTML($tag) {
		$tag = trim($tag);
		if (!preg_match('~^@[a-z\-]+~',$tag)) {return NULL;}
		$tag = preg_replace('~\s+~',' ',$tag);
		$chunks = preg_split('~\s+~',$tag,3);
		switch ($chunks[0]) {

			case '@param':
				return $tag;

			case '@return':
				return $tag;

			case '@todo':
				$tag = preg_replace('~^[^\s]+\s~','',$tag);
				return '<div class="alert alert-warning m-0 px-2 py-1" role="alert"><strong>To do:</strong> '.$tag.'</div>';

			case '@link':
				if (empty($chunks[1])) {return 'error: no link specified';}
				if (!HttpUrl::isValidURL($chunks[1])) {return 'error: invalid link specified';}
				if (!empty($chunks[2])) { // if there is link text
					$link_text = join(' ',array_slice($chunks,2));
					return
						'<div class="alert alert-light m-0 px-2 py-1" role="alert">'
						.'&#x1F517; '
						.'<a href="'.$chunks[2].'">'.$link_text.'</a>'
						.'</div>'
					;
				} else { // use the URL as the link text
					return
						'<div class="alert alert-light m-0 px-2 py-1" role="alert">'
						.'&#x1F517; '
						.'<a href="'.$chunks[1].'">'.$chunks[1].'</a>'
						.'</div>'
					;
				}

			case '@deprecated':
				return '<div class="alert alert-danger m-0 px-2 py-1" role="alert">'.$tag.'</a></div>';

			default:
				return preg_replace('~^@?([^\s]+)\s+(.+)$~',"<div class=\"alert alert-light m-0 px-2 py-1\" role=\"alert\"><strong>$1:</strong> $2</div>",$tag);
		}
	}



	private static function cleanLines($block_text) {
		$return = array();
		foreach (explode("\n",$block_text) as $line) {
			$line = preg_replace('~^\s*/?\*+/?\s*~','',$line); // delete slash and asterisk prefixes
			$line = preg_replace('~\*/$~','',trim($line));
			$line = preg_replace('~\s+~',' ',trim($line));
			$line = trim($line);
			if (!preg_match('~\w~',$line)) { // if there's no real info, it's empty
				$line = '';
			}
			if (empty($line) and count($return) == 0) {
				continue; // skip empty lines at the beginning.
			}
			$return[] = $line;
		}
		return $return;
	}



	/**
	 * Gets the DocBlocks for a file or directory
	 *
	 * @param  string $filename the file or directory to parse
	 * @return DocBlock[] an array of DocBlock objects
	 */
	public static function getDocBlocksForFile($filename) {
		if (!is_readable($filename)) {
			throw new Exception($filename.' is not readable');
		}
		if (is_file($filename)) {
			$return  = array();
			$content = file_get_contents($filename);
			if (preg_match('~\.php$~i',$filename)) {
				try { // dodge "Warning: Unexpected character in input"
					$tokens  = @token_get_all($content);
				} catch (Exception $e) {
					return array();
				}
				foreach ($tokens as $i => $token) {
					if ($token[0] == T_DOC_COMMENT) {
						$return[] = new DocBlock($token[1]);
					}
				}
				return $return;
			} else {
				preg_match_all('~/\*\*.+?\*/~s',$content,$matches);
				foreach ($matches[0] as $text) {
					$db = new DocBlock($text);
					$return[] = $db;
				}
				return $return;
			}
		} else if (is_dir($filename)) {
			$file = $filename.'/.docblock';
			if (is_file($file) and is_readable($file)) {
				$db = new DocBlock(file_get_contents($file));
				return array($db);
			} else {
				return array();
			}
		} else {
			throw new Exception($filename.' not found');
		}
	}



	/**
	 * Calculates the completion status of documentation throughout the system
	 *
	 * @return array An associative array in the form [filename] => complete (0 or 1)
	 */
	public static function getDocumentationStats() {
		$return = array();
		$accepted_extensions = array('php','sql','css','js');
		$files = self::getFilesIn();
		foreach ($files as $filename) {
			$basename = basename($filename);
			if ($basename == '.docblock') {continue;}
			$std_file = self::standardizeFilename($filename);
			$return[$std_file] = '?'; // default value, unless set below
			if (is_dir($filename)) {
				$docblocks = self::getDocBlocksForFile($filename);
				$return[$std_file] = (empty($docblocks)? 0 : 1);
			} else {
				$ext = strtolower(preg_replace('~^.+\.([^\.]+)$~',"$1",$basename));
				if (!in_array($ext,$accepted_extensions)) {
					unset($return[$std_file]);
					continue;
				}
				switch ($ext) {
					case 'sql':
					case 'js':
					case 'css':
						$docblocks = self::getDocBlocksForFile($filename);
						$return[$std_file] = empty($docblocks) ? 0 : 1;
						break;
					case 'php':
						if (preg_match('~\.class\.php$~',$basename) > 0) {
							$class_name = preg_replace('~\..+$~','',$basename);
							$class_ref = new ReflectionClass($class_name);
							$class_comment = $class_ref->getDocComment();
							$return[$std_file] = $class_comment ? 1 : 0;
							$methods = $class_ref->getMethods();
							foreach ($methods as $i => $method_ref) {
								$inherited = ($class_name != ($method_ref->getDeclaringClass()->name));
								if ($inherited) {continue;}
								$method_comment = $method_ref->getDocComment();
								$return[preg_replace('~\.class\.php$~i','',$std_file).'::'.$method_ref->name.'()'] = $method_comment ? 1 : 0;
							}
						} else {
							$docblocks = self::getDocBlocksForFile($filename);
							$return[$std_file] = empty($docblocks) ? 0 : 1;
						}
						break;
					default:
						// not a file we expect to contain comments
				}
			}
		}
		return $return;
	}



	public static function getAllTodos() {
		return self::getAllTagsByType('@todo');
	}

	public static function getAllDeprecations() {
		return self::getAllTagsByType('@deprecated');
	}



	/**
	 * Gets all tags of one type from DocBlocks throughout the project
	 *
	 * This is useful when, for example, you want to get all of the
	 * @deprecated tags or all of the @todo tags.
	 *
	 * @param string $type The type of tag to get
	 * @return array An associative array in the form [filename] => tags[]
	 */
	private static function getAllTagsByType($type) {
		$files = self::getFilesIn();
		$data  = array();
		foreach ($files as $filename) {
			$std_filename = self::standardizeFilename($filename);
			$docblocks = self::getDocBlocksForFile($filename);
			foreach ($docblocks as $db) {
				$tags = $db->getTags($type);
				if (count($tags)) {
					foreach ($tags as $tag) {
						$data[$std_filename][] = $tag;
					}
				}
			}
		}
		return $data;
	}


}
