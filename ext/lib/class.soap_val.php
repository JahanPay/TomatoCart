<?php


/*

www.QooHost.ir
*/


class soapval extends nusoap_base {
	/**
	 * The XML element name
	 *
	 * @var string
	 * @access private
	 */
	var $name;
	/**
	 * The XML type name (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $type;
	/**
	 * The PHP value
	 *
	 * @var mixed
	 * @access private
	 */
	var $value;
	/**
	 * The XML element namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $element_ns;
	/**
	 * The XML type namespace (string or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $type_ns;
	/**
	 * The XML element attributes (array or false)
	 *
	 * @var mixed
	 * @access private
	 */
	var $attributes;

	/**
	* constructor
	*
	* @param    string $name optional name
	* @param    mixed $type optional type name
	* @param	mixed $value optional value
	* @param	www.mihanteam.com
	* @param	mixed $type_ns optional namespace of type
	* @param	mixed $attributes associative array of attributes to add to element serialization
	* @access   public
	*/
  	function soapval($name='soapval',$type=false,$value=-1,$element_ns=false,$type_ns=false,$attributes=false) {
		parent::nusoap_base();
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
		$this->element_ns = $element_ns;
		$this->type_ns = $type_ns;
		$this->attributes = $attributes;
    }

	/**
	* return serialized value
	*
	* @param	string $use The WSDL use value (encoded|literal)
	* @return	string XML data
	* @access   public
	*/
	function serialize($use='encoded') {
		return $this->serialize_val($this->value, $this->name, $this->type, $this->element_ns, $this->type_ns, $this->attributes, $use, true);
    }

	/**
	* decodes a soapval object into a PHP native type
	*
	* @return	mixed
	* @access   public
	*/
	function decode(){
		return $this->value;
	}
}




?>