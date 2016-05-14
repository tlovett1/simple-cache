=== Simple Cache ===
Contributors: tlovett1
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HR34W94MM53RQ
Tags: cache, page cache, object caching, object cache, memcache, redis, memcached
Requires at least: 3.9
Tested up to: 4.6
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


