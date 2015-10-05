Content Redirects for Bolt
==========================

This extension provides redirects for content items from an arbitrary number of
source paths.

## Background

When migrating from an old site to Bolt, you will likely want the new site to
redirect old URL paths to current ones, so that existing links to specific pages
keep working. Same thing if you change the slug of a content item for example.

[BoltRedirector](https://github.com/foundrycode/boltredirector) provides an
existing solution for this where you put your redirects in a YML file. You can
even use smart patterns to avoid repeating yourself.

But if you're migrating, say, thousands of articles that have URL paths that you
can't generalize into a consistent pattern, the YML file becomes too limiting.
(It's hard to manage, and also too heavy to parse on each page load.)

## How it works

This extension looks at each request and checks if the current path exists in
the `bolt_content_redirect` table. If so, it redirects to the specified article.
(Beware: this adds an extra SQL query to every request, always. The exception is
the front page and anything beneith `/bolt`.)

Redirects have four properties: `source` (`"/old/path"`), `content_type`
(`"article"`), `id` (`1`), and `"code"` (`301` or `302`).

`code` represents the HTTP status code that is used for the redirect. This
may be `null`, in which case the configured default value is used.

There is no user interface for managing these redirects – they are meant to be
created by your migration script. This would be a welcome addition however!

## Configuration

`status_code`: The HTTP status code to use for the redirects – either 301 or
302.

## Which HTTP status code should I use?

301 redirects are considered permanent and are potentially cached indefinitely
by your users' browsers and by proxy servers. 302 redirects are considered
temporary and are not cached.

With this in mind, it is a good idea to use 302 while developing and testing
things out. Otherwise, chances are you will end up with cached redirects that
are wrong, and there is no way to uncache them on behalf of your users. When the
site has gone live and is stable, 301 redirects are usually better, since they
will reduce the amount of work your server has to do to serve the redirects.
