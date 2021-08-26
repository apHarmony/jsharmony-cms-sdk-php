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

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PassthruResponse {
  /** @var int|null */
  public $http_code = null;

  /** @var string|null */
  public $content_type = null;

  /** @var string|null */
  public $content = null;

  public function serve(){
    if($this->http_code) http_response_code($this->http_code);
    if($this->content_type) header('Content-Type: '.$this->content_type);
    echo $this->content??'';
  }
}