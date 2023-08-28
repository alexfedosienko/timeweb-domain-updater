<?php

namespace TimewebAutoupdateIP\Service;

class Log
{
  public static function info($message)
  {
    $message = date("d.m.Y H:i:s", strtotime("now")) . ": " . $message . "\n";
    // echo $message;
    fwrite(STDOUT, $message);
  }
}
