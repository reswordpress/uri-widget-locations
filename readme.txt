=== URI Archive ===

Contributors: John Pennypacker
Tags: widgets
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0

Control where widgets display by URL.

== Description ==

Adds a field for URLs to each widget.  Widgets then only display when the URL of the page matches the URL of the widget.

To match the front page, use a special keyword:
<front>

Otherwise, enter the URL that the widget should be displayed on.  Like so:
/article/foo-bar

The widget accepts as many URLs to match as you'd like, listed on new lines.  e.g.
/article/foo-bar
/article/foo-bar-baz
/article/foo

Asterisks can be used as wild card characters.  For example, to display a widget on all URLs that begin with /article use:
/article/*

It's also possible to exclude URLs. If you'd like to display the widget on articles, but not on the article index/category/archive pages, you can prefix the pattern with an exclamation point:
/article/*
!/article/page/*

Note: the exclusion logic is performed *after* the matching logic.  