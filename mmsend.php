#!/usr/bin/php
<?php
/**
 * Simple Mattermost send CLI utility written in PHP
 * mmsend.php
 * Author Joaquim Homrighausen
 * Version 2017.03
 *
 * Sponsored by WebbPlatsen i Sverige AB, Stockholm Sweden, www.webbplatsen.se
 *
 * If you break this code, you own all the pieces :)
 *
 * You need the PHP CLI binary installed on the server, with cURL functionality
 *
 * You need to make this script executable, or run it via the PHP-CLI interpreter manually
 *
 * Any code marked as "CLI" can be removed if you want to embed this somehow
 *
 * Any fprintf (STDERR, ...) should be replaced with echo, or some other very
 * cool output function.
 *
 *
 * MIT License
 * Copyright (c) 2017 ComXSentio AB; All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

//CLI begin -------------------------------------------------------------------
if (!function_exists ('php_sapi_name'))
    {
    fprintf (STDERR, __FILE__.": unable to determine SAPI, php_sapi_name() seems to be missing\n");
    exit (1);
    }
if (php_sapi_name () !== 'cli')
    {
    fprintf (STDERR, __FILE__.": unable to determine SAPI, we want CLI\n");
    exit (1);
    }
define ('MYFILEROOT', dirname (realpath ($_SERVER ['SCRIPT_FILENAME'])));
$ifn = MYFILEROOT.'/'.basename (realpath ($_SERVER ['SCRIPT_FILENAME']), '.php').'.ini';
$logfn = MYFILEROOT.'/'.basename (realpath ($_SERVER ['SCRIPT_FILENAME']), '.php').'.log';
ini_set ('error_log', $logfn);
ini_set ('log_errors', 'on');
ini_set ('display_errors', 'on');
ini_set ('max_execution_time', 600);
error_reporting (E_ALL|E_NOTICE|E_DEPRECATED);
set_time_limit (600);
$myArgC = $_SERVER ['argc'];
$myArgV = $_SERVER ['argv'];

function showHelp ()
{
    fprintf (STDERR, "mmsend by JoHo\n\n");
    fprintf (STDERR, "usage: mmsend [<section>|none] [test|send] [<url>|config] [<text>] \n\n");
    fprintf (STDERR, "  section  Use settings from [section] in .ini file\n");
    fprintf (STDERR, "  none     Do not use settings from any section in .ini file\n");
    fprintf (STDERR, "\n");
    fprintf (STDERR, "  test     Test, don't actually send anything\n");
    fprintf (STDERR, "  send     Do actually send something\n");
    fprintf (STDERR, "\n");
    fprintf (STDERR, "  url      The complete URL for the incoming webhook\n");
    fprintf (STDERR, "  config   Read URL from configuration file\n");
    fprintf (STDERR, "\n");
    fprintf (STDERR, "  text     Text to send as message (overrides configuraiton file value)\n");
    fprintf (STDERR, "           (use matching quotes for text containing spaces)\n");
    exit (1);
}

if ($myArgC < 4)
    showHelp ();
$xcSection = trim ($myArgV [1]);
$xcMode = trim ($myArgV [2]);
$xcURL = trim ($myArgV [3]);
if (!empty ($myArgV [4]))
    $xcText = trim ($myArgV [4]);
else
    $xcText = '';
if ($xcSection == 'none' && $xcURL == 'config')
    {
    fprintf (STDERR, "%s: If you want to use text from configuration file, you need to specify a section\n", $ifn);
    exit (2);
    }
if ($xcMode != 'test' && $xcMode != 'send')
    {
    fprintf (STDERR, "%s: Mode must be either 'test' or 'send'\n", $ifn);
    exit (2);
    }

$ifp = parse_ini_file ($ifn, true);
if (!is_array ($ifp))
    {
    fprintf (STDERR, "%s: Unable to parse file\n", $ifn);
    exit (2);
    }
if ($xcSection != 'none' && empty ($ifp [$xcSection]))
    {
    fprintf (STDERR, "%s: Specified section (%s) not found\n", $ifn, $xcSection);
    exit (2);
    }
if ($xcURL == 'config')
    {
    if (empty ($ifp [$xcSection]['url']))
        {
        fprintf (STDERR, "%s: 'config' specified as url, but no 'url' setting found in section %s\n", $ifn, $xcSection);
        exit (2);
        }
    $xcURL = $ifp [$xcSection]['url'];
    }
if (empty ($xcText))
    {
    if (empty ($ifp [$xcSection]['text']))
        {
        fprintf (STDERR, "%s: No text specified on command-line, and no 'text' found in section %s\n", $ifn, $xcSection);
        exit (2);
        }
    $xcText = $ifp [$xcSection]['text'];
    }

if ($xcMode == 'test')
    {
    fprintf (STDERR, "Test mode enabled. Not sending anything.\n\n");
    fprintf (STDERR, " URL: %s\n", $xcURL);
    fprintf (STDERR, "Text: %s\n", $xcText);
    exit (4);
    }

if (!empty ($ifp [$xcSection]['channel']))
    $xcChannel = $ifp [$xcSection]['channel'];
else
    $xcChannel = '';

//CLI end ---------------------------------------------------------------------

$payload = '{';
$payload .= '"text":"'.$xcText.'"';
if (strlen ($xcChannel) > 0)
    $payload .= ',"channel":"'.$xcChannel.'"';
$payload .= '}';

$zCurlH = curl_init ($xcURL);
if (!is_resource ($zCurlH))
    {
    fprintf (STDERR, "mmsend: Unable to initialize cURL\n");
    exit (3);
    }
$cOptArr = array (
    CURLOPT_URL => $xcURL,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1
    );
$rc = curl_setopt_array ($zCurlH, $cOptArr);
if ($rc == false)
    {
    curl_close ($zCurlH);
    fprintf (STDERR, "mmsend: Unable to do curl_setopt_array ()\n");
    exit (3);
    }
$rc = curl_setopt ($zCurlH, CURLOPT_POSTFIELDS, http_build_query (array ('payload' => $payload)));
if ($rc == false)
    {
    curl_close ($zCurlH);
    fprintf (STDERR, "mmsend: Unable to set cURL CURLOPT_POSTFIELDS\n");
    exit (3);
    }
$rc = curl_exec ($zCurlH);
if ($rc === false)
    {
    curl_close ($zCurlH);
    fprintf (STDERR, "mmsend: Unable to issue curl_exec (), cURL said %s\n", curl_error ($zCurlH));
    exit (3);
    }

//Since we tell cURL to return transfer results, you may want to check the
//contents of $rc, it will most likely contain a Mattermost response encoded
//in JSON.

curl_close ($zCurlH);
exit (0);
?>