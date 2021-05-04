=== Simple Cache ===
Contributors: tlovett1
Tags: cache, page cache, object caching, object cache, memcache, redis, memcached
Requires at least: 3.9
Tested up to: 5.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A very simple plugin to make your site run lightning fast with caching.

== Description ==

Simple Cache was constructed after getting frustrated with the major caching plugins available and building sites with developer-only complex caching solutions that get millions of page views per day. Simple Cache promises the following:

* Extremely simple one-click install. There is an on-off switch. That's it. No need to wade through 50 complicated settings.
* Simple Cache makes your site run very fast so you can handle lots of traffic.
* Extremely easy to delete. Don't like the plugin? You can remove it, and your website won't break.
* Easily clear the cache if you need to.
* Enable gzip compression
* Want to get advanced with object caching (Memached or Redis)? An advanced mode is available that will automatically setup [Batcache](https://wordpress.org/plugins/batcache/) and [Memcached](https://wordpress.org/plugins/memcached/)/[Redis](https://wordpress.org/plugins/wp-redis/) for you.

If you need your site to run fast, don't have time to mess with complicated settings, and have been frustrated by other caching plugins, give Simple Cache a try.

Pull requests are welcome on [Github](https://github.com/tlovett1/simple-cache).

== Installation ==

1. Install the plugin through your dashboard.
2. Navigate to Settings > Simple Cache. Turn caching on.
3. Done.

== Support ==

For full documentation, questions, feature requests, and support concerning the Simple Cache plugin, please refer to [Github](http://github.com/tlovett1/simple-cache).

== Changelog ==

Version 2.0.0 of Simple Cache comes with some useful new features and bug fixes. This release was
created by [Luis Herranz](https://github.com/luisherranz) and the [Frontity](https://frontity.org/) framework.

= 2.0.0 =

Breaking Changes:
* The REST API is not cached anymore by default

New Features:
* Cache REST API setting added in advanced mode.
* Restore headers setting added to advanced mode. This will tell Simple Cache to cache headers and return the
same headers when a cache hit occurs.
* New X-Simple-Cache header. HIT/MISS depending on if a cache match is found.

Bug Fixes:
* Don't flush the cache when the user edits a draft post

= 1.7.4 =
* Fix WP_PLUGIN_DIR constant

= 1.7.3 =
* Use WP_PLUGIN_DIR constant

= 1.7.2 =
* Add try/catch to redis connect
* Add sc_get_cache_path function
* Fix cache flush path
* Fix purge intervals
* Improve error messages
* Improved string encoding for better compatibility with other languages

= 1.7.1 =
* Fix cache directory write check
* Fix cache directory write error notice

= 1.7 =
* Remove `WP_Filesystem` usage
* Add support for object cache dropin using Memcached extension
* Add optional constants for setting the config and caching directories
* Support network-wide installs. Add multisite settings page
* Support Memcached PHP extension

= 1.6.4 =
* Fix undefined `$blog_id` warning in redis object cache.
* Prevent mixed content. Props [benoitchantre](https://github.com/benoitchantre).
* Added WP_CACHE_KEY_SALT constant with a random value. Props [gagan0123](https://github.com/gagan0123 ).

= 1.6.3 =
* Fix missing `FS_CHMOD_DIR` and `FS_CHMOD_MODE` in `sc_cache()`. Props [chesio](https://github.com/chesio)
* Make form labels properly clickable. Props [ranss](https://github.com/ranss)
* Only show notices to admins. Props [psorensen](https://github.com/psorensen)

= 1.6.2 =
* Prevent fatal when commenting with Captcha.

= 1.6.1 =
* Fix comment update warnings

= 1.6 =
* Add wildcards and regex to cache exemptions

= 1.5.6 =
* Prevent gzipping on 404 or non-cached pages

= 1.5.5 =
* __Fix Memcache cache purging with the following:__
* Allow Memcache flushing in multisite
* Include object cache within the admin
* Only batcache when a URL is accessed 2 times

= 1.5.4 =
* Fix comment status PHP notice
* Only let admins see purge cache button

= 1.5.3 =
* Fix non-HTML (JSON) file caching.

= 1.5.2 =
* Fix password protected posts

= 1.5.1 =
* Only create gzip file cache file if necessary and vice-versa
* Don't check exceptions in simple mode

= 1.5 =
* Add page cache URL exemptions in advanced mode
* Improve messaging around object caching extensions

= 1.4.2 =
* Properly verify files can be created/edited. Props [davetgreen](https://github.com/davetgreen)

= 1.4.1 =
* Make sure file.php is included on purging. Props [sagliksever](https://github.com/sagliksever)
* Make sure we don't cache wp-login.php and other php files.
* Reschedule cron when settings are saved
* Properly output HTTP caching headers
* Make HTML cache comment more human readable.
* Fix rtrim file path bug
* Properly clear file cache on cron
* *(Important)* Purge all cache when a post is updated/created. This makes sure you don't show a stale blog index when content is created/updated.
* Make sure config file doesnt balloon

= 1.4 =
* Properly purge cache when comments are created/approved.
* Make sure user gets non cached pages after commenting
* Fix bug causing php files with get params to be cached
* Properly check WordPress logged in cookies

= 1.3 =
* Purge single view cache for all post types when they are updated/deleted
* Admin bar button for purging the cache

= 1.2.5 =
* Support wp-config.php one directory below WP.

= 1.2.4 =
* Fix config file getting erased in Windows
* Add developer function sc_cache_flush() for clearing the cache

= 1.2.3 =
* Properly check needed file permissions
* Sane default cache expiration time

= 1.2.2 =
* On uninstall delete all related plugin files including config.
* Turn off object caching in admin
* Update notices
* Dont show in memory options that aren't available

= 1.2.1 =
* Fix advanced dropdown bug

= 1.2 =
* Support gzip compression
* Fix bug when config variable doesn't exist
* Fix advanced tab toggling JS error
* Fix bug where in memory cache setting was sticking in simple mode

= 1.1 =
* Add page cache length field
* Code formatting

= 1.0.3 =
* Fix error messaging around non-writeable file system
* Fix redis

= 1.0.2 =
* Properly clean up files
* Don't break if advanced-cache.php or object-cache.php is gone.

= 1.0.1 =
* Boolval bug fixed

= 1.0 =
* Plugin released


