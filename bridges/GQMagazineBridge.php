<?php

/**
 * An extension of the previous SexactuBridge to cover the whole GQMagazine.
 * This one taks a page (as an example sexe/news or journaliste/maia-mazaurette) which is to be configured,
 * reads all the articles visible on that page, and make a stream out of it.
 * @author nicolas-delsaux
 *
 */
class GQMagazineBridge extends BridgeAbstract
{

	const MAINTAINER = 'Riduidel';

	const NAME = 'GQMagazine';

	// URI is no more valid, since we can address the whole gq galaxy
	const URI = 'https://www.gqmagazine.fr';

	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'GQMagazine section extractor bridge. This bridge allows you get only a specific section.'
		. '<br/>' 
		. 'I typically use it to get articles published by Maia Mazaurette by configuring it to use '
		. 'gqmagazine.fr as domain and journaliste/maia-mazaurette as page.';
	
	const PARAMETERS = array( array(
		'domain' => array(
			'name' => 'Domain to use',
			'required' => true,
			'values' => array(
				'www.gqmagazine.fr' => 'www.gqmagazine.fr'
			),
			'defaultValue' => 'www.gqmagazine.fr'
		),
		'page' => array(
			'name' => 'Initial page to load',
			'required' => true
		),
	));
	
	const REPLACED_ATTRIBUTES = array(
		'href' => 'href',
		'src' => 'src',
		'data-original' => 'src'
	);
	
	public function getDomain() {
		return $this->getInput('domain');
	}

	public function getURI()
	{
		return $this->getDomain() . '/' . $this->getInput('page');
	}
	
	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request ' . $this->getURI());

		// Since GQ don't want simple class scrapping, let's do it the hard way and ... discover content !
		$main = $html->find('main', 0);
		foreach ($main->find('a') as $link) {
			$uri = $link->href;
			$title = $link->find('h2', 0);
			$date = $link->find('time', 0);

			$item = array();
			$author = $link->find('span[itemprop=name]', 0);
			$item['author'] = $author->plaintext;
			$item['title'] = $title->plaintext;
			if(substr($uri, 0, 1) === 'h') { // absolute uri
				$item['uri'] = $uri;
			} else if(substr($uri, 0, 1) === '/') { // domain relative url
				$item['uri'] = $this->getDomain() . $uri;
			} else {
				$item['uri'] = $this->getDomain() . '/' . $uri;
			}

			$article = $this->loadFullArticle($item['uri']);
			if($article) {
				$item['content'] = $this->replaceUriInHtmlElement($article);
			} else {
				$item['content'] = "<strong>Article body couldn't be loaded</strong>. It must be a bug!";
			}
			$short_date = $date->datetime;
			$item['timestamp'] = strtotime($short_date);
			$this->items[] = $item;
		}
	}
	
	
	/**
	 * Loads the full article and returns the contents
	 * @param $uri The article URI
	 * @return The article content
	 */
	private function loadFullArticle($uri){
		$html = getSimpleHTMLDOMCached($uri);
		// Once again, that generated css classes madness is an obstacle ... which i can go over easily
		foreach($html->find('div') as $div) {
			// List the CSS classes of that div
			$classes = $div->class;
			// I can't directly lookup that class since GQ since to generate random names like "ArticleBodySection-fkggUW"
			if(strpos($classes, "ArticleBodySection") !== false) {
				return $div;
			}
		}
		return null;
	}
	
	/**
	 * Replaces all relative URIs with absolute ones
	 * @param $element A simplehtmldom element
	 * @return The $element->innertext with all URIs replaced
	 */
	private function replaceUriInHtmlElement($element){
		$returned = $element->innertext;
		foreach (self::REPLACED_ATTRIBUTES as $initial => $final) {
			$returned = str_replace($initial . '="/', $final . '="' . self::URI . '/', $returned);
		}
		return $returned;
	}
}
