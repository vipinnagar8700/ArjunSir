<?php

namespace OTGS\Toolset\Common\Result;

/**
 * Pseudo-enum for the importance of a Result message, can be used for filtering.
 *
 * INFO is considered to be the default value.
 *
 * @since 4.0.6
 */
abstract class LogLevel {

	const UNDEFINED = 0;

	const DEBUG = 1;

	const INFO = 2;
}
