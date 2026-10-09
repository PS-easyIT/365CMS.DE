<?php
namespace Melbahja\Seo\Utils;


use Melbahja\Seo\Exceptions\SeoException;


/**
 * @package Melbahja\Seo
 * @see https://git.io/phpseo
 * @license MIT
 * @copyright Mohamed Elbahja
 */
class Utils
{

	public static $encoding = 'UTF-8';

	/**
	 * Escape attr chars.
	 *
	 * @param  string $text
	 * @return string
	 */
	public static function escape(string $text): string
	{
		return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, static::$encoding);
	}

	/**
	 * Escape url for sitemaps.
	 *
	 * @param  string $url
	 * @return string
	 */
	public static function escapeUrl(string $url): string
	{
		$url = parse_url($url);
		$url['path'] = $url['path'] ?? '';
		$url['query'] = $url['query'] ?? '';

		if ($url['path'] !== '') {
			// 365CMS-Patch: bereits kodierte Segmente nicht doppelt kodieren (%C3%BC -> %25C3%25BC).
			$url['path'] = implode('/', array_map(static fn(string $segment): string => rawurlencode(rawurldecode($segment)), explode('/', $url['path'])));
		}

		if ($url['query'] !== '') {
			$url['query'] = "?{$url['query']}";
		}

		return str_replace(
			['&', "'", '"', '>', '<'],
			['&amp;', '&apos;', '&quot;', '&gt;', '&lt;'],
			$url['scheme'] . "://{$url['host']}" . (isset($url['port']) ? ':' . $url['port'] : '') . "{$url['path']}{$url['query']}"
		);
	}

	/**
	 * Encode url for sitemaps, other qoute/senstive chars are already escaped by xml writer!
	 *
	 * @param  string $url
	 * @return string
	 */
	public static function encodeSitemapUrl(string $url): string
	{
		$url = parse_url($url);
		$url['path']  = $url['path'] ?? '';
		$url['query'] = $url['query'] ?? '';

		if ($url['path'] !== '') {
			// 365CMS-Patch: bereits kodierte Segmente nicht doppelt kodieren (%C3%BC -> %25C3%25BC).
			$url['path'] = implode('/', array_map(static fn(string $segment): string => rawurlencode(rawurldecode($segment)), explode('/', $url['path'])));
		}

		if ($url['query'] !== '') {
			$url['query'] = "?{$url['query']}";
		}

		// 365CMS-Patch: Port erhalten (Sites auf Nicht-Standard-Ports verloren ihn in allen Sitemap-URLs).
		return "{$url['scheme']}://{$url['host']}" . (isset($url['port']) ? ':' . $url['port'] : '') . "{$url['path']}{$url['query']}";
	}

	/**
	 * Wrap callable func into a Traversable generator.
	 *
	 * @param  callable $func  must have yields
	 * @return \Traversable
	 */
	public static function generator(callable $func): \Traversable
	{
		return new class($func) implements \IteratorAggregate
		{
			public function __construct(private readonly mixed $callable){}

			public function getIterator(): \Traversable
			{
				return ($this->callable)();
			}
		};
	}

	/**
	 * Resolve a relative URL against a base URL.
	 *
	 * @param string $baseUrl
	 * @param string $url     relative or absolute URL.
	 * @return string         absolute URL.
	 */
	public static function resolveRelativeUrl(string $baseUrl, string $url): string
	{
		if (str_contains($url, '://') === false) {
			return rtrim($baseUrl, '/') . ($url[0] !== '/' ? "/{$url}" : $url);
		}

		return $url;
	}

	/**
	 * Normalize a date value to ISO 8601 format.
	 *
	 * @param string|int $date
	 * @return string ISO 8601 date.
	 * @throws SeoException if the format is invalid.
	 */
	public static function formatDate(string|int $date): string
	{
		if (($timestamp = is_int($date) ? $date : strtotime($date)) !== false) {
			return date('c', $timestamp);
		}

		throw new SeoException("Invalid date format: {$date}");
	}
}
