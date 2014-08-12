<?php

/** This file is part of KCFinder project
  *
  *      @desc Base configuration file
  *   @package KCFinder
  *   @version 3.0-dev
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
  *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
  *      @link http://kcfinder.sunhater.com
  */

// IMPORTANT!!! Do not remove uncommented settings in this file even if
// you are using session configuration.
// See http://kcfinder.sunhater.com/install for setting descriptions


$_CONFIG = array(


// GENERAL SETTINGS

    'disabled' => true,
    'theme' => "oxygen",
    'uploadURL' => "../upload",
    'uploadDir' => "",

    'types' => array(

    // (F)CKEditor types
        'files'   =>  "",
        'flash'   =>  "swf",
        'images'  =>  "*img",

    // TinyMCE types
        'file'    =>  "",
        'media'   =>  "swf flv avi mpg mpeg qt mov wmv asf rm",
        'image'   =>  "*img",
    ),


// IMAGE SETTINGS

    'imageDriversPriority' => "imagick gmagick gd",
    'jpegQuality' => 96,
    'thumbsDir' => ".thumbs",

    'maxImageWidth' => 0,
    'maxImageHeight' => 0,

    'thumbWidth' => 100,
    'thumbHeight' => 100,

    'watermark' => "",


// DISABLE / ENABLE SETTINGS

    'denyZipDownload' => false,
    'denyUpdateCheck' => false,
    'denyExtensionRename' => false,


// PERMISSION SETTINGS

    'dirPerms' => 0755,
    'filePerms' => 0644,

    'access' => array(

        'files' => array(
            'upload' => true,
            'delete' => true,
            'copy'   => true,
            'move'   => true,
            'rename' => true
        ),

        'dirs' => array(
            'create' => true,
            'delete' => true,
            'rename' => true
        )
    ),

    'deniedExts' => "exe com msi bat php phps phtml php3 php4 php5 php6 cgi pl",


// MISC SETTINGS

    'filenameChangeChars' => array(
        ' ' => "-",
        ':' => "-",
        '--' => '-',
        'ě' => 'e',
        'š' => 's',
        'č' => 'c',
        'ř' => 'r',
        'ž' => 'z',
        'ý' => 'y',
        'á' => 'a',
        'í' => 'i',
        'é' => 'e',
        'Ě' => 'E',
        'Š' => 'S',
        'Č' => 'C',
        'Ř' => 'R',
        'Ž' => 'Z',
        'Ý' => 'Y',
        'Á' => 'A',
        'Í' => 'I',
        'É' => 'E',
    ),

    'dirnameChangeChars' => array(
        ' ' => "-",
        ':' => "-",
        '--' => '-',
        'ě' => 'e',
        'š' => 's',
        'č' => 'c',
        'ř' => 'r',
        'ž' => 'z',
        'ý' => 'y',
        'á' => 'a',
        'í' => 'i',
        'é' => 'e',
        'Ě' => 'E',
        'Š' => 'S',
        'Č' => 'C',
        'Ř' => 'R',
        'Ž' => 'Z',
        'Ý' => 'Y',
        'Á' => 'A',
        'Í' => 'I',
        'É' => 'E',
    ),

    'mime_magic' => "",

    'cookieDomain' => "",
    'cookiePath' => "",
    'cookiePrefix' => 'KCFINDER_',


// THE FOLLOWING SETTINGS CANNOT BE OVERRIDED WITH SESSION SETTINGS

    '_check4htaccess' => true,
    //'_cssMinCmd' => "java -jar /path/to/yuicompressor.jar --type css {file}",
    //'_jsMinCmd' => "java -jar /path/to/yuicompressor.jar --type js {file}",
    //'_tinyMCEPath' => "/tiny_mce",

    '_sessionVar' => "KCFINDER",
    //'_sessionLifetime' => 30,
    '_sessionDir' => "../../temp/sessions",

    //'_sessionDomain' => ".mysite.com",
    // '_sessionPath' => "/temp/sessions",
);