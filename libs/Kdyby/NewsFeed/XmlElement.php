<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\NewsFeed;

use Kdyby;
use Nette;



/**
 * @author Robert Lemke <rl@robertlemke.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class XmlElement extends \SimpleXMLElement
{

	/**
	 * @return \DOMElement
	 */
	public function toDom()
	{
		return dom_import_simplexml($this);
	}



	/**
	 * Adds a new child node - and replaces "&" by "&amp;" on the way ...
	 *
	 * @param string $name Name of the tag
	 * @param string $value The tag value, if any
	 * @param array $attributes
	 * @param string $namespace The tag namespace, if any
	 * @return XMLElement
	 */
	public function addChild($name, $value = NULL, $attributes = array(), $namespace = NULL)
	{
		if (is_string($attributes)) {
			$namespace = $attributes;
			$attributes = array();
		}

		$child = parent::addChild($name, $this->formatValue($value), $namespace);

		foreach ($attributes as $name => $value) {
			$child->addAttribute($name, $value);
		}

		return $child;
	}



	/**
	 * @param XmlElement $xml
	 * @return XmlElement
	 */
	public function appendChild(XmlElement $xml)
	{
		$toDom = $this->toDom();
		$toDom->appendChild($toDom->ownerDocument->importNode($xml->toDom(), TRUE));
		return $this;
	}



	/**
	 * Adds a new attribute - and replace "&" by "&amp;" on the way ...
	 *
	 * @param string $name Name of the attribute
	 * @param string $value The value to set, if any
	 * @param string $namespace The namespace, if any
	 * @return XmlElement
	 */
	public function addAttribute($name, $value = NULL, $namespace = NULL)
	{
		parent::addAttribute($name, $this->formatValue($value), $namespace);
		return $this;
	}



	/**
	 * @param string $value
	 * @return XmlElement
	 */
	public function addCdata($value)
	{
		$domNode = $this->toDom();
		$domNode->appendChild($domNode->ownerDocument->createCDATASection($value));
		return $this;
	}



	/**
	 * @return string
	 */
	public function toXml()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->appendChild($dom->importNode($this->toDom(), TRUE));
		$dom->formatOutput = true;
		return $dom->saveXML();
	}



	protected function formatValue($value)
	{
		if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
			$value = $value->format('D, d M Y H:i:s O');
		}

		if ($value !== NULL) {
			$value = str_replace('&', '&amp;', $value);
		}

		return $value;
	}



	/**
	 * @param string $name
	 * @return XmlElement
	 */
	public static function createRssElement($name)
	{
		return new XMLElement('<?xml version="1.0" encoding="UTF-8" ?>
			<' . $name . ' version="2.0"
				xmlns:content="http://purl.org/rss/1.0/modules/content/"
				xmlns:wfw="http://wellformedweb.org/CommentAPI/"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:atom="http://www.w3.org/2005/Atom"
				xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
				xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
			/>
			', LIBXML_NOERROR | LIBXML_ERR_NONE | LIBXML_ERR_FATAL);
	}

}
