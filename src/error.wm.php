<?php
	/**
	 * Class ParameterError
	 */
	class ParameterError extends Exception
	{
		/**
		 * ParameterError constructor.
		 *
		 * @inheritDoc
		 *
		 * @param                $message
		 * @param int            $code
		 * @param Exception|NULL $previous
		 */
		public function __construct ($message, $code = 0, Exception $previous = NULL)
		{
			parent::__construct($message, $code, $previous);
		}

		/**
		 * @return string
		 */
		public function __toString ()
		{
			return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
		}
	}