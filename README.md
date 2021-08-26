# jsharmony-cms-sdk-php
jsHarmony CMS SDK for PHP

## Installation

Installation and integration instructions are available at [jsHarmonyCMS.com](https://www.jsharmonycms.com/resources/integrations/php/)

## API Documentation

### *apHarmony\jsHarmonyCms\Router Class*

* [Constructor](#router-constructor)
* *Public Properties*
   * [config](#router-config)
* *Public Methods*
   * [serve](#router-serve)
   * [getStandalone](#router-getstandalone)
   * [isInEditor](#router-isineditor)
   * [resolve](#router-resolve)
   * [route](#router-route)
   * [matchRedirect](#router-matchredirect)
   * [getRedirectData](#router-getredirectdata)
   * [getEditorScript](#router-geteditorscript)
   * [serveFile](#router-servefile)
   * [redirect301](#router-redirect301)
   * [redirect302](#router-redirect302)
   * [passthru](#router-passthru)
   * [generate404](#router-generate404)
   * [generateError](#router-generateerror)
   * [getPageData](#router-getpagedata)
   * [getPageFileName](#router-getpagefileName)
   * [getFile](#router-getfile)
   * [getJsonFile](#router-getjsonfile)


### *apHarmony\jsHarmonyCms\Page Class*

* *Public Properties*
   * [seo](#page-seo)
       * [title](#page-seo-title)
       * [keywords](#page-seo-keywords)
       * [metadesc](#page-seo-metadesc)
       * [canonical_url](#page-seo-canonical_url)
   * [css](#page-css)
   * [js](#page-js)
   * [header](#page-header)
   * [footer](#page-footer)
   * [title](#page-title)
   * [page_template_id](#page-page_template_id)
   * [content](#page-content)
   * [properties](#page-properties)
   * [isInEditor](#page-isineditor)
   * [editorScript](#page-editorscript)
   * [notFound](#page-notfound)
* *Public Methods*
   * [fromArray](#pagefromarray)

### *apHarmony\jsHarmonyCms\Response Class*

* [Constructor](#response-constructor)
* *Public Properties*
   * [type](#response-type)
   * [filename](#response-filename)
   * [redirect](#response-redirect)


### *apHarmony\jsHarmonyCms\PassthruResponse Class*

* *Public Properties*
   * [http_code](#passthruresponse-http_code)
   * [content_type](#passthruresponse-content_type)
   * [content](#passthruresponse-content)
* *Public Methods*
   * [serve](#passthruresponse-serve)


### *apHarmony\jsHarmonyCms\Redirect Class*

* [Constructor](#redirect-constructor)
* *Public Properties*
   * [http_code](#redirect-http_code)
   * [url](#redirect-url)

---

## *apHarmony\jsHarmonyCms\Router Class*

---

## Router Constructor

```php
new Router($config)
```

#### Arguments

- `$config` (array) :: Associative array with one or more of the configuration keys below:
```php
[
  'content_path' => null,
  //(string) File path to published CMS content files

  'redirect_listing_path' => null,
  //(string) Path to redirect listing JSON file (relative to content_path)

  'default_document' => 'index.html',
  //(string) Default Directory Document

  'strict_url_resolution' => false,
  //(bool) Whether to support URL variations (appending "/" or Default Document)

  'passthru_timeout' => 30,
  //(int) Maximum number of seconds for passthru request

  'cms_clientjs_editor_launcher_path' => '/.jsHarmonyCms/jsHarmonyCmsEditor.js',
  //(string) Path where router will serve the client-side JS script that launches CMS Editor

  'cms_server_urls' => [],
  //Array(string) The CMS Server URLs that will be enabled for Page Editing (set to '*' to enable any remote CMS)
  //  * Used by page.editorScript, and the getEditorScript function
  //  * NOT used by jsHarmonyCmsEditor.js - the launcher instead uses access_keys for validating the remote CMS
]
```

#### Example
```php
$cmsRouter = new Router([ 'cms_server_urls' => ['https://cms.example.com'] ]);
```

---

## Public Properties

---

### Router->config
`(array)`

An associative array with the Router's config.  Config parameters are defined in the Constructor above.
```php
$cmsRouter->config['passthru_timeout'] = 60;
```

---

## Public Methods

---

### Router->serve
`Router->serve(?string $url = null, array $options = [])`

*Main Entry Point* - Serve CMS Content
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
* `$options: (array)` *(Optional)* An associative array that may have any of the following keys:
   ```php
   [
      'onPage' => (function($router, $filename){ }),
      //Function to execute on page route
      //Return value will be passed as return value of "serve" function
      //Default: function($router, $filename){ $router->serveFile($filename); return true; }

      'on301' => (function($router, $url){ }),
      //Function to execute when a 301 redirect is processed
      //Return value will be passed as return value of "serve" function
      //Default: function($router, $url){ $router->redirect301($url); return true; }

      'on302' => (function($router, $url){ }),
      //Function to execute when a 302 redirect is processed
      //Return value will be passed as return value of "serve" function
      //Default: function($router, $url){ $router->redirect302($url); return true; }
      
      'onPassthru' => (function($router, $url){ }),
      //Function to execute when a PASSTHRU redirect is processed
      //Return value will be passed as return value of "serve" function
      //Default: function($router, $url){ $router->passthru($url)->serve(); return true; }

      'on404' => (function($router){ }|null)
      //Function to execute when on 404 / Page Not Found.  Set to null to continue on Page Not Found.
      //Return value will be passed as return value of "serve" function
      //Default: null

      'onError' => (function($router, $err){ }|null) 
      //Function to execute when an unexpected error occurs.  If null, Exception will be thrown instead.
      //Return value will be passed as return value of "serve" function
      //Default: null

      'serveCmsEditorScript' => (bool)
      //Whether the router should serve the CMS Editor Launcher script at config['cms_clientjs_editor_launcher_path']
      //Default: true
   }
   ```
#### Returns
`(mixed)` Result of the onPage, on301, on302, onPassthru, on404, or onError handler, or TRUE if the return value is null or not defined.
#### Example
```php
$cmsRouter->serve();
```

---

### Router->getStandalone
`Router->getStandalone(?string $url = null)`

*Main Entry Point* - Get CMS Page Data for Standalone Integration
#### Parameters:
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(Page)` Page Content

If page is opened from CMS Editor or Not Found, an empty Page Object will be returned
#### Example
```php
$cmsRouter->getStandalone();
```

---

### Router->isInEditor
`Router->isInEditor(?string $url = null)`

Checks whether the page is in CMS Edit mode

#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL

#### Returns
`(bool)` True if this page was opened from the CMS Editor

#### Example
```php
if($cmsRouter->isInEditor()){ /* Perform Operation */ }
```

---

### Router->resolve
`Router->resolve(?string $url = null, array $options = [])`

Converts URL to CMS Content Path
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
* `$options: (array)` *(Optional)* An associative array that may have any of the following keys:
   ```php
   [
      'strictUrlResolution' => (bool), 
      // Whether to try URL variations (adding "/", "/<default_document>")
      // Default: $this->config['strict_url_resolution']

      'variation' => (int)
      // Starting Variation ID
      // Default: 1
   ]
   ```
#### Returns
`(string)` CMS Content Path
#### Example
```php
$contentPath = $cmsRouter->resolve($targetUrl);
```

---

### Router->route
`Router->route(?string $url = null)`

Run CMS router on the target URL
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(Response|null)` Response with Page Filename, Redirect, or null if not found
#### Example
```php
$response = $cmsRouter->route($targetUrl);
```

---

### Router->matchRedirect
`Router->matchRedirect(?array $redirects, ?string $url)`

Check if URL matches redirects and return first match
#### Parameters
* `redirects: (array|null)` Array of CMS Redirects (from getRedirectData function)
* `url: (string|null)` Target URL to match against the CMS Redirects

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(Redirect|null)` Redirect
#### Example
```php
$redirect = $cmsRouter->matchRedirect($cmsRedirects);
if($redirect && ($redirect->http_code=='301')){
  $cmsRouter->redirect301($redirect->url);
}
```

---

### Router->getRedirectData
`Router->getRedirectData()`

Get CMS Redirect Data

Requires `config['redirect_listing_path']` to be defined
#### Returns
`array|null` JSON array of CMS redirects
#### Example
```php
$cmsRedirects = $cmsRouter->getRedirectData();
```

---

### Router->getEditorScript
`Router->getEditorScript(?string $url = null)`

Generate script for CMS Editor
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(string)` HTML Code to launch the CMS Editor

If the page was not launched from the CMS Editor, an empty string will be returned

#### Security

The querystring jshcms_url parameter is validated against `config['cms_server_urls']`

If the CMS Server is not found in `config['cms_server_urls']`, an empty string will be returned
#### Example
```php
echo $cmsRouter->getEditorScript();
```

---

### Router->serveFile
`Router->serveFile($filePath)`

Serves a file to the user
#### Parameters
* `$filePath (string)` Path to target file

#### Example
```php
$pageFilename = $cmsRouter->getPageFileName();
$cmsRouter->serveFile($pageFilename);
```

---

### Router->redirect301
`Router->redirect301(string $url)`

Perform 301 Redirect
#### Parameters
* `$url (string)` Target URL for Redirect

#### Example
```php
$cmsRouter->redirect301('https://example.com');
```

---

### Router->redirect302
`Router->redirect302(string $url)`

Perform 302 Redirect
#### Parameters
* `$url (string)` Target URL for Redirect

#### Example
```php
$cmsRouter->redirect302('https://example.com');
```

---

### Router->passthru
`Router->passthru(string $url)`

Perform Passthru Request
#### Parameters
* `$url (string)` Target URL for Passthru Redirect

#### Returns
`(PassthruResponse)` Response

Call the PassthruResponse->serve() method to serve the page

#### Example
```php
$cmsRouter->passthru('https://example.com')->serve();
```

---

### Router->generate404
`Router->generate404()`

Generate a 404 Not Found Page
#### Parameters
N/A

#### Example
```php
$cmsRouter->generate404();
```

---

### Router->generateError
`Router->generateError($err)`

Generate a 500 Error Page
#### Parameters
* `$err (Exception|string)` Error message

#### Example
```php
$cmsRouter->generateError('An unexpected error has occurred.');
```

---

### Router->getPageData
`Router->getPageData(?string $url = null, array $options = [])`

Get CMS Page Data
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
* `$options: (array)` *(Optional)* An associative array that may have any of the following keys:
   ```php
   [
      'variation' => (int)
      // Starting Variation ID
      // Default: 1
   ]
   ```
#### Returns
`(array|null)` JSON data as an associative array, or null if page was not found
#### Example
```php
$pageData = $cmsRouter->getPageData($targetUrl);
```

---

### Router->getPageFileName
`Router->getPageFileName(?string $url = null, array $options = [])`

Get CMS Page File
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
* `$options: (array)` *(Optional)* An associative array that may have any of the following keys:
   ```php
   [
      'variation' => (int)
      // Starting Variation ID
      // Default: 1
   ]
   ```
#### Returns
`(string)` Full path to CMS content file

`PageNotFoundException` exception is thrown if page is not found
#### Example
```php
$pageFile = $cmsRouter->getPageFile($targetUrl);
```

----

### Router->getFile
`Router->getFile($filePath)`

Reads a file from the file system
#### Parameters
* `$filePath (string)` Path to target file

#### Returns
`(string)` File content

#### Example
```php
$pageFilename = $cmsRouter->getPageFileName();
$pageContent = $cmsRouter->getFile($pageFilename);
```

---

### Router->getJsonFile
`Router->getJsonFile($filePath)`

Reads and parses a JSON file
#### Parameters
* `$filePath (string)` Path to target file

#### Returns
`(array)` JSON content

#### Example
```php
$pageFilename = $cmsRouter->getPageFileName();
$pageData = $cmsRouter->getJsonFile($pageFilename);
```

---

## *apHarmony\jsHarmonyCms\Page Class*

---

## Public Properties

---

### Page->seo

`(PageSeo)`

A class instance with the SEO-related properties for a page

---

### Page->seo->title

`(string)`

Page content for the header `<title>` tag

---

### Page->seo->keywords

`(string)`

Page content for the header `<meta name="keywords" content="..." />` tag

---

### Page->seo->metadesc

`(string)`

Page content for the header `<meta name="description" content="..." />` tag

---

### Page->seo->canonical_url

`(string)`

Page content for the header `<link rel="canonical" href="..." />` tag

---

### Page->css

`(string)`

Page content for a header `<style type="text/css">` tag

---

### Page->js

`(string)`

Page content for a header `<script type="javascript">` tag

---

### Page->header

`(string)`

HTML content to be appended to the `<head>` tag

---

### Page->footer

`(string)`

HTML content to be appended to the end of the `<body>` tag

---

### Page->title

`(string)`

HTML content to be added to an `<h1>` tag in the body

---

### Page->page_template_id

`(string)`

Name of the CMS page template used by this page

---

### Page->content

`(PageDictionary)`

Array of content for this page, indexed by Content Element ID.

#### Example
```php
echo $page->content->body;
echo $page->content->sidebar;
```

If a content area is not defined, its value will be an empty string.

---

### Page->properties

`(PageDictionary)`

Array of property values for this page, indexed by property name.

#### Example
```php
if($page->properties->showTitle != 'N'){
  echo '<title>'.htmlspecialchars($page->title).'</title>';
}
```

If a property is not defined, its value will be an empty string.

---

### Page->isInEditor

`(bool)`

True if the page was detected to have been opened by the CMS.

---

### Page->editorScript

`(string)`

If the page was opened by the CMS, the script tag used to launch the CMS Editor.  Otherwise, an empty string.

---

### Page->notFound

`(bool)`

Set to true by Router->getStandalone if no matching content was found when the page content was supposed to be rendered (when not in CMS Editor mode).

---

## Public Methods

---

### Page::fromArray
`Page::fromArray($data)`

Generate a Page object from JSON data
#### Parameters
* `$data (array)` CMS JSON Page Content
#### Returns
`(Page)` Page
#### Example
```php
$page = Page::fromArray([
  'title'=>'Welcome',
  'content'=>['body'=>'Hello World']
]);
```

---

## *apHarmony\jsHarmonyCms\Response Class*

---

## Response Constructor

```php
new Response($type)
```

#### Arguments

- `$type (string)` Type of the response - 'page' or 'redirect'

#### Example
```php
$response = new Response('page');
```
---

## Public Properties

---

### Response->type

`(string|null)`

Type of the response - 'page' or 'redirect'

---

### Response->filename

`(string|null)`

Page filename, if response type is "page"

---

### Response->redirect

`(Redirect|null)`

Redirect, if response type is "redirect"

---

## *apHarmony\jsHarmonyCms\PassthruResponse Class*

---

## Public Properties

---

### PassthruResponse->http_code

`(int|null)`

The HTTP response code from the target passthru page

---

### PassthruResponse->content_type

`(string|null)`

The HTTP content type from the target passthru page

---

### PassthruResponse->content

`(string|null)`

The HTML content from the target passthru page

---

## Public Methods

---

### PassthruResponse->serve
`PassthruResponse->serve()`

Serve the passthru content to the user
#### Parameters
N/A
#### Example
```php
$cmsRouter->passthru('https://example.com')->serve();
```

---

## *apHarmony\jsHarmonyCms\Redirect Class*

---

### Redirect Constructor

```php
new Redirect($http_code, $url)
```

#### Arguments

- `$http_code (string)` HTTP Code ('301', '302' or 'PASSTHRU')

- `$url (string)` Destination URL

#### Example
```php
$redirect = new Redirect('301', 'https://example.com');
```

---

## Public Properties

---

### Redirect->http_code

`(string|null)`

HTTP Code ('301', '302' or 'PASSTHRU')

---

### Redirect->url

`(string|null)`

Destination URL
