# MySQL-backed PHP Sessions in WordPress

wpdb-phpsessions is a [PHP sessions](http://php.net/manual/en/book.session.php) backend that uses the MySQL database (via [`$wpdb`](https://codex.wordpress.org/Class_Reference/wpdb)) for storage and management.

## Why is this even needed?

In an endeavor to speed WordPress sites up we decided to move all PHP sessions into the database. Check out our extensive [PHP Sessions Can Hurt Your WordPress Performance](https://pressjitsu.com/blog/wordpress-sessions-performance/) article for more information and caveats.

## Installation

Drop `wpdb-phpsessions.php` into your wp-content/plugins/ or wp-content/mu-plugins/ directory and enjoy.

## License

wpdb-phpsessions Copyright (c) 2016 Pressjitsu // licensed under the GPLv3 (https://www.gnu.org/licenses/gpl-3.0.txt)
