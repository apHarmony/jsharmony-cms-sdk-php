# jsharmony-cms-sdk-php
jsHarmony CMS SDK for PHP

## Installation

Installation and integration instructions are available at [jsHarmonyCMS.com](https://www.jsharmonycms.com/resources/integrations/php/)

## API Documentation

### *apHarmony\jsHarmonyCms\CmsRouter Class*

* [Constructor](#cmsrouter-constructor)
* *Public Properties*
   * [config](#cmsrouter-config)
* *Public Methods*
   * [serve](#cmsrouter-serve)
   * [getStandalone](#cmsrouter-getstandalone)
   * [isInEditor](#cmsrouter-isineditor)
   * [resolve](#cmsrouter-resolve)
   * [route](#cmsrouter-route)
   * [matchRedirect](#cmsrouter-matchredirect)
   * [getRedirectData](#cmsrouter-getredirectdata)
   * [getEditorScript](#cmsrouter-geteditorscript)
   * [serveFile](#cmsrouter-servefile)
   * [redirect301](#cmsrouter-redirect301)
   * [redirect302](#cmsrouter-redirect302)
   * [passthru](#cmsrouter-passthru)
   * [generate404](#cmsrouter-generate404)
   * [generateError](#cmsrouter-generateerror)
   * [getPage](#cmsrouter-getpage)
   * [getPageFromFile](#cmsrouter-getpagefromfile)
   * [getPageFileName](#cmsrouter-getpagefileName)
   * [getFile](#cmsrouter-getfile)
   * [getJsonFile](#cmsrouter-getjsonfile)


### *apHarmony\jsHarmonyCms\CmsPage Class*

* *Public Properties*
   * [seo](#cmspage-seo)
       * [title](#cmspage-seo-title)
       * [keywords](#cmspage-seo-keywords)
       * [metadesc](#cmspage-seo-metadesc)
       * [canonical_url](#cmspage-seo-canonical_url)
   * [css](#cmspage-css)
   * [js](#cmspage-js)
   * [header](#cmspage-header)
   * [footer](#cmspage-footer)
   * [title](#cmspage-title)
   * [page_template_id](#cmspage-page_template_id)
   * [content](#cmspage-content)
   * [properties](#cmspage-properties)
   * [isInEditor](#cmspage-isineditor)
   * [editorScript](#cmspage-editorscript)
   * [notFound](#cmspage-notfound)
* *Public Methods*
   * [fromArray](#cmspagefromarray)

### *apHarmony\jsHarmonyCms\CmsResponse Class*

* [Constructor](#cmsresponse-constructor)
* *Public Properties*
   * [type](#cmsresponse-type)
   * [filename](#cmsresponse-filename)
   * [redirect](#cmsresponse-redirect)


### *apHarmony\jsHarmonyCms\CmsPassthruResponse Class*

* *Public Properties*
   * [http_code](#cmspassthruresponse-http_code)
   * [content_type](#cmspassthruresponse-content_type)
   * [content](#cmspassthruresponse-content)
* *Public Methods*
   * [serve](#cmspassthruresponse-serve)


### *apHarmony\jsHarmonyCms\CmsRedirect Class*

* [Constructor](#cmsredirect-constructor)
* *Public Properties*
   * [http_code](#cmsredirect-http_code)
   * [url](#cmsredirect-url)

### *jsHarmonyCmsEditor Class* (Client JS)

* [Constructor](#jsharmonycmseditor-constructor)


---

## *apHarmony\jsHarmonyCms\CmsRouter Class*

---

## CmsRouter Constructor

```php
new CmsRouter($config)
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
$cmsRouter = new CmsRouter([ 'cms_server_urls' => ['https://cms.example.com'] ]);
```

---

## Public Properties

---

### CmsRouter->config
`(array)`

An associative array with the CmsRouter's config.  Config parameters are defined in the Constructor above.
```php
$cmsRouter->config['passthru_timeout'] = 60;
```

---

## Public Methods

---

### CmsRouter->serve
`CmsRouter->serve(?string $url = null, array $options = [])`

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

### CmsRouter->getStandalone
`CmsRouter->getStandalone(?string $url = null)`

*Main Entry Point* - Get CMS Page Data for Standalone Integration
#### Parameters:
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(CmsPage)` Page Content

If page is opened from CMS Editor or Not Found, an empty CmsPage Object will be returned
#### Example
```php
$cmsRouter->getStandalone();
```

---

### CmsRouter->isInEditor
`CmsRouter->isInEditor(?string $url = null)`

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

### CmsRouter->resolve
`CmsRouter->resolve(?string $url = null, array $options = [])`

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

### CmsRouter->route
`CmsRouter->route(?string $url = null)`

Run CMS router on the target URL
#### Parameters
* `$url (string|null)` *(Optional)* CMS Page URL

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(CmsResponse|null)` Response with Page Filename, Redirect, or null if not found
#### Example
```php
$response = $cmsRouter->route($targetUrl);
```

---

### CmsRouter->matchRedirect
`CmsRouter->matchRedirect(?array $redirects, ?string $url)`

Check if URL matches redirects and return first match
#### Parameters
* `redirects: (array|null)` Array of CMS Redirects (from getRedirectData function)
* `url: (string|null)` Target URL to match against the CMS Redirects

   Use Full URL, Root-relative URL, or leave blank to use current URL
#### Returns
`(CmsRedirect|null)` Redirect
#### Example
```php
$redirect = $cmsRouter->matchRedirect($cmsRedirects);
if($redirect && ($redirect->http_code=='301')){
  $cmsRouter->redirect301($redirect->url);
}
```

---

### CmsRouter->getRedirectData
`CmsRouter->getRedirectData()`

Get CMS Redirect Data

Requires `config['redirect_listing_path']` to be defined
#### Returns
`array|null` JSON array of CMS redirects
#### Example
```php
$cmsRedirects = $cmsRouter->getRedirectData();
```

---

### CmsRouter->getEditorScript
`CmsRouter->getEditorScript(?string $url = null)`

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

### CmsRouter->serveFile
`CmsRouter->serveFile($filePath)`

Serves a file to the user
#### Parameters
* `$filePath (string)` Path to target file

#### Example
```php
$pageFilename = $cmsRouter->getPageFileName();
$cmsRouter->serveFile($pageFilename);
```

---

### CmsRouter->redirect301
`CmsRouter->redirect301(string $url)`

Perform 301 Redirect
#### Parameters
* `$url (string)` Target URL for Redirect

#### Example
```php
$cmsRouter->redirect301('https://example.com');
```

---

### CmsRouter->redirect302
`CmsRouter->redirect302(string $url)`

Perform 302 Redirect
#### Parameters
* `$url (string)` Target URL for Redirect

#### Example
```php
$cmsRouter->redirect302('https://example.com');
```

---

### CmsRouter->passthru
`CmsRouter->passthru(string $url)`

Perform Passthru Request
#### Parameters
* `$url (string)` Target URL for Passthru Redirect

#### Returns
`(CmsPassthruResponse)` Response

Call the CmsPassthruResponse->serve() method to serve the page

#### Example
```php
$cmsRouter->passthru('https://example.com')->serve();
```

---

### CmsRouter->generate404
`CmsRouter->generate404()`

Generate a 404 Not Found Page
#### Parameters
N/A

#### Example
```php
$cmsRouter->generate404();
```

---

### CmsRouter->generateError
`CmsRouter->generateError($err)`

Generate a 500 Error Page
#### Parameters
* `$err (Exception|string)` Error message

#### Example
```php
$cmsRouter->generateError('An unexpected error has occurred.');
```

---

### CmsRouter->getPage
`CmsRouter->getPage(?string $url = null, array $options = [])`

Get CMS Page from URL
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
`(CmsPage|null)` Page data, or null if page was not found
#### Example
```php
$page = $cmsRouter->getPage($targetUrl);
```

---

### CmsRouter->getPageFromFile
`CmsRouter->getPageFromFile(string $filePath)`

Get CMS Page from file path
#### Parameters
* `$filePath (string)` Path to target file
#### Returns
`(CmsPage|null)` Page data, or null if page was not found or an error occurred
#### Example
```php
$page = $cmsRouter->getPageFromFile($filePath);
```

---

### CmsRouter->getPageFileName
`CmsRouter->getPageFileName(?string $url = null, array $options = [])`

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

`CmsPageNotFoundException` exception is thrown if page is not found
#### Example
```php
$pageFile = $cmsRouter->getPageFile($targetUrl);
```

----

### CmsRouter->getFile
`CmsRouter->getFile($filePath)`

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

### CmsRouter->getJsonFile
`CmsRouter->getJsonFile($filePath)`

Reads and parses a JSON file
#### Parameters
* `$filePath (string)` Path to target file

#### Returns
`(array|null)` JSON content, or null if file was not found or an error occurred

#### Example
```php
$pageFilename = $cmsRouter->getPageFileName();
$pageData = $cmsRouter->getJsonFile($pageFilename);
```

---

## *apHarmony\jsHarmonyCms\CmsPage Class*

---

## Public Properties

---

### CmsPage->seo

`(CmsPageSeo)`

A class instance with the SEO-related properties for a page

---

### CmsPage->seo->title

`(string)`

Page content for the header `<title>` tag

---

### CmsPage->seo->keywords

`(string)`

Page content for the header `<meta name="keywords" content="..." />` tag

---

### CmsPage->seo->metadesc

`(string)`

Page content for the header `<meta name="description" content="..." />` tag

---

### CmsPage->seo->canonical_url

`(string)`

Page content for the header `<link rel="canonical" href="..." />` tag

---

### CmsPage->css

`(string)`

Page content for a header `<style type="text/css">` tag

---

### CmsPage->js

`(string)`

Page content for a header `<script type="javascript">` tag

---

### CmsPage->header

`(string)`

HTML content to be appended to the `<head>` tag

---

### CmsPage->footer

`(string)`

HTML content to be appended to the end of the `<body>` tag

---

### CmsPage->title

`(string)`

HTML content to be added to an `<h1>` tag in the body

---

### CmsPage->page_template_id

`(string)`

Name of the CMS page template used by this page

---

### CmsPage->content

`(CmsPageDictionary)`

Array of content for this page, indexed by Content Element ID.

#### Example
```php
echo $page->content->body;
echo $page->content->sidebar;
```

If a content area is not defined, its value will be an empty string.

---

### CmsPage->properties

`(CmsPageDictionary)`

Array of property values for this page, indexed by property name.

#### Example
```php
if($page->properties->showTitle != 'N'){
  echo '<title>'.htmlspecialchars($page->title).'</title>';
}
```

If a property is not defined, its value will be an empty string.

---

### CmsPage->isInEditor

`(bool)`

True if the page was detected to have been opened by the CMS.

---

### CmsPage->editorScript

`(string)`

If the page was opened by the CMS, the script tag used to launch the CMS Editor.  Otherwise, an empty string.

---

### CmsPage->notFound

`(bool)`

Set to true by CmsRouter->getStandalone if no matching content was found when the page content was supposed to be rendered (when not in CMS Editor mode).

---

## Public Methods

---

### CmsPage::fromArray
`CmsPage::fromArray($data)`

Generate a CmsPage object from JSON data
#### Parameters
* `$data (array)` CMS JSON Page Content
#### Returns
`(CmsPage)` Page
#### Example
```php
$page = CmsPage::fromArray([
  'title'=>'Welcome',
  'content'=>['body'=>'Hello World']
]);
```

---

## *apHarmony\jsHarmonyCms\CmsResponse Class*

---

## CmsResponse Constructor

```php
new CmsResponse($type)
```

#### Arguments

- `$type (string)` Type of the response - 'page' or 'redirect'

#### Example
```php
$response = new CmsResponse('page');
```
---

## Public Properties

---

### CmsResponse->type

`(string|null)`

Type of the response - 'page' or 'redirect'

---

### CmsResponse->filename

`(string|null)`

Page filename, if response type is "page"

---

### CmsResponse->redirect

`(CmsRedirect|null)`

Redirect, if response type is "redirect"

---

## *apHarmony\jsHarmonyCms\CmsPassthruResponse Class*

---

## Public Properties

---

### CmsPassthruResponse->http_code

`(int|null)`

The HTTP response code from the target passthru page

---

### CmsPassthruResponse->content_type

`(string|null)`

The HTTP content type from the target passthru page

---

### CmsPassthruResponse->content

`(string|null)`

The HTML content from the target passthru page

---

## Public Methods

---

### CmsPassthruResponse->serve
`CmsPassthruResponse->serve()`

Serve the passthru content to the user
#### Parameters
N/A
#### Example
```php
$cmsRouter->passthru('https://example.com')->serve();
```

---

## *apHarmony\jsHarmonyCms\CmsRedirect Class*

---

### CmsRedirect Constructor

```php
new CmsRedirect($http_code, $url)
```

#### Arguments

- `$http_code (string)` HTTP Code ('301', '302' or 'PASSTHRU')

- `$url (string)` Destination URL

#### Example
```php
$redirect = new CmsRedirect('301', 'https://example.com');
```

---

## Public Properties

---

### CmsRedirect->http_code

`(string|null)`

HTTP Code ('301', '302' or 'PASSTHRU')

---

### CmsRedirect->url

`(string|null)`

Destination URL

---

## *jsHarmonyCmsEditor Class (Client JS)*

---

### jsHarmonyCmsEditor Constructor

```js
jsHarmonyCmsEditor(config)
```

#### Arguments

- `config` (Object) :: Object with one or more of the configuration keys below:
```js
{
  access_keys: [],
  //Array(string) CMS Editor Access Keys, used to validate remote CMS URL
}
```

#### Example
```js
//Load the CMS Editor in this page
jsHarmonyCmsEditor({ access_keys: ['*****ACCESS_KEY*****'] });
```
