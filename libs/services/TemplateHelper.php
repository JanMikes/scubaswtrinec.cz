<?php

namespace App\Services;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class TemplateHelper extends Nette\Object
{

	public function loader($helper)
	{
		if (method_exists($this, $helper)) {
			return callback($this, $helper);
		}
	}


	public function weekStart($week, $year)
	{
		$week--;
		$time = strtotime("1 January $year", time());
		$day = date('w', $time);
		$time += ((7*$week)+1-$day)*24*3600;
		return date('Y-n-j', $time);
	}


	public function weekEnd($week, $year)
	{
		$week--;
		$time = strtotime("1 January $year", time());
		$day = date('w', $time);
		$time += ((7*$week)+1-$day)*24*3600;
		$time += 6*24*3600;
		return date('Y-n-j', $time);
	}


	/**
	 * @param  string  		$string
	 * @param  string|NULL 	$text
	 * @param  boolean 		$link
	 * @return Nette\Utils\Html|string
	 */
	public function email($string, $text = NULL, $link = TRUE) {
		$string = str_replace('@', '&#64;', $string);
		if (!$text) {
			$text = $string;
		}
		if ($link) {
			return Nette\Utils\Html::el()
				->setHtml('<a rel="nofollow" href="mai&#108;&#116;&#111;&#58;' . $string . '">' . $text . '</a>');
		}
		return $text;
	}


	/**
	* @param  string $s
	* @return string
	*/
	public function orphans($s)
	{
		return preg_replace('~( +(a|cca|č.|či|do|i|k|ke|na|o|od|po|s|tj.|u|v|z|za) +)~i', ' $2&nbsp;', $s);
	}


	/**
	* @param  string $s
	* @return string
	*/
	public function presenterFormat($s)
	{
		$parts = explode(":", $s);
		if (count($parts) > 2) {
			array_shift($parts);
		}
		return implode(" - ", $parts);
	}

}
