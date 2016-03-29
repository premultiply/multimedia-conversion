# What do you need? #

In order MC to work properly you need working Linux/Unix server with PHP5 and PostgreSQL and following applications and php extensions:

  * **Zend Framework** - http://framework.zend.com/
  * **ffmpeg** - http://www.ffmpeg.org
  * **sox** - http://sox.sourceforge.net
  * **MLT** - http://www.mltframework.org
  * **ffmpeg-php php extension** - http://ffmpeg-php.sourceforge.net
  * **pecl\_http php extension** - http://pecl.php.net/package/pecl_http
  * **GD php extension** - http://www.libgd.org/
  * **qt-faststart** - http://multimedia.cx/eggs/improving-qt-faststart/
  * **flvtool2** - http://inlet-media.de/flvtool2
# Zend Framework files placement #

Extract contents of "ZendFramework-<version.number>/library/" directory from your Zend Framework package into "library".

# Server configuration #

  * php scripts maximum execution time respectively long (converting takes its time)
  * respectively high size limit of files uploaded using POST (if you'll use that method)
  * "public\_html" should be root directory

# Database #

You should execute SQL code from _/sql/db.sql_ in order to create database "mc" owned by user "mc". If your database name and username are different, you can edit _/sql/db.sql_ to fit your needs, or execute only that code:

```

CREATE TABLE jobs (
    id text NOT NULL,
    downloaded timestamp without time zone,
    uploaded timestamp without time zone,
    converted timestamp without time zone,
    conversion_started timestamp without time zone,
    upload_started timestamp without time zone,
    deleted timestamp without time zone,
    deletion_reason text,
    filename text,
    format text NOT NULL,
    quality text NOT NULL,
    status_url text
);


ALTER TABLE public.jobs OWNER TO mc;

ALTER TABLE ONLY jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);
```

when you're logged in to your PostgreSQL database.

# MC configuration #

You have to enter proper values into _/application/config/config.ini_ file.

```
database.params.host = <address of db server, "localhost" in most cases>
database.params.username = <your db username>
database.params.password = <your db password>
database.params.dbname = <name of your database>

http.throttle = <output files sending speed to end-user (in bytes/second)>

file.lifetime = <time of converted files being held on server before deleting (in hours)>

xml.depth - see AdvancedUsage for informations about this option

path.ffmpeg = <path to ffmpeg (/usr/bin/ffmpeg in my case)>
path.files = <path to directory where MC will keep its workfiles>
path.null = <path to null device (/dev/null in my case)
path.flvtool2 = <path to flvtool2 (/usr/bin/flvtool2 in my case)>
path.qtf = <path to qt-faststart (/usr/local/bin/qtf in my case)>
path.inigo = <path to inigo/melt (/usr/local/bin/melt)>

```

# Recommended versions #

MC has been tested and confirmed to work with following versions of applications mentioned above:

  * Zend Framework - 1.7.2
  * ffmpeg - SVN-[r16849](https://code.google.com/p/multimedia-conversion/source/detail?r=16849)
  * MLT - 0.3.6
  * sox - 14.2.0
  * ffmpeg-php - 0.6.1
  * GD - 2.0 or higher
  * http - 1.6.3

However, you don't have to use exactly the same versions. MC itself doesn't use any version-specific features of those applications. The purpose is to make them working together on your machine and those versions worked for me on Ubuntu 8.10 and Debian Lenny.

## A word on compiling ffmpeg, sox and MLT ##

The spectrum of supported formats and codecs depends mainly on options used when configuring ffmpeg, sox and MLT. For example, my configuration options for ffmpeg were:

```
--enable-gpl --enable-postproc --enable-pthreads --enable-libfaac --enable-libfaad --enable-libmp3lame --enable-libtheora --enable-libx264 --enable-libxvid --enable-libamr-nb --enable-libamr-wb --enable-libamr-wb --enable-shared --enable-nonfree --enable-libvorbis --enable-libgsm --enable-swscale
```

For further informations about configuring and building ffmpeg, sox and MLT please visit their websites. (links at the top of this page).

### Warning ###

What's important, when compiling MLT you can make use of ffmpeg codecs already installed in your system, or compile them independently with MLT, which may cause a situation when your ffmpeg supports some formats that your MLT doesn't support and vice versa.