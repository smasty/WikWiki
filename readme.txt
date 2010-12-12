Inspired by ShortestWikiContest(1), I've created WikWiki from scratch.
It's 1287 characters long (17 lines) and written in PHP. WikiPrinciples(2) are obeyed.

Working demo: http://labs.smasty.net/WikWiki
Highlighted & formatted source: http://labs.smasty.net/WikWiki/code.html

Features
========

- AutomaticLinkGeneration(3) - Same behaviour as on c2.com/wiki
- EasyTextInput(4)
  - Four or more dash characters create horizontal rule
  - Dash on a new line followed by space creates list item
  - New line creates <br> tag
  - URLs starting by http(s) are converted to links
  - Space indented line is monospaced
- BackLinks(5) support

Installation
============
No special installation is required, just copy the source.

----------------------------------------------------------------------------------------
(1) http://c2.com/cgi/wiki?ShortestWikiContest
(2) http://c2.com/cgi/wiki?WikiPrinciples
(3) http://c2.com/cgi/wiki?AutomaticLinkGeneration
(4) http://c2.com/cgi/wiki?EasyTextInput
(5) http://c2.com/cgi/wiki?BackLink