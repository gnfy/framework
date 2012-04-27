<?
class SofeeXmlParser {

	/**
	* XML parser handle
	*
	* @var		resource
	* @see		xml_parser_create()
	*/
	var $parser;

	/**
	* source encoding
	*
	* @var		string
	*/
	var $srcenc;

	/**
	* target encoding
	*
	* @var		string
	*/
	var $dstenc;

	/**
	* the original struct
	*
	* @access	private
	* @var		array
	*/
	var $_struct = array();

	/**
	* Constructor
	*
	* @access		public
	* @param		mixed		[$srcenc] source encoding
	* @param		mixed		[$dstenc] target encoding
	* @return		void
	* @since
	*/
	function SofeeXmlParser($srcenc = null, $dstenc = null) {
		$this->srcenc = $srcenc;
		$this->dstenc = $dstenc;

		// initialize the variable.
		$this->parser = null;
		$this->_struct = array();
	}

	/**
	* Free the resources
	*
	* @access		public
	* @return		void
	**/
	function free() {
		if (isset($this->parser) && is_resource($this->parser)) {
			xml_parser_free($this->parser);
			unset($this->parser);
		}
	}

	/**
	* Parses the XML file
	*
	* @access		public
	* @param		string		[$data] the XML file str
	* @return		void
	* @since
	*/
	function parseFile($data) {
		$this->parseString($data);
	}

	/**
	* Parses a string.
	*
	* @access		public
	* @param		string		[$data] XML data
	* @return		void
	*/
	function parseString($data) {
		if ($this->srcenc === null) {
			$this->parser = @xml_parser_create() or die('Unable to create XML parser resource.');
		} else {
			$this->parser = @xml_parser_create($this->srcenc) or die('Unable to create XML parser resource with '. $this->srcenc .' encoding.');
		}

		if ($this->dstenc !== null) {
			@xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $this->dstenc) or die('Invalid target encoding');
		}
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);	// lowercase tags
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);		// skip empty tags
		if (!xml_parse_into_struct($this->parser, $data, &$this->_struct)) {
			/*
			printf("XML error: %s at line %d",
					xml_error_string(xml_get_error_code($this->parser)),
					xml_get_current_line_number($this->parser)
			);
			*/
			$this->free();
			exit();
		}

		$this->_count = count($this->_struct);
		$this->free();
	}

	/**
	* return the data struction
	*
	* @access		public
	* @return		array
	*/
	function getTree() {
		$i = 0;
		$tree = array();

		$tree = $this->addNode(
			$tree,
			$this->_struct[$i]['tag'],
			(isset($this->_struct[$i]['value'])) ? $this->_struct[$i]['value'] : '',
			(isset($this->_struct[$i]['attributes'])) ? $this->_struct[$i]['attributes'] : '',
			$this->getChild($i)
		);

		unset($this->_struct);
		return ($tree);
	}

	/**
	* recursion the children node data
	*
	* @access		public
	* @param		integer		[$i] the last struct index
	* @return		array
	*/
	function getChild(&$i) {
		// contain node data
		$children = array();

		// loop
		while (++$i < $this->_count) {
			// node tag name
			$tagname = $this->_struct[$i]['tag'];
			$value = isset($this->_struct[$i]['value']) ? $this->_struct[$i]['value'] : '';
			$attributes = isset($this->_struct[$i]['attributes']) ? $this->_struct[$i]['attributes'] : '';

			switch ($this->_struct[$i]['type']) {
				case 'open':
					// node has more children
					$child = $this->getChild($i);
					// append the children data to the current node
					$children = $this->addNode($children, $tagname, $value, $attributes, $child);
					break;
				case 'complete':
					// at end of current branch
					$children = $this->addNode($children, $tagname, $value, $attributes);
					break;
				case 'cdata':
					// node has CDATA after one of it's children
					$children['value'] .= $value;
					break;
				case 'close':
					// end of node, return collected data
					return $children;
					break;
			}

		}
		//return $children;
	}

	/**
	* Appends some values to an array
	*
	* @access		public
	* @param		array		[$target]
	* @param		string		[$key]
	* @param		string		[$value]
	* @param		array		[$attributes]
	* @param		array		[$inner] the children
	* @return		void
	* @since
	*/
	function addNode($target, $key, $value = '', $attributes = '', $child = '') {
		if (!isset($target[$key]['value']) && !isset($target[$key][0])) {
			if ($child != '') {
				$target[$key] = $child;
			}
			if ($attributes != '') {
				foreach ($attributes as $k => $v) {
					$target[$key][$k] = $v;
				}
			}

			$target[$key]['value'] = $value;
		} else {
			if (!isset($target[$key][0])) {
				// is string or other
				$oldvalue = $target[$key];
				$target[$key] = array();
				$target[$key][0] = $oldvalue;
				$index = 1;
			} else {
				// is array
				$index = count($target[$key]);
			}

			if ($child != '') {
				$target[$key][$index] = $child;
			}

			if ($attributes != '') {
				foreach ($attributes as $k => $v) {
					$target[$key][$index][$k] = $v;
				}
			}
			$target[$key][$index]['value'] = $value;
		}
		return $target;
	}

}

function arr2xml($array)
{
	static $last_temp;
	static $last_tag;

	$str = "";
	while(list($key,$value) = each($array))
	{
		if(!is_int($key))
		{
			$last_temp = $key;
		}
		if(is_array($value))
		{
			if(is_int($key))
			{
				if($last_tag == "")
				{
					$last_tag = $last_temp;
				}
				else
				{
					$str .= "</{$last_tag}><{$last_tag}>";
				}
				$str .= arr2xml($value);

			}
			else
			{
				$str .= "<{$key}>".arr2xml($value)."</{$key}>";
			}
		}
		else
		{
			if($key == "value")
			{
				$str .= $value;
			}
			else
			{
				$str.="<{$key}>{$value}</{$key}>";
			}
		}
	}
	return $str;
}
?>