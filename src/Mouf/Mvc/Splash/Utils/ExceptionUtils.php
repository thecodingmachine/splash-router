<?php
namespace Mouf\Mvc\Splash\Utils;

class ExceptionUtils
{
	/**
	 * Returns the Exception Backtrace as a nice HTML view.
	 *
	 * @param unknown_type $backtrace
	 * @return unknown
	 */
	private static function getHTMLBackTrace($backtrace)
	{
		$str = '';

		foreach ($backtrace as $step) {
			if ($step['function']!='getHTMLBackTrace' && $step['function']!='handle_error') {
				$str .= '<tr><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['class'])) ? htmlspecialchars($step['class'], ENT_NOQUOTES, "UTF-8") : '').
				((isset($step['type'])) ? htmlspecialchars($step['type'], ENT_NOQUOTES, "UTF-8") : '').htmlspecialchars($step['function'], ENT_NOQUOTES, "UTF-8").'(';

				if (is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ($step['args'] as $param) {
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
                        $params .= ', ';
						$drawn = true;
					}
					$str .= htmlspecialchars($params, ENT_NOQUOTES, "UTF-8");
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= '</td><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['file'])) ? htmlspecialchars(self::displayFile($step['file']), ENT_NOQUOTES, "UTF-8") : '');
				$str .= '</td><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['line'])) ? $step['line'] : '');
				$str .= '</td></tr>';
			}
		}

		return $str;
	}

	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param Exception $exception
	 */
	public static function getHtmlForException(\Exception $exception)
	{
		//global $sys_error_reporting_mail;
        //global $sys_error_messages;
        $msg='';

		$msg = '<table>';

		$display_errors = ini_get('display_errors');
		$color = "#FF0000";
		$type = "Uncaught ".get_class($exception);
		if ($exception->getCode() != null)
		$type.=" with error code ".$exception->getCode();

		$msg .= "<tr><td colspan='3' style='background-color:$color; color:white; text-align:center'><b>$type</b></td></tr>";

		$msg .= "<tr><td style='background-color:#AAAAAA; color:white; text-align:center'>Context/Message</td>";
		$msg .= "<td style='background-color:#AAAAAA; color:white; text-align:center'>File</td>";
		$msg .= "<td style='background-color:#AAAAAA; color:white; text-align:center'>Line</td></tr>";

		$msg .= "<tr><td style='background-color:#EEEEEE; color:black'><b>".nl2br($exception->getMessage())."</b></td>";
		$msg .= "<td style='background-color:#EEEEEE; color:black'>".self::displayFile($exception->getFile())."</td>";
		$msg .= "<td style='background-color:#EEEEEE; color:black'>".$exception->getLine()."</td></tr>";
		$msg .= self::getHTMLBackTrace($exception->getTrace());
		$msg .= "</table>";

		return $msg;

	}

	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param Exception $exception
	 */
	public static function getTextForException(\Exception $exception)
	{
		// Now, let's compute the same message, but without the HTML markup for the error log.
        $textTrace = "Message: ".$exception->getMessage()."\n";
		$textTrace .= "File: ".$exception->getFile()."\n";
		$textTrace .= "Line: ".$exception->getLine()."\n";
		$textTrace .= "Stacktrace:\n";
		$textTrace .= self::getTextBackTrace($exception->getTrace());
		return $textTrace;
	}
	/**
	 * Returns the Exception Backtrace as a text string.
	 *
	 * @param unknown_type $backtrace
	 * @return unknown
	 */
	private static function getTextBackTrace($backtrace)
	{
		$str = '';

		foreach ($backtrace as $step) {
			if ($step['function']!='getTextBackTrace' && $step['function']!='handle_error') {
				if (isset($step['file']) && isset($step['line'])) {
					$str .= "In ".$step['file'] . " at line ".$step['line'].": ";
				}
				if (isset($step['class']) && isset($step['type']) && isset($step['function'])) {
					$str .= $step['class'].$step['type'].$step['function'].'(';
				}

				if (is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ($step['args'] as $param) {
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
                        $params .= ', ';
						$drawn = true;
					}
					$str .= $params;
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= "\n";
			}
		}

		return $str;
	}

	/**
	 * Used by the debug function to display a nice view of the parameters.
	 *
	 * @param unknown_type $var
	 * @return unknown
	 */
	private static function getPhpVariableAsText($var)
	{
		if( is_string( $var ) )
		return( '"'.str_replace( array("\x00", "\x0a", "\x0d", "\x1a", "\x09"), array('\0', '\n', '\r', '\Z', '\t'), $var ).'"' );
		elseif ( is_int( $var ) || is_float( $var ) ) {
			return( $var );
		} elseif ( is_bool( $var ) ) {
			if( $var )
			return( 'true' );
			else
			return( 'false' );
		} elseif ( is_array( $var ) ) {
			$result = 'array( ';
			$comma = '';
			foreach ($var as $key => $val) {
				$result .= $comma.self::getPhpVariableAsText( $key ).' => '.self::getPhpVariableAsText( $val );
				$comma = ', ';
			}
			$result .= ' )';
			return( $result );
		} elseif (is_object($var)) return "Object ".get_class($var);
		elseif(is_resource($var)) return "Resource ".get_resource_type($var);
		return "Unknown type variable";
	}

	private static function displayFile($file)
	{
		$realpath = realpath($file);
		if (!$realpath) {
			// If the file is a phar::// or something...

            return $file;
		}
		$cwd = getcwd().DIRECTORY_SEPARATOR;
		return self::getRelativePath($cwd, $realpath);
	}

	/**
	 * Returns a relative path based on 2 absolute paths.
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	private static function getRelativePath($from, $to)
	{
		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
            if (isset($to[$depth]) && $dir === $to[$depth]) {
				// ignore this directory
                array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
                $remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}

}
