<?php
/**
 * This file is part of OpenClinic
 *
 * Copyright (c) 2002-2005 jact
 * Licensed under the GNU GPL. For full terms see the file LICENSE.
 *
 * $Id: Error.php,v 1.1 2005/07/19 19:50:20 jact Exp $
 */

/**
 * Error.php
 *
 * Contains the class Error
 *
 * Author: jact <jachavar@gmail.com>
 */

/**
 * Error set of show error functions
 *
 * @author jact <jachavar@gmail.com>
 * @access public
 * @since 0.8
 *
 * Methods:
 *  void query(Query $query, bool $goOut = true)
 *  void connection(DbConnection $conn, bool $goOut = true)
 *  void fetch(Query $query, bool $goOut = true)
 *  void message(string $errorMsg, int $errorType = E_USER_WARNING)
 *  string backTrace(array $context)
 *  void customHandler(int $number, string $message, string $file, int $line, array $context)
 */
class Error
{
  /**
   * void query(Query $query, bool $goOut = true)
   *
   * Displays the query error page
   *
   * @param Query $query Query object containing query error parameters.
   * @param bool $goOut if true, execute an exit()
   * @return void
   * @access public
   */
  function query($query, $goOut = true)
  {
    Error::message($query->getError() . " " . $query->getDbErrno() . " - " . $query->getDbError() . "." . $query->getSQL());

    if (defined("OPEN_DEBUG") && OPEN_DEBUG)
    {
      echo "\n<!-- _dbErrno = " . $query->getDbErrno() . "-->\n";
      echo "<!-- _dbError = " . $query->getDbError() . "-->\n";
      if ($query->getSQL() != "")
      {
        echo "<!-- _SQL = " . $query->getSQL() . "-->\n";
      }
    }

    if ($query->getDbErrno() == 1049) // Unable to connect to database
    {
      echo '<p><a href="../install.html">' . "Install instructions". "</a></p>\n";
    }

    if ($goOut)
    {
      exit($query->getError());
    }
  }

  /**
   * void connection(DbConnection $conn, bool $goOut = true)
   *
   * Displays the connection error page
   *
   * @param DbConnection $conn DbConnection object containing connection error parameters.
   * @param bool $goOut if true, execute an exit()
   * @return void
   * @access public
   */
  function connection($conn, $goOut = true)
  {
    Error::message($conn->getError() . " " . $conn->getDbErrno() . " - " . $conn->getDbError() . "." . $conn->getSQL());

    if (defined("OPEN_DEBUG") && OPEN_DEBUG)
    {
      echo "\n<!-- _dbErrno = " . $conn->getDbErrno() . "-->\n";
      echo "<!-- _dbError = " . $conn->getDbError() . "-->\n";
      echo "<!-- _error = " . $conn->getError() . "-->\n";
      if ($conn->getSQL() != "")
      {
        echo "<!-- _SQL = " . $conn->getSQL() . "-->\n";
      }
    }

    if ($conn->getDbErrno() == 1049) // Unable to connect to database
    {
      echo '<p><a href="../install.html">' . "Install instructions". "</a></p>\n";
    }

    if ($goOut)
    {
      exit($conn->getError());
    }
  }

  /**
   * void fetch(Query $query, bool $goOut = true)
   *
   * Displays the fetch error page
   *
   * @param bool $goOut if true, execute an exit()
   * @return void
   * @access public
   * @since 0.7
   */
  function fetch($query, $goOut = true)
  {
    if ($query->getSQL() != "")
    {
      echo "\n<!-- _SQL = " . $query->getSQL() . "-->\n";
    }

    if ($goOut)
    {
      exit($query->getError());
    }
  }

  /**
   * void message(string $errorMsg, int $errorType = E_USER_WARNING)
   *
   * Displays an error message
   *
   * @param string $errorMsg
   * @param int $errorType (optional) E_USER_WARNING by default
   * @return void
   * @access public
   */
  function message($errorMsg, $errorType = E_USER_WARNING)
  {
    trigger_error($errorMsg, $errorType);
  }

  /**
   * string backTrace(array $context)
   *
   * Returns information about backtracing of a function
   *
   * @param array $context
   * @return string information about backtracing
   * @access public
   * @since 0.7
   */
  function backTrace($context)
  {
    $calls = "\nBacktrace:";
    $trace = (function_exists("debug_backtrace") ? debug_backtrace() : null); // SF.net DEMO version PHP 4.1.2

    // Start at 2 -- ignore this function (0) and the customHandler() (1)
    for ($x = 2; $x < count($trace); $x++)
    {
      $callNo = $x - 2;
      $calls .= "\n " . $callNo . ": " . $trace[$x]["function"];
      $calls .= " (line " . $trace[$x]["line"] . " in " . $trace[$x]["file"] . ")";
    }

    $calls .= "\nVariables in " . (isset($trace[2]["function"]) ? $trace[2]["function"] : "UNKNOWN") . "():";

    // Use the $context to get variable information for the function with the error
    foreach ($context as $key => $value)
    {
      $calls .= "\n " . $key . " is " . (( !empty($value) ) ? serialize($value) : "NULL");
    }

    return $calls;
  }

  /**
   * void customHandler(int $number, string $message, string $file, int $line, array $context)
   *
   * Custom error handler
   *
   * @param int $number type of error
   * @param string $message
   * @param string $file
   * @param int $line
   * @param array $context
   * @return void
   * @access public
   * @since 0.7
   */
  function customHandler($number, $message, $file, $line, $context)
  {
    $goOut = false;

    switch ($number)
    {
      case E_USER_ERROR:
        $error = "\nERROR";
        $goOut = true;
        break;

      case E_WARNING:
      case E_USER_WARNING:
        $error = "\nWARNING";
        break;

      case E_NOTICE:
        if (defined("OPEN_DEBUG") && !OPEN_DEBUG)
        {
          return; // do nothing
        }
        //break; // don't remove comment mark

      case E_USER_NOTICE:
        $error = "\nNOTICE";
        break;

      default:
        $error = "\nUNHANDLED ERROR";
        break;
    }

    $error .= " on line " . $line . " in " . $file . ".\n";
    $error .= Error::backTrace($context);
    //$error .= "\n" . $message;
    $error .= "\nClient IP: " . $_SERVER["REMOTE_ADDR"];

    $prepend = "\n[PHP Error " . date("Y-m-d H:i:s") . "]";
    $error = ereg_replace("\n", $prepend, $error);
    $error .= "\n" . str_repeat("_", 78); // separator line

    if ( !defined("OPEN_SCREEN_ERRORS") || OPEN_SCREEN_ERRORS )
    {
      echo '<pre>' . $error . "</pre>\n";
    }

    if (defined("OPEN_LOG_ERRORS") && OPEN_LOG_ERRORS)
    {
      $logDir = substr(OPEN_LOG_FILE, 0, strrpos(OPEN_LOG_FILE, "/"));
      //echo $logDir; // debug
      if (is_dir($logDir))
      {
        error_log($error, 3, OPEN_LOG_FILE);
      }
    }

    if ($goOut)
    {
      $_SESSION = array();
      session_destroy();
      exit();
    }
  }
} // end class
?>