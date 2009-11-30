<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A generic stream wrapper sitting between PHP and stream wrappers implementing
 * \F3\FLOW3\Resource\StreamWrapperInterface.
 *
 * The resource manager will register configured stream wrappers with this class,
 * enabling the use of FLOW3 goodies like DI in those stream wrappers.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StreamWrapper {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	static protected $objectFactory;

	/**
	 * @var array
	 */
	static protected $registeredStreamWrappers = array();

	/**
	 * @var resource
	 */
	public $context ;

	/**
	 * @var \F3\FLOW3\Resource\StreamWrapperInterface
	 */
	protected $streamWrapper;

	/**
	 * Set the object factory.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function setObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		self::$objectFactory = $objectFactory;
	}

	/**
	 * Register a stream wrapper. Later registrations for a scheme will override
	 * earlier ones without warning.
	 *
	 * @param string $scheme
	 * @param string $objectName
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function registerStreamWrapper($scheme, $objectName) {
		self::$registeredStreamWrappers[$scheme] = $objectName;
	}

	/**
	 * Returns the stream wrappers registered with this class.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function getRegisteredStreamWrappers() {
		return self::$registeredStreamWrappers;
	}

	/**
	 * Create the internal stream wrapper if needed.
	 *
	 * @param string $path The path to fetch the scheme from.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createStreamWrapper($path) {
		if ($this->streamWrapper === NULL) {
			$explodedPath = explode(':', $path, 2);
			$scheme = array_shift($explodedPath);
			$this->streamWrapper = self::$objectFactory->create(self::$registeredStreamWrappers[$scheme]);
		}
	}

	/**
	 * Close directory handle.
	 *
	 * This method is called in response to closedir().
	 *
	 * Any resources which were locked, or allocated, during opening and use of
	 * the directory stream should be released.
	 *
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function dir_closedir() {
		return $this->streamWrapper->closeDirectory();
	}

	/**
	 * Open directory handle.
	 *
	 * This method is called in response to opendir().
	 *
	 * @param string $path Specifies the URL that was passed to opendir().
	 * @param int $options Whether or not to enforce safe_mode (0x04).
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function dir_opendir($path, $options) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->openDirectory($path, $options);
	}

	/**
	 * Read entry from directory handle.
	 *
	 * This method is called in response to readdir().
	 *
	 * @return string Should return string representing the next filename, or FALSE if there is no next file.
	 */
	public function dir_readdir() {
		return $this->streamWrapper->readDirectory();
	}

	/**
	 * Rewind directory handle.
	 *
	 * This method is called in response to rewinddir().
	 *
	 * Should reset the output generated by dir_readdir(). I.e.: The next call
	 * to dir_readdir() should return the first entry in the location returned
	 * by dir_opendir().
	 *
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function dir_rewinddir() {
		return $this->streamWrapper->rewindDirectory();
	}

	/**
	 * Create a directory.
	 *
	 * This method is called in response to mkdir().
	 *
	 * Note: In order for the appropriate error message to be returned this
	 * method should not be defined if the wrapper does not support creating
	 * directories.
	 *
	 * @param string $path Directory which should be created.
	 * @param integer $mode The value passed to mkdir().
	 * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function mkdir($path, $mode,$options) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->makeDirectory($path, $mode, $options);
	}

	/**
	 * Renames a file or directory.
	 *
	 * This method is called in response to rename().
	 *
	 * Should attempt to rename path_from to path_to.
	 *
	 * Note: In order for the appropriate error message to be returned this
	 * method should not be defined if the wrapper does not support creating
	 * directories.
	 *
	 * @param string $path_from The URL to the current file.
	 * @param string $path_to The URL which the path_from should be renamed to.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function rename($path_from, $path_to) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->rename($path_from, $path_to);
	}

	/**
	 * Removes a directory.
	 *
	 * This method is called in response to rmdir().
	 *
	 * Note: In order for the appropriate error message to be returned this
	 * method should not be defined if the wrapper does not support creating
	 * directories.
	 *
	 * @param string $path The directory URL which should be removed.
	 * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function rmdir($path, $options) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->removeDirectory($path, $options);
	}

	/**
	 * Retrieve the underlaying resource.
	 *
	 * This method is called in response to stream_select().
	 *
	 * @param integer $cast_as Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
	 * @return resource Should return the underlying stream resource used by the wrapper, or FALSE.
	 */
	public function stream_cast($cast_as) {
		return $this->streamWrapper->cast($cast_as);
	}

	/**
	 * Close an resource.
	 *
	 * This method is called in response to fclose().
	 *
	 * All resources that were locked, or allocated, by the wrapper should be
	 * released.
	 *
	 * @return void
	 */
	public function stream_close() {
		$this->streamWrapper->close();
	}

	/**
	 * Tests for end-of-file on a file pointer.
	 *
	 * This method is called in response to feof().
	 *
	 * @return boolean Should return TRUE if the read/write position is at the end of the stream and if no more data is available to be read, or FALSE otherwise.
	 */
	public function stream_eof() {
		return $this->streamWrapper->isAtEof();
	}

	/**
	 * Flushes the output.
	 *
	 * This method is called in response to fflush().
	 *
	 * If you have cached data in your stream but not yet stored it into the
	 * underlying storage, you should do so now.
	 *
	 * Note: If not implemented, FALSE is assumed as the return value.
	 *
	 * @return boolean Should return TRUE if the cached data was successfully stored (or if there was no data to store), or FALSE if the data could not be stored.
	 */
	public function stream_flush() {
		return $this->streamWrapper->flush();
	}

	/**
	 * Advisory file locking.
	 *
	 * This method is called in response to flock(), when file_put_contents()
	 * (when flags contains LOCK_EX), stream_set_blocking() and when closing the
	 * stream (LOCK_UN).
	 *
	 * $operation is one of the following:
	 *  LOCK_SH to acquire a shared lock (reader).
	 *  LOCK_EX to acquire an exclusive lock (writer).
	 *  LOCK_UN to release a lock (shared or exclusive).
	 *  LOCK_NB if you don't want flock() to block while locking.
	 *
	 * @param integer $operation One of the LOCK_* constants
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function stream_lock($operation) {
		switch ($operation) {
			case LOCK_UN:
				$this->streamWrapper->unlock();
			break;
			default:
				$this->streamWrapper->lock($operation);
		}
	}

	/**
	 * Opens file or URL.
	 *
	 * This method is called immediately after the wrapper is initialized (f.e.
	 * by fopen() and file_get_contents()).
	 *
	 * $optiosn can hold one of the following values OR'd together:
	 *  STREAM_USE_PATH
	 *    If path is relative, search for the resource using the include_path.
	 *  STREAM_REPORT_ERRORS
	 *    If this flag is set, you are responsible for raising errors using
	 *    trigger_error() during opening of the stream. If this flag is not set,
	 *    you should not raise any errors.
	 *
	 * @param string $path Specifies the URL that was passed to the original function.
	 * @param string $mode The mode used to open the file, as detailed for fopen().
	 * @param integer $options Holds additional flags set by the streams API.
	 * @param string &$opened_path path If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path should be set to the full path of the file/resource that was actually opened.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->open($path, $mode, $options, $opened_path);
	}

	/**
	 * Read from stream.
	 *
	 * This method is called in response to fread() and fgets().
	 *
	 * Note: Remember to update the read/write position of the stream (by the
	 * number of bytes that were successfully read).
	 *
	 * @param integer $count How many bytes of data from the current position should be returned.
	 * @return string If there are less than count bytes available, return as many as are available. If no more data is available, return either FALSE or an empty string.
	 */
	public function stream_read($count) {
		return $this->streamWrapper->read($count);
	}

	/**
	 * Seeks to specific location in a stream.
	 *
	 * This method is called in response to fseek().
	 *
	 * The read/write position of the stream should be updated according to the
	 * offset and whence .
	 *
	 * $whence can one of:
	 *  SEEK_SET - Set position equal to offset bytes.
	 *  SEEK_CUR - Set position to current location plus offset.
	 *  SEEK_END - Set position to end-of-file plus offset.
	 *
	 * @param integer $offset The stream offset to seek to.
	 * @param integer $whence
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function stream_seek($offset, $whence = SEEK_SET) {
		return $this->streamWrapper->seek($offset, $whence);
	}

	/**
	 * Change stream options.
	 *
	 * This method is called to set options on the stream.
	 *
	 * $option can be one of:
	 *  STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
	 *  STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
	 *  STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
	 *
	 * If $option is ... then $arg1 is set to:
	 *  STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
	 *  STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
	 *  STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
	 *
	 * If $option is ... then $arg2 is set to:
	 *  STREAM_OPTION_BLOCKING: This option is not set.
	 *  STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
	 *  STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
	 *
	 * @param integer $option
	 * @param integer $arg1
	 * @param integer $arg2
	 * @return boolean TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 */
	public function stream_set_option($option, $arg1, $arg2) {
		return $this->streamWrapper->setOption($option, $arg1, $arg2);
	}

	/**
	 * Retrieve information about a file resource.
	 *
	 * This method is called in response to fstat().
	 *
	 * @return array See http://php.net/stat
	 */
	public function stream_stat() {
		return $this->streamWrapper->resourceStat();
	}

	/**
	 * Retrieve the current position of a stream.
	 *
	 * This method is called in response to ftell().
	 *
	 * @return int Should return the current position of the stream.
	 */
	public function stream_tell() {
		return $this->streamWrapper->tell();
	}

	/**
	 * Write to stream.
	 *
	 * This method is called in response to fwrite().
	 *
	 * If there is not enough room in the underlying stream, store as much as
	 * possible.
	 *
	 * Note: Remember to update the current position of the stream by number of
	 * bytes that were successfully written.
	 *
	 * @param string $data Should be stored into the underlying stream.
	 * @return int Should return the number of bytes that were successfully stored, or 0 if none could be stored.
	 */
	public function stream_write($data) {
		return $this->streamWrapper->write($data);
	}

	/**
	 * Delete a file.
	 *
	 * This method is called in response to unlink().
	 *
	 * Note: In order for the appropriate error message to be returned this
	 * method should not be defined if the wrapper does not support removing
	 * files.
	 *
	 * @param string $path The file URL which should be deleted.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function unlink($path) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->unlink($path);
	}

	/**
	 * Retrieve information about a file.
	 *
	 * This method is called in response to all stat() related functions.
	 *
	 * $flags can hold one or more of the following values OR'd together:
	 *  STREAM_URL_STAT_LINK
	 *     For resources with the ability to link to other resource (such as an
	 *     HTTP Location: forward, or a filesystem symlink). This flag specified
	 *     that only information about the link itself should be returned, not
	 *     the resource pointed to by the link. This flag is set in response to
	 *     calls to lstat(), is_link(), or filetype().
	 *  STREAM_URL_STAT_QUIET
	 *     If this flag is set, your wrapper should not raise any errors. If
	 *     this flag is not set, you are responsible for reporting errors using
	 *     the trigger_error() function during stating of the path.
	 *
	 * @param string $path The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL. Other URL forms are not supported.
	 * @param integer $flags Holds additional flags set by the streams API.
	 * @return array Should return as many elements as stat() does. Unknown or unavailable values should be set to a rational value (usually 0).
	 */
	public function url_stat($path, $flags) {
		$this->createStreamWrapper($path);
		return $this->streamWrapper->pathStat($path, $flags);
	}

}

?>