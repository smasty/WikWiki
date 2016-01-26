# WikWiki - simple wiki in one PHP file.

Copyright (c) 2015 Martin Srank, http://smasty.net

Licensed under the terms and conditions of
the MIT License (http://www.opensource.org/licenses/mit-license.php)

## Features

- Advanced formattng options (using Texy! syntax)
- Backlinks support
- Provides list of recent changes
- Custom 404 error - Head over to Special:NotFound and edit the page.
                     Use %PAGE% as a placeholer for name of not found page.


## Installation

No special installation steps are required. Just copy the source file (index.php),
the Texy! formatter file (texy.min.php) and the stylesheet (style.css) to your
desired folder.

Make sure that PHP has write access to the folder. WikWiki will try to create
a data folder automatically. If it fails, you'll be asked to create it manually.
