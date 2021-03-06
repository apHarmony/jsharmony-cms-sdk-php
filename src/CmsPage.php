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

use Exception;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CmsPage {

  /** @var CmsPageSeo */
  public $seo = null;

  /** @var string */
  public $css = '';

  /** @var string */
  public $js = '';

  /** @var string */
  public $header = '';

  /** @var string */
  public $footer = '';

  /** @var string */
  public $title = '';

  /** @var string */
  public $page_template_id = null;

  /** @var CmsPageDictionary */
  public $content = null;

  /** @var CmsPageDictionary */
  public $properties = null;

  /** @var bool */
  public $isInEditor = false;

  /** @var string */
  public $editorScript = '';

  /** @var bool */
  public $notFound = false;

  public static function fromArray($data){
    $page = new CmsPage();
    if(isset($data) && is_array($data)){
      foreach(['css','js','header','footer','title','page_template_id'] as $key){
        if(isset($data[$key])) $page->$key = $data[$key];
      }
      if(isset($data['seo']) && is_array($data['seo'])){
        foreach(['title','keywords','metadesc','canonical_url'] as $key){
          if(isset($data['seo'][$key])) $page->seo->$key = $data['seo'][$key];
        }
      }
      if(isset($data['content']) && is_array($data['content'])){
        foreach($data['content'] as $key=>$val){
          $page->content->$key = $val;
        }
      }
      if(isset($data['properties']) && is_array($data['properties'])){
        foreach($data['properties'] as $key=>$val){
          $page->properties->$key = $val;
        }
      }
    }
    return $page;
  }

  public function __construct(){
    $this->seo = new CmsPageSeo();
    $this->content = new CmsPageDictionary();
    $this->properties = new CmsPageDictionary();
  }
}

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CmsPageSeo {
  /** @var string */
  public $title = '';

  /** @var string */
  public $keywords = '';

  /** @var string */
  public $metadesc = '';

  /** @var string */
  public $canonical_url = '';
}

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CmsPageDictionary {
  public function __get($name){ return $this->__dictionary[$name] ?? ''; }
  public function __set($name, $val){ $this->__dictionary[$name] = $val; }
  public function __call($name, $args){
    if(count($args)==0) return $this->__dictionary[$name] ?? '';
    else if(count($args)==1) $this->__dictionary[$name] = $args[0];
    else throw new Exception('Invalid Arguments');
  }

  /** @var array */
  private $__dictionary = [];
}