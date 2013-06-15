==========
 Tinytape 
==========

---------
 Notice!
---------

Tinytape is currently defunct. I let the tinytape.com domain lapse, but still
own tinyta.pe. The site will not return as a PHP application.

If I ever bring Tinytape back, it will be rewritten in Python or Go and use
Redis as its exclusive data store.

All API tokens and secrets included in the repo have been deactivated, and the
database no longer exists.

------
 v4.0 
------

To install Tinytape to a server, it must already be running a copy of
Interchange that's properly configured. This repository should then be overlaid
up on the Interchange root (the directory specified by `IXG_PATH_PREFIX`).

Setting up the DBs
==================

This is some good times.

#. Open `libraries/prefixes.php`
#. Modify the Cloud database instantiation to match your MySQL information
#. Open `libraries/redis.php`
#. Modify the PHPRedis client initialization to match your Redis configuration
#. Open `libraries/sphinx.php`
#. Modify the Sphinx client initialization to match your Sphinx configuration

MySQL
-----

To set up your MySQL instance, simply set up a new database (call it `tinytape`
for the sake of simplicity). Then, run `setup/tinytape.sql` to create the
tables.

Note that in order to get to the admin stuff, you'll need a user with admin
priviliges, so sign up for an account and then manually promote the user to
admin status by changing the `admin` column for their record to `1`. Log out
and log back in for the changes to take effect.

Redis
-----

So if you've ever used Redis before, you know that it can be pretty finnicky
when it comes to not faceplanting. Memory is a huge issue: too little memory or
disk space, especially in non-AOF configurations, will result in a complete
loss of all data up until the last time Redis was able to build your dump.

The best configuration for Tinytape is append-only. Create a CRON job to run
every night (or every few hours if you're paranoid) to rewrite the AOF and
push it to S3 for backup.

Otherwise, any old Redis installation will do. Tinytape expects a password (the
`auth` command), and the password should be stored at `/var/auth/redis`.

Sphinx
------

Set up Sphinx to index MySQL. Beyond loading in a config file, there's really
no other configuration necessary.

Database-side
~~~~~~~~~~~~~

On Debian, Sphinx requires the "libmysqlclient15-dev" package to be installed.
On other platforms, this may be called "mysql-devel".

To perform the Sphinx indexing operation, a cron job should be created. A good
default would be something like this:

::

	*   *   *   *   *   /usr/local/sphinx/bin/indexer --quiet --config /usr/local/sphinx/etc/sphinx.conf --rotate tinytape1

A sample configuration file can be found in `setup/sphinx.conf` and should be
reviewed and placed in `/usr/local/sphinx/etc/`. A data directory for the
Sphinx index should be placed at `/usr/local/sphinx/data/tinytape`.

Sphinx's searchd daemon needs to be running for search to work. To create the
most basic kind of init.d script, simply save the following to
`/etc/init.d/sphinx` (don't forget to `chmod +x /etc/init.d/sphinx`):

::

	cd /usr/local/sphinx/etc
	/usr/local/sphinx/bin/searchd


Web-server side
~~~~~~~~~~~~~~~

libsphinxclient must be installed on the web server before the PECL sphinx
package can be installed. This is bundled in the Sphinx tarball under
`api/libsphinxclient`. Depending on the version of Sphinx, some tweaking to
`sphinxclient.c` may be required.


Prerequirements
===============

Twitter
-------

To enable Twitter support, you need to have PCRE installed: ::

    apt-get install libpcre3-dev

PECL
----

There are a bunch of PECL extensions you should have:

- OAuth
- Sphinx
