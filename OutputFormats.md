# Predefined output formats and qualities #

## Video ##

### flv ###

  * normal
    * video bitrate: 400 kbps
    * video framerate: depends on input file when just converting or 25 when making use of MLT
    * video width: 320 px
    * video height: calculated dynamically or 180 px
    * video codec: flv
    * audio bitrate: 64 kbps
    * audio sample rate: 22050 Hz
    * number of audio channels: 1
    * audio codec: libmp3lame
    * thumbnails available: yes
    * number of passes: 1

### h264 ###

  * normal
    * video bitrate: 800 kbps
    * video framerate: depends on input file when just converting or 25 when making use of MLT
    * video width: 640 px
    * video height: calculated dynamically or 360 px
    * video codec: libx264
    * audio bitrate: 128 kbps
    * audio sample rate: depending on input file
    * number of audio channels: 2
    * audio codec: libfaac
    * thumbnails available: no
    * number of passes: 2


### 3gp ###

  * normal
    * video bitrate: 200 kbps
    * video framerate: depends on input file when just converting or 25 when making use of MLT
    * video width: 176 px
    * video height: 144 px
    * video codec: h263
    * audio bitrate: 32 kbps
    * audio sample rate: 8000 Hz
    * number of audio channels: 1
    * audio codec: libfaac
    * thumbnails available: yes
    * number of passes: 1

## Audio ##

### mp3 ###

  * normal
    * audio bitrate: 192 kbps
    * audio sample rate: 22050 Hz
    * number of audio channels: 2
    * audio codec: libmp3lame

## Warning! ##

In order those formats to work properly you need to have ffmpeg and MLT configured and built with support of codecs mentioned above.

# Custom output formats #

Since all the predefined formats are there because they fit my needs, they doesn't necessarily fit yours. Luckily, you can easily define your own output formats by editing _/application/config/formats.ini_

Let's take flv video format as an example.

**_formats.ini_ entry:
```
flv.normal.pass.first.ffmpeg = -b 400k -acodec libmp3lame -ac 1 -ar 22050 -ab 64k -vcodec flv -f flv
flv.normal.pass.first.mlt = "b=400k s=320x180 acodec=libmp3lame ac=1 ar=22050 ab=64k vcodec=flv f=flv"
flv.normal.width = 320
flv.extension = flv
flv.mediatype = video
flv.thumbs = true
flv.flvtool2 = true
```**

As you can see, some options are specified this way:

```
<format>.<quality>.<option>.<sub-option>.... = <value>
```

Those are quality-dependent. Others are specified that way:

```
<format>.<option>.<sub-option>.... = value
```

Those apply to format, not depending on quality.

Now, let's analyse it line by line.

```
flv.normal.pass.first.ffmpeg = -b 400k -acodec libmp3lame -ac 1 -ar 22050 -ab 64k -vcodec flv -f flv
```

Here the options for ffmpeg encoding are defined. You can specify codecs and their options here, as well as file format. You can get familiar with those options at [ffmpeg website](http://ffmpeg.org).
**Do not specify -s (size) option here.**

**You can set first pass only, or both first and second if you wish to use two-pass encoding. Specifying only second pass won't work.**

```
flv.normal.pass.first.mlt = "b=400k s=320x180 acodec=libmp3lame ac=1 ar=22050 ab=64k vcodec=flv f=flv"
```

Options for MLT, pretty the same as for ffmpeg, with a bit different syntax. You can read more about those options [here](http://www.mltframework.org/twiki/bin/view/MLT/ConsumerAvformat).
**In opposition to ffmpeg, you have to specify video size here, and remember to have those options between quotes, unless the ini file won't parse properly.**

```
flv.normal.width = 320
```

Width of output file. You can specify height similarly (flv.normal.height = 240) if you want your output files to have constant, not depending on input files, size.

```
flv.extension = flv
```

Extension of the file that will be sent to end-user.

```
flv.mediatype = video
```

Type of media that output file contains. **Can be "audio" for audio-only files or "video" for video (which are mixed audio and video in most cases).**

```
flv.thumbs = true
```

This option decides whether thumbnails of output files will be available or not. **Change it to "false" if you'll experience any problems with thumbnails in your format. It doesn't need to be set when mediatype is "audio".**

```
flv.flvtool2 = true
```

If **flvtool2** is set true, MC treats output files with flvtool2, which is important when you want to stream your flv files. Has no use with formats other than flv.

## More complex, 2-pass h264 video encoding _formats.ini_ entry: ##

```
h264.normal.pass.first.ffmpeg = -pass 1 -an -vcodec libx264 -b 800k -bt 800k -threads 0 -f rawvideo
h264.normal.pass.second.ffmpeg = -pass 2 -acodec libfaac -ac 2 -ab 128k -vcodec libx264 -b 800k -bt 800k -threads 0 -f mp4
h264.normal.pass.first.mlt = "pass=1 vcodec=libx264 s=640x360 b=800k bt=800k threads=0 f=rawvideo"
h264.normal.pass.second.mlt = "pass=2 acodec=libfaac ac=2 ab=128k vcodec=libx264 s=640x360 b=800k bt=800k threads=0 f=mp4"
h264.normal.width = 640
h264.extension = mp4
h264.mediatype = video
h264.thumbs = false
h264.qtf = true
```

```
h264.qtf = true
```

Decides whether to use **qt-faststart** on output files. Similar to **flvtool2**, but for h264 files.