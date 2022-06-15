# irys-tools ([tools.irys.cc](https://tools.irys.cc))

Miscellaneous server-side tools that don't fit anywhere else.

Primarily documented in `public/index.html`,
such that the documentation will render
when browsing to the site.

## Setting up on a new host

Clone this repository to somewhere the web server can access,
point the web server at the `public/` subdirectory of the repo.

### Apache

If you're using Apache - you're good to go,
there's a `.htaccess` present with the rewrite rules.

### nginx

Here's the rewrites:

```nginx
rewrite ^/pkavi/([msg])/(.*)(?:\.[a-z]+)?$ /pkavi/index.php?ty=$1&id=$2&$args last;
rewrite ^/pkavi/stats(?:\.json)?$ /pkavi/cache.php?$args last;
```

## License

Public domain, CC0, whatever you wish to call it.

Some files in `includes/` may be under different licenses,
see the header comments in those files for details.
