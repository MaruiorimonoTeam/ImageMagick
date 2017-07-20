<?php

namespace DecodeLLC\ImageMagick;

use DecodeLLC\ImageMagick\Exception;
use DecodeLLC\ImageMagick\Dispatcher;

/**
 * {description}
 */
class Image
{

	/**
	 * {description}
	 */
	const PROPERTY_WIDTH       = 1;
	const PROPERTY_HEIGHT      = 2;
	const PROPERTY_FORMAT      = 3;
	const PROPERTY_MIME_TYPE   = 4;
	const PROPERTY_UNITS       = 5;
	const PROPERTY_RESOLUTION  = 6;
	const PROPERTY_DEPTH       = 7;
	const PROPERTY_COLORSPACE  = 8;
	const PROPERTY_COMPRESSION = 9;
	const PROPERTY_QUALITY     = 10;
	const PROPERTY_PROFILES    = 11;

	/**
	 * {description}
	 *
	 * @var     string
	 * @access  protected
	 */
	protected $location;

	/**
	 * {description}
	 *
	 * @var     string
	 * @access  protected
	 */
	protected $temporaryPath;

	/**
	 * {description}
	 *
	 * @var     array
	 * @access  protected
	 */
	protected $properties = [];

	/**
	 * {description}
	 *
	 * @param   string   $location
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct($location)
	{
		$this->location = $location;

		$this->isRemote() ? $this->download() : $this->copy();
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  void
	 */
	public function __destruct()
	{
		$this->isReady() and unlink(
			$this->getTemporaryPath()
		);
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  string
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  string
	 */
	public function getTemporaryPath()
	{
		return $this->temporaryPath;
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  string
	 */
	public function getTemporaryFolder()
	{
		return sys_get_temp_dir();
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  string
	 */
	public function getTemporaryPrefix()
	{
		return 'php_imagemagick_';
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isRemote()
	{
		if (filter_var($this->getLocation(), FILTER_VALIDATE_URL))
		{
			return true;
		}

		return false;
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isReady()
	{
		if (file_exists($this->getTemporaryPath()))
		{
			return true;
		}

		return false;
	}

	/**
	 * {description}
	 *
	 * @access  public
	 * @return  string
	 *
	 * @throws  Exception
	 */
	public function read()
	{
		if (! $this->isReady())
		{
			$message = '[%s] Temporary file %s is not ready.';

			throw new Exception(sprintf($message, __METHOD__, $this->getTemporaryPath()));
		}

		if (($source = file_get_contents($this->getTemporaryPath())) === false)
		{
			$message = '[%s] Failed to read the file: %s';

			throw new Exception(sprintf($message, __METHOD__, $this->getTemporaryPath()));
		}

		return $source;
	}

	/**
	 * {description}
	 *
	 * @param   string   $destination
	 *
	 * @access  public
	 * @return  bool
	 *
	 * @throws  Exception
	 */
	public function save($destination)
	{
		if (! $this->isReady())
		{
			$message = '[%s] Temporary file %s is not ready.';

			throw new Exception(sprintf($message, __METHOD__, $this->getTemporaryPath()));
		}

		if (file_put_contents($destination, $this->read(), LOCK_EX) === false)
		{
			$message = '[%s] Failed to save the file: %s as %s';

			throw new Exception(sprintf($message, __METHOD__, $this->getTemporaryPath(), $destination));
		}

		return true;
	}

	/**
	 * {description}
	 *
	 * @param   int      $key
	 * @param   string   $value
	 *
	 * @access  public
	 * @return  void
	 */
	public function setProperty($key, $value)
	{
		$this->properties[$key] = $value;
	}

	/**
	 * {description}
	 *
	 * @param   int     $key
	 * @param   mixed   $default
	 *
	 * @access  public
	 * @return  mixed
	 */
	public function getProperty($key, $default = null)
	{
		if (array_key_exists($key, $this->properties))
		{
			return $this->properties[$key];
		}

		return $default;
	}

	/**
	 * {description}
	 *
	 * @param   int   $key
	 *
	 * @access  public
	 * @return  bool
	 */
	public function hasProperty($key)
	{
		if (array_key_exists($key, $this->properties))
		{
			return true;
		}

		return false;
	}

	/**
	 * {description}
	 *
	 * @access  protected
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function copy()
	{
		$content = file_get_contents($this->getLocation(), false);

		if ($content === false)
		{
			$message = '[%s] Failed to copy the file by path: %s';

			throw new Exception(sprintf($message, __METHOD__, $this->getLocation()));
		}

		$this->createTemporaryFile($content);
	}

	/**
	 * {description}
	 *
	 * @access  protected
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @todo    Handling the response status code for a remote location.
	 */
	protected function download()
	{
		$options = [];

		$context = stream_context_create($options);

		$content = file_get_contents($this->getLocation(), false, $context);

		if ($content === false)
		{
			$message = '[%s] Failed to download the file by url: %s';

			throw new Exception(sprintf($message, __METHOD__, $this->getLocation()));
		}

		// todo: use the global variable:
		// > $http_response_header
		// for handling the response status code.

		$this->createTemporaryFile($content);
	}

	/**
	 * {description}
	 *
	 * @param   string   $content
	 *
	 * @access  protected
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function createTemporaryFile($content)
	{
		$randomHash = hash('sha1', uniqid(mt_rand(100000000, 999999999), true));

		$temporaryFolder = $this->getTemporaryFolder() . DIRECTORY_SEPARATOR;

		$temporaryFilename = $this->getTemporaryPrefix() . $randomHash;

		$temporaryPath = $temporaryFolder . $temporaryFilename;

		if (file_put_contents($temporaryPath, $content, LOCK_EX) === false)
		{
			$message = '[%s] Failed to save the file in temporary folder: %s';

			throw new Exception(sprintf($message, __METHOD__, $this->getTemporaryFolder()));
		}

		$this->temporaryPath = $temporaryPath;
	}
}
