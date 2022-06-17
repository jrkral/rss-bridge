<?php

class CacheFactory
{
	private $folder;
	private $cacheNames;

	public function __construct(string $folder = PATH_LIB_CACHES)
	{
		$this->folder = $folder;
		// create cache names
		foreach(scandir($this->folder) as $file) {
			if(preg_match('/^([^.]+)Cache\.php$/U', $file, $m)) {
				$this->cacheNames[] = $m[1];
			}
		}
	}

	public function create(string $name): CacheInterface
	{
		$name = $this->sanitizeCacheName($name) . 'Cache';

		if(! preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name)) {
			throw new \InvalidArgumentException('Cache name invalid!');
		}

		$filePath = $this->folder . $name . '.php';
		if(!file_exists($filePath)) {
			throw new \Exception('Invalid cache');
		}
		$className = '\\' . $name;
		return new $className();
	}

	protected function sanitizeCacheName(string $name)
	{
		// Trim trailing '.php' if exists
		if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
			$name = $matches[1];
		}

		// Trim trailing 'Cache' if exists
		if (preg_match('/(.+)(?:Cache)$/i', $name, $matches)) {
			$name = $matches[1];
		}

		if(in_array(strtolower($name), array_map('strtolower', $this->cacheNames))) {
			$index = array_search(strtolower($name), array_map('strtolower', $this->cacheNames));
			return $this->cacheNames[$index];
		}
		return null;
	}
}
