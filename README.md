# zzbrick
template system for webpages

'brick' is a kind of a template language that is intended to allow easily
combination of html, text and placeholder blocks in content management systems.  
'brick' will format all text with markdown and replace the placeholder blocks
with real content depending on which type they are.

Part of [»Zugzwang Project«](https://www.zugzwang.org/)

## How zzbrick works

`$page = brick_format($block, $parameters);`

brick_format() formats the text in $block and returns an array:

- `'text'` = page text
- `'title'` = page title
- `'status'` = (optional) HTTP status code
- etc., see more in brick_format() function comments

## Block syntax

Inside a text, placeholders for the real content are used:

`%%% request news 2004 %%%`

- `request` is the type of the module  
	 the module file request.inc.php knows what to do with this placeholder
- `news` is the function of the module
   zzbrick_request/news.inc.php will be included with a corresponding 
   function cms_news(); which will be called, other functions which are being
   accessed through cms_news() must be in the same file, in files starting with
   zzbrick_request/news_
- `2004` and further variables, separated by spaces, are variables which are
   being passed to the function. variables must not include `"`, since this character
  is used to allow the use of whitespace in single variables, e. g. `"2004 fall"`

## Modules

### Integrated modules

- `loop` - will do a loop and repeat parts of the brick  
  `%%% loop start "optional HTML if content" "optional HTML if no content" %%%`  
  […]  
  `%%% loop end "optional HTML if content" %%%`  
- »subloops«:  
  `%%% loop subcategory %%%`  
  […]  
  `%%% loop end "optional HTML if content" %%%`
- only part of the items  
  `%%% loop start 2- %%%` e. g. 1, 2-4, -5
	
### Further modules

- `comment` – comment blocks, won't be displayed
- `condition` – shows content depending on condition
- `count` – adds current count of item inside a loop
- `explain` – allows to explain zzbrick syntax in content
- `forms` – includes forms from zzform module
- `ipfilter` – shows content only if client is in preset IP range
- `item` – gets item from array
- `language` – shows content dependent on language
- `link` – will not link to self if link url = current url
- `loopposition` – add string depending on position in loop
- `page` – add page element, from custom function
- `position` – sets or changes position of text block in a predefined matrix
- `redirect` – redirects to another URL
- `request` – add output of request function whith URL parameters; returns content in a $page-array
- `rights` – shows content depending on the result of a custom function which checks access rights
- `setting` – add string value of corresponding setting
- `template` – add content of template
- `templatefields` – show all items available in template
- `text` – translates text blocks

## Settings

- `lang`: language code for HTML lang attribute of HEAD
- more settings can be found in file `configuration/settings.cfg`

## Default file structure

- zzbrick_rights/access_rights.inc.php
- zzbrick_request/{request}.inc.php
- zzbrick_forms/{tables}.php
- zzbrick_tables/{tables}.php

## Functions

Functions that contain customisations

- wrap_access_rights() - returns true if access is granted or false if no
	access is granted
- cms_{request}() - returns $page-Array as defined in brick_format()
