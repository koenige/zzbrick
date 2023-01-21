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
   zzbrick_request/news_ or in the zzbrick_request/_common.inc.php which will 
   always be included
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

- `brick_custom_dir`: directory for the customised brick scripts, zzwrap
sets this to a default in $zz_setting['custom'], prefix zzbrick_
- `brick_default_position`: if a matrix of the content is wanted, here you 
can define a default position
- `brick_types_translated`: here you can translate the first part of the
zzbrick definition e. g. `%%% abfrage ... ... %%%` might be translated to
request: `$setting['brick_types_translated']['abfrage'] = 'request'`
this may also be used to define a certain subtype
- `brick_request_shortcuts`: shortcuts, that is you can write `%%% image blubb 
%%%` instead of `%%% request image blubb %%%`
- `brick_username_in_session`: Name of key from $_SESSION that will be used  
as username for zzform(), default is 'username'
- `brick_authentication_file`: file to include if
authentication is needed for accessing the zzform scripts. might be 
false, then no file will be included. zzwrap sets this automatically
- `brick_authentication_function`: function to call if
authentication is needed
- `brick_translate_text_function`: Name of function to translate text; 
zzwrap sets this to wrap_text
- `brick_fulltextformat`: name of function to format the complete output of
brick_format instead of formatting each paragraph separately with 
markdown
- `brick_ipfilter_translated`: similar to brick_types_translated, here
you can translate '=', ':' and '-' to different text
- `brick_ipv4_allowed_range`: standard allowed range of IP adresses if
no address is set in ipfilter
- `brick_rights_translated`: similar to brick_types_translated, here
you can translate '=', ':' and '-' to different text
- `lang`: language code for HTML lang attribute of HEAD

## Default file structure

- zzbrick_rights/access_rights.inc.php
- zzbrick_request/{request}.inc.php
- zzbrick_request/_common.inc.php
- zzbrick_forms/{tables}.inc.php
- zzbrick_tables/{tables}.inc.php

## Functions

Functions that contain customisations

- wrap_access_rights() - returns true if access is granted or false if no
	access is granted
- cms_{request}() - returns $page-Array as defined in brick_format()
