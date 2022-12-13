<?php

namespace Contacta\MessageChars;

use Contacta\MessageChars\Constants;

class MessageChars
{
	private $message;
	private $encoding;
	private $size;
	private $count;
    
	public function __construct(String $message = '') {

		$encoding = mb_detect_encoding($message, ['ASCII', 'UTF-8', 'ISO-8859-1'], true);
		$string = iconv($encoding, "UTF-8", $message); 

		$this->message = $string;
		$this->inspect();
	}

	public function __set($property, $value)
	{
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
	}

	public function __get($property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}

		throw new \InvalidArgumentException("You can get not exist $property property, please verify", 1);		
	}

	public function __toString()
	{
		return $this->message;
	}

	public function getEncoding() : string
	{
		return $this->encoding;
	}

	public function getCount() : int
	{
		return $this->size;
	}

	public function convertToGsm() : string
	{
		$string = strtr($this->message, Constants::UCS2_EQUIVALENT_GSM);
		return $string;
	}

	public function inspect() : void
	{
		if (!mb_check_encoding($this->message, 'UTF-8')) {
            throw new \InvalidArgumentException('Content encoding could not be verified ' . $this->message);
        }

		$this->encoding = Constants::ENCODING_7BIT;
		$this->size = 0;

		$mb_len = mb_strlen($this->message,'UTF-8');
		for ($i=0; $i < $mb_len; $i++) { 
			$char = mb_substr($this->message, $i, 1, 'UTF-8');
			
			if (in_array($char, Constants::GSM0338_BASIC, true)) {
				$this->size++;
			} 
			elseif (in_array($char, Constants::GSM0338_EXTENDED, true)) {
				$this->size += 2;
				$this->encoding = Constants::ENCODING_7BIT_EXTENDED;
			}
			else {
				$this->encoding = Constants::ENCODING_UCS2;
				break;
			}
		}

		if ($this->encoding == Constants::ENCODING_UCS2) {
			$this->size = 0;
			for ($i=0; $i < $mb_len; $i++) { 
				$char = mb_substr($this->message, $i, 1, 'UTF-8');
				$utf16Hex = bin2hex(mb_convert_encoding($char, 'UTF-16', 'UTF-8'));

				$this->size += strlen($utf16Hex) / 4;
			}
		}

		$single_size = Constants::MAXIMUM_CHARACTERS_7BIT_SINGLE;
		$concat_size = Constants::MAXIMUM_CHARACTERS_7BIT_CONCATENATED;

		if ($this->encoding == Constants::ENCODING_UCS2) {
			$single_size = Constants::MAXIMUM_CHARACTERS_UCS2_SINGLE;
			$concat_size = Constants::MAXIMUM_CHARACTERS_UCS2_CONCATENATED;
		}

		$this->count = 1;
		if ($this->size > $single_size) {
			$this->count = (int) ceil($this->size / $concat_size);
		}
	}

}
