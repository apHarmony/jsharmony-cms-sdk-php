<?php
/*
Copyright 2021 apHarmony

This file is part of jsHarmony.

jsHarmony is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

jsHarmony is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this package.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace apHarmony\jsHarmonyCms;

use apHarmony\jsHarmonyCms\CmsPage;
use apHarmony\jsHarmonyCms\CmsRedirect;
use apHarmony\jsHarmonyCms\CmsResponse;
use Exception;

class CmsPageNotFoundException extends Exception { public function  __construct($urlPath) { parent::__construct('Page not found: '.$urlPath); } }

class CmsRouter {

  public $config = [
    'content_path' => '.',              //(string) File path to published CMS content files
    'redirect_listing_path' => null,    //(string) Path to redirect listing JSON file (relative to content_path)
    'default_document' => 'index.html', //(string) Default Directory Document
    'strict_url_resolution' => false,   //(bool) Whether to support URL variations (appending "/" or Default Document)
    'passthru_timeout' => 30,           //(int) Maximum number of seconds for passthru request
    'cms_clientjs_editor_launcher_path' => '/.jsHarmonyCms/jsHarmonyCmsEditor.js', //(string) Path where router will serve the client-side JS script that launches CMS Editor
    'cms_server_urls' => [],            //Array(string) The CMS Server URLs that will be enabled for Page Editing (set to '*' to enable any remote CMS)
                                        //  * Used by CmsPage->editorScript, and the getEditorScript function
                                        //  * NOT used by jsHarmonyCmsEditor.js - the launcher instead uses access_keys for validating the remote CMS
  ];

  public function __construct($config = []){
    if(!$config) $config = [];
    $this->config = array_merge($this->config, $config);
  }

  /**
   * serve [Main Entry Point] - Serve CMS Content
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @param array $options An associative array that may have any of the following keys:
   *      onPage:          (function($router, $filename){}) Function to execute on page route
   *                       Default: function($router, $filename){ $router->serveFile($filename); return true; }
   *      on301:           (function($router, $url){}) Function to execute when a 301 redirect is processed
   *                       Default: function($router, $url){ $router->redirect301($url); return true; }
   *      on302:           (function($router, $url){}) Function to execute when a 302 redirect is processed
   *                       Default: function($router, $url){ $router->redirect302($url); return true; }
   *      onPassthru:      (function($router, $url){}) Function to execute when a PASSTHRU redirect is processed
   *                       Default: function($router, $url){ $router->passthru($url)->serve(); return true; }
   *      on404:           (function($router){}|null) Function to execute when on 404 / Page Not Found.  Set to null to continue on Page Not Found.
   *                       Default: null
   *      onError:         (function($router, $err){}|null) Function to execute when an unexpected error occurs.  If null, Exception will be thrown instead.
   *                       Default: null
   *      serveCmsEditorScript: (bool) Whether the router should serve the CMS Editor Launcher script at config['cms_clientjs_editor_launcher_path']
   *                       Default: true
   * @return mixed Result of the onPage, on301, on302, onPassthru, on404, or onError handler, or TRUE if the return value is null or not defined.
   * @SuppressWarnings(PHPMD.Superglobals)
   */
  public function serve(?string $url = null, array $options = []){
    $options = array_merge([
      'onPage' => function($router, $filename){ $router->serveFile($filename); return true; },
      'on301' => function($router, $url){ $router->redirect301($url); return true; },
      'on302' => function($router, $url){ $router->redirect302($url); return true; },
      'onPassthru' => function($router, $url){ $router->passthru($url)->serve(); return true; },
      'on404' => null,
      'onError' => null,
      'serveCmsEditorScript' => true,
    ], $options);

    try{
      if(is_null($url)) $url = $this->getCurrentUrl();
      $url = $this->prefixUrlSlash($url);
      $urlPath = $this->extractUrlPath($url);
      if($options['serveCmsEditorScript']){
        if($urlPath==$this->config['cms_clientjs_editor_launcher_path']){
          $filename = __DIR__.'/../clientjs/jsHarmonyCmsEditor.min.js';
          if($options['onPage']) return $options['onPage']($this, $filename) ?? true;
          return true;
        }
      }
      $response = $this->route($url);
      if($response === null){
        if($options['on404']){
          return $options['on404']($this) ?? true;
        }
        return false;
      }
      if($response->type == 'page'){
        if($options['onPage']) return $options['onPage']($this, $response->filename) ?? true;
        return true;
      }
      if($response->type == 'redirect'){
        if($response->redirect){
          if($response->redirect->http_code=='301'){
            if($options['on301']) return $options['on301']($this, $response->redirect->url) ?? true;
          }
          else if($response->redirect->http_code=='302'){
            if($options['on302']) return $options['on302']($this, $response->redirect->url) ?? true;
          }
          else if($response->redirect->http_code=='PASSTHRU'){
            //Prefix URL
            $passthruUrl = $response->redirect->url;
            $passthruUrlParts = parse_url($passthruUrl);
            if($passthruUrl && $passthruUrlParts && !isset($passthruUrlParts['scheme'])){
              $curUrlParts = parse_url(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
              if($curUrlParts && isset($curUrlParts['scheme']) && $curUrlParts['scheme']){
                $passthruUrlBase =
                  $curUrlParts['scheme'].'://'.
                  ($curUrlParts['host'] ?? '').
                  (isset($curUrlParts['port']) ? ':'.$curUrlParts['port'] : '').
                  ($curUrlParts['user'] ?? '').
                  (isset($curUrlParts['pass']) ? ':'.$curUrlParts['pass'] : '').
                  (isset($curUrlParts['user']) || isset($curUrlParts['pass']) ? '@' : '');
                if($passthruUrl[0]!='/'){
                  if(isset($curUrlParts['path'])){
                    $passthruUrlBase .= $this->dirname($curUrlParts['path']);
                  }
                }
                $passthruUrl = $passthruUrlBase.$passthruUrl;
              }
            }
            if($options['onPassthru']) return $options['onPassthru']($this, $passthruUrl) ?? true;
          }
          else throw new Exception('Invalid redirect HTTP code: '.$response->redirect->http_code);
        }
        return true;
      }
    }
    catch(CmsPageNotFoundException $ex){
      if($options['on404']){
        return $options['on404']($this) ?? true;
      }
      return false;
    }
    catch(Exception $ex){
      if($options['onError']) return $options['onError']($this, $ex) ?? true;
      else throw $ex;
    }
    return true;
  }

  /**
   * getStandalone [Main Entry Point] - Get CMS Page Data for Standalone Integration
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @return CmsPage Page Content
   * If page is opened from CMS Editor or Not Found, an empty CmsPage Object will be returned
   */
  public function getStandalone(?string $url = null){
    $page = new CmsPage();
    $page->isInEditor = $this->isInEditor();
    if($page->isInEditor) $page->editorScript = $this->getEditorScript();
    else {
      $pageData = $this->getPage($url);
      if($pageData===null){
        $page->notFound = true;
      }
      else{
        $page = $pageData;
      }
    }
    return $page;
  }

  /**
   * getPlaceholder - Get Placeholder Page for Editor template rendering
   * @return CmsPage Placeholder Page
   */
  public function getPlaceholder(){
    $page = new CmsPage();
    $page->isInEditor = $this->isInEditor();
    if($page->isInEditor) $page->editorScript = $this->getEditorScript();
    return $page;
  }

  /**
   * isInEditor - Check whether page is currently in CMS Editing Mode
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @return bool True if page is opened from CMS Editor
   */
  public function isInEditor(?string $url = null){
    if(is_null($url)) $url = $this->getCurrentUrl();
    $qs = $this->getQuery($url);
    return (isset($qs['jshcms_token']) && $qs['jshcms_token']);
  }

  /**
   * resolve - Convert URL to CMS Content Path
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @param array $options An associative array that may have any of the following keys:
   *      strictUrlResolution: (bool) Whether to try URL variations (adding "/", "/<default_document>")
   *      variation:           (int)  Starting Variation ID
   * @return string CMS Content Path
   */
  public function resolve(?string $url = null, array $options = []){
    $options = array_merge([
      'strictUrlResolution' => $this->config['strict_url_resolution'],
      'variation' => 1,
    ], $options);

    if(is_null($url)) $url = $this->getCurrentUrl();
    $url = $this->prefixUrlSlash($url);
    $urlPath = $this->extractUrlPath($url);
    //Add url prefix
    $url = $this->joinPath($this->config['content_path'], $urlPath);
    if(!$options['strictUrlResolution']){
      //Add trailing slash and "/index.html", if applicable
      if($url && (($url[strlen($url)-1]=='/')||($url[strlen($url)-1]=='\\'))){
        $url = $this->joinPath($url, $this->config['default_document']);
      }
      if($options['variation']==1){ /* Do nothing */ }
      if($options['variation']==2){
        $urlExt = $this->getExtension($url);
        $defaultExt = $this->getExtension($this->config['default_document']);
        if($urlExt && $defaultExt && ($urlExt == $defaultExt)) $options['variation'] = (int)$options['variation'] + 1;
        else $url = $this->joinPath($url, $this->config['default_document']);
      }
      if($options['variation']>=3) throw new CmsPageNotFoundException($urlPath);
    }
    else if($options['variation']>=2) throw new CmsPageNotFoundException($urlPath);
    return $url;
  }

  /**
   * Find match in CMS router for target URL
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @return CmsResponse|null Response with Page Filename, Redirect, or null if not found
  */
  public function route(?string $url = null){

    if(is_null($url)) $url = $this->getCurrentUrl();

    $redirects = $this->getRedirectData();
    $redirect = $this->matchRedirect($redirects, $url);
    if($redirect){
      $response = new CmsResponse('redirect');
      $response->redirect = $redirect;
      return $response;
    }

    try{
      $pageFilename = $this->getPageFileName($url);

      $response = new CmsResponse('page');
      $response->filename = $pageFilename;
      return $response;
    }
    catch(CmsPageNotFoundException $ex){
      return null;
    }
  }

  /**
   * matchRedirect - Check if URL matches redirects and return first match
   * @param array|null $redirects Array of CMS Redirects (from getRedirectData function)
   * @param string|null $url Target URL to match against the CMS Redirects
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @return CmsRedirect|null Redirect
   */
  public function matchRedirect(?array $redirects, ?string $url){
    if(is_null($url)) $url = $this->getCurrentUrl();
    $url = $this->prefixUrlSlash($url);
    $urlPath = $this->extractUrlPath($url);

    if($redirects && count($redirects)){
      foreach($redirects as $redirect){
        if(!$redirect) continue;
        $cmpUrlPath = strval($redirect['redirect_url']?:'');
        $destUrl = strval($redirect['redirect_dest']?:'');
        if($redirect['redirect_url_type']=='EXACT'){
          if($urlPath != $cmpUrlPath) continue;
        }
        else if($redirect['redirect_url_type']=='EXACTICASE'){
          if(strtolower($urlPath) != strtolower($cmpUrlPath)) continue;
        }
        else if(($redirect['redirect_url_type']=='BEGINS')||($redirect['redirect_url_type']=='BEGINSICASE')){
          if(!$this->beginsWith($urlPath, $cmpUrlPath, ($redirect['redirect_url_type']=='BEGINSICASE'))) continue;
        }
        else if(($redirect['redirect_url_type']=='REGEX')||($redirect['redirect_url_type']=='REGEXICASE')){
          $rxFlags = ($redirect['redirect_url_type']=='REGEXICASE') ? 'i' : '';
          if(!preg_match('/'.str_replace("/","\\/",$cmpUrlPath).'/'.$rxFlags, $urlPath)) continue;
          $destUrl = preg_replace('/'.str_replace("/","\\/",$cmpUrlPath).'/'.$rxFlags, $redirect['redirect_dest'], $urlPath);
        }
        return new CmsRedirect($redirect['redirect_http_code'], $destUrl);
      }
    }
    return null;
  }

  /**
   * getRedirectData - Get CMS Redirect Data
   * @return array|null JSON array of CMS redirects
   */
  public function getRedirectData(){
    $redirectListingPath = $this->config['redirect_listing_path'];
    if(!$redirectListingPath) return null;
    if(!$this->pathIsAbsolute($redirectListingPath)){
      $redirectListingPath = $this->joinPath($this->config['content_path'], $redirectListingPath);
    }
    return $this->getJsonFile($redirectListingPath);
  }

  /**
   * getEditorScript - Generate script for CMS Editor
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @return string HTML Code to launch the CMS Editor
   * If the page was not launched from the CMS Editor, an empty string will be returned
   * The querystring jshcms_url parameter is validated against config['cms_server_urls']
   * If the CMS Server is not found in config['cms_server_urls'], an empty string will be returned
   */
  public function getEditorScript(?string $url = null){
    if(is_null($url)) $url = $this->getCurrentUrl();
    $qs = $this->getQuery($url);
    if (!isset($qs['jshcms_token']) || !$qs['jshcms_token']) return '';
    if (!isset($qs['jshcms_url']) || !$qs['jshcms_url']) return '';
    //Validate URL
    $cmsServerUrl = $qs['jshcms_url'];
    if(!is_array($this->config['cms_server_urls'])) $this->config['cms_server_urls'] = [$this->config['cms_server_urls']];
    $foundMatch = false;
    $curUrl = parse_url($cmsServerUrl);
    if($curUrl){
      foreach($this->config['cms_server_urls'] as $testUrl){
        $testUrl = strval($testUrl ?? '');
        if(!$testUrl) continue;
        if($testUrl=='*'){ $foundMatch = true; break; }
        try{
          $parsedUrl = parse_url($testUrl);
          if($parsedUrl){
            $strEqual = function($a, $b){ return strtolower(strval($a??''))==strtolower(strval($b??'')); };
            $strPortEqual = function($a, $b, $schemeA, $schemeB) use($strEqual){
              $a = $a ?? '';
              $b = $b ?? '';
              if(!$a && ($schemeA=='https')) $a = 443;
              if(!$b && ($schemeB=='https')) $b = 443;
              if(!$a && ($schemeA=='http')) $a = 80;
              if(!$b && ($schemeB=='http')) $b = 80;
              return $strEqual($a, $b);
            };
            if(($parsedUrl['scheme']??'') && !$strEqual($curUrl['scheme']??'',$parsedUrl['scheme']??'')) continue;
            if(!$strEqual($curUrl['host']??'',$parsedUrl['host']??'')) continue;
            if(!$strPortEqual($curUrl['port']??'',$parsedUrl['port']??'',$curUrl['scheme']??'',($parsedUrl['scheme']??'')?:($curUrl['scheme']??''))) continue;
            $parsedPath = ($parsedUrl['path'] ?? '') ?: '/';
            $curPath = ($curUrl['path'] ?? '') ?: '/';
            if(strpos($curPath, $parsedPath)!==false){ $foundMatch = true; break; }
          }
        }
        catch(Exception $ex){
        }
      }
    }
    if(!$foundMatch) return '';
    return '<script type="text/javascript" src="'.$this->escapeHTMLAttr($this->joinPath($cmsServerUrl,'/js/jsHarmonyCMS.js')).'"></script>';
  }

  /**
   * serveFile - Serves a file to the user
   * @param string $filePath Path to target file
   */
  public function serveFile($filePath){
    header('Content-Type: ' . mime_content_type($filePath));
    readfile($filePath);
  }

  /**
   * redirect301 - Perform 301 Redirect
   * @param string $url Target URL for Redirect
   */
  public function redirect301(string $url){
    $url = $this->escapeURL($url);
    http_response_code(301);
    header("Location: ".$url);
  }

  /**
   * redirect302 - Perform 302 Redirect
   * @param string $url Target URL for Redirect
   */
  public function redirect302(string $url){
    $url = $this->escapeURL($url);
    http_response_code(302);
    header("Location: ".$url);
  }

  /**
   * passthru - Perform Passthru Request
   * @param string $url Target URL for Passthru Redirect
   * @return CmsPassthruResponse Response
   * Call the CmsPassthruResponse->serve() method to serve the page
   */
  public function passthru(string $url){
    $url = $this->escapeURL($url);
    if($this->beginsWith($url, 'http://') || $this->beginsWith($url, 'https://')){
      $conn = curl_init($url);
      curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($conn, CURLOPT_FRESH_CONNECT,  true);
      curl_setopt($conn, CURLOPT_TIMEOUT, $this->config['passthru_timeout']);
      curl_setopt($conn, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
      $rsltContent = (curl_exec($conn));
      $rsltHttpCode = curl_getinfo($conn, CURLINFO_HTTP_CODE);   //get status code
      $rsltContentType = curl_getinfo($conn, CURLINFO_CONTENT_TYPE);
      $rsltError = null;
      if (curl_errno($conn)) { $rsltError = curl_error($conn); }
      curl_close($conn);

      if($rsltError) throw new Exception($rsltError);

      if ($rsltHttpCode >= 400) throw new CmsPageNotFoundException($url);

      $response = new CmsPassthruResponse();
      $response->http_code = $rsltHttpCode;
      $response->content = $rsltContent;
      $response->content_type = $rsltContentType;
      return $response;
    }
    else throw new Exception('PASSTHRU requires a full HTTP/HTTPS URL');
  }

  /**
   * generate404 - Generate a 404 Not Found Page
   */
  public function generate404(){
    http_response_code(404);
    $this->renderPageText('404 - Not Found', 'Not Found', 'The requested page was not found on this server.');
  }

  /**
   * generateError - Generate a 500 Error Page
   * @param Exception|string $err Error message
   */
  public function generateError($err){
    http_response_code(500);
    $this->renderPageText('System Error', 'System Error', strval($err));
  }

  /**
   * getPage - Get CMS Page Data
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @param array $options An associative array that may have any of the following keys:
   *      variation:           (int)  Starting Variation ID
   * @return CmsPage|null Page Content, or null if page was not found
   */
  public function getPage(?string $url = null, array $options = []){
    $options = array_merge([
      'variation' => 1,
    ], $options);

    if(is_null($url)) $url = $this->getCurrentUrl();
    
    try{
      $pageFilename = $this->getPageFileName($url);
      return $this->getPageFromFile($pageFilename);
    }
    catch(CmsPageNotFoundException $ex){
      return null;
    }
  }

  /**
   * getPageFromFile - Get CMS Page from File
   * @param string $filePath Path to target file
   * @return CmsPage|null Page Content, or null if page was not found or an error occurrerd
   */
  public function getPageFromFile(string $filePath){
    try{
      $pageContent = $this->getFile($filePath);
      $pageData = json_decode($pageContent, true);
      return CmsPage::fromArray($pageData);
    }
    catch(\Exception $ex){
      return null;
    }
  }

  /**
   * getPageFileName - Get CMS Page File Content
   * @param string|null $url CMS Page URL
   *      Use Full URL, Root-relative URL, or leave blank for current URL
   * @param array $options An associative array that may have any of the following keys:
   *      variation:           (int)  Starting Variation ID
   * @return string Full path to CMS content file
   */
  public function getPageFileName(?string $url = null, array $options = []){
    $options = array_merge([
      'variation' => 1,
    ], $options);

    $contentPath = $this->resolve($url, $options);
    if(!is_file($contentPath)){
      $options['variation']++;
      return $this->getPageFileName($url, $options);
    }
    return realpath($contentPath);
  }

  /**
   * getFile - Reads a file from the file system
   * @param string $filePath Path to target file
   * @return string File content
   */
  public function getFile($filePath){
    if(!$filePath) throw new Exception('Blank file path');
    if(!$this->pathIsAbsolute($filePath)){
      $filePath = $this->joinPath($this->config['content_path'], $filePath);
    }
    return file_get_contents($filePath);
  }

  /**
   * getJsonFile - Reads and parses a JSON file
   * @param string $filePath Path to target file
   * @return array|null JSON content, or null if file was not found or an error occurred
   */
  public function getJsonFile($filePath){
    try{
      $content = $this->getFile($filePath);
      return json_decode($content, true);
    }
    catch(\Exception $ex){
      return null;
    }
  }

  //==================
  //Internal Functions
  //==================

  //CMS Helper Functions
  //--------------------

  private function getQuery($url){
    $purl = parse_url($url);
    if($purl){
      if(isset($purl['query']) && $purl['query']){
        $qs = [];
        parse_str($purl['query'], $qs);
        return $qs ?? [];
      }
    }
    return [];
  }

  /**
   * @SuppressWarnings(PHPMD.Superglobals)
   */
  private function getCurrentUrl(){
    return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
  }

  private function prefixUrlSlash($url){
    if(!$url) return '/';
    if(strpos($url, '//')===false){
      if(strpos($url, '/') !== 0){
        if(strpos($url, "\\")===0) $url = substr($url, 1);
        $url = '/'.$url;
      }
    }
    return $url;
  }

  private function extractUrlPath($url){
    //Extract path
    $purl = parse_url($url);
    if(!$purl) throw new Exception('Invalid URL: '.$url);
    $urlPath = (isset($purl['path']) ? $purl['path'] : '');
    if(!$urlPath || ($urlPath[0] != '/')) $urlPath = '/'.$urlPath;
    return $urlPath;
  }

  private function renderPage($pageTitle, $bodyTitle, $body){
    echo implode('',[
      '<!DOCTYPE HTML><html>',
      '<head>',
      '<meta charset="utf-8"/>',
      '<title>'.$this->escapeHTML($pageTitle).'</title>',
      '<style>body { font-family: sans-serif; }</style>',
      '</head>',
      '<body>',
      '<h1>'.$this->escapeHTML($bodyTitle).'</h1>',
      $body,
      '</body>',
      '</html>'
    ]);
  }

  private function renderPageText($pageTitle, $bodyTitle, $bodyText){
    return $this->renderPage($pageTitle, $bodyTitle, $this->escapeHTML($bodyText));
  }

  //Utility - Path
  //--------------

  private function pathIsAbsolute($path) {
    return $path && !!preg_match("/^[\\\\\/]|([A-Za-z]:(?![^\\\\\/]))/",$path);
  }

  private function joinPath($a, $b){
    $a = $a ?: '';
    $b = $b ?: '';
    if(!$a) return $b;
    if(!$b) return $a;
    $aEnd = $a[strlen($a)-1];
    $bStart = $b[0];
    while(strlen($a) && (($aEnd=='/')||($aEnd=='\\'))){ $a = substr($a, 0, strlen($a)-1); if($a) $aEnd=$a[strlen($a)-1]; }
    while(strlen($b) && (($bStart=='/')||($bStart=='\\'))){ $b = substr($b, 1); if($b) $bStart=$b[0]; }
    return $a.'/'.$b;
  }

  private function dirname($path){
    if(!$path) return '/';
    if($path[0]!='/') $path = '/'.$path;
    $lastSlash = strrpos($path, '/');
    $path = substr($path, 0, $lastSlash+1);
    return $path;
  }

  private function getExtension($path){
    if(!$path) return '';
    $lastSlash = 0;
    for($i=strlen($path)-1;$i>=0;$i--){
      if(($path[$i]=='/')||($path[$i]=="\\")){ $lastSlash = $i+1; break; }
    }
    $path = substr($path, $lastSlash);
    if(!$path) return '';
    $lastDot = strrpos($path, '.');
    if($lastDot !== false) $path = substr($path, $lastDot);
    return $path;
  }

  //Utility - PHP Extensions
  //------------------------

  private function beginsWith($haystack, $needle, $caseInsensitive = false){
    if($caseInsensitive) return (strncmp(strtolower($haystack), strtolower($needle), strlen($needle)) == 0); 
    return (strncmp($haystack, $needle, strlen($needle)) == 0); 
  }

  private function escapeURL($url){
    return str_replace('%', urlencode('%'), $url ?? '');
  }

  private function escapeHTML($val){
    return htmlspecialchars($val);
  }

  private function escapeHTMLAttr($val){
    return htmlspecialchars($val);
  }
  
}
