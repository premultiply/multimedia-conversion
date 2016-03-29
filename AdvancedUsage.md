# Introduction #

Apart from simple conversion between various multimedia formats, MC offers advanced manipulation of multimedia files. It includes joining, cutting, mixing, transitions, embedding watermarks, various video and audio effects and more. For complete list of features visit [MLT website](http://mltframework.org).

# How does it work? #

You have to upload MltXml (in fact, our XML is a little different, please read next paragraph) file to MC just as regular audio or video file. You have to specify format and quality of output file and wait patiently.

For documentation of MltXml format [visit this site](http://www.mltframework.org/twiki/bin/view/MLT/MltXml).

# How our XML differs from original MltXml format? #

Firstly, each time you specify a resource in XML:

```
<property name="resource">clip1.dv</property>
```

it must be correct URI, otherwise MC will ignore it.

So it should be like that:

```
<property name="resource">http://www.myserver.com/clip1.dv</property>
```

Secondly, apart from "in" and "out" attributes, which are numbers of frames, you can specify "start" and "len" (length) in milliseconds. This feature is implemented to make it easier for Flash applications to work with MC. If "in" and "out" attributes are specified simultaneously they are ignored.

Thirdly, you shouldn't use "source\_fps" property in xml-s sent to MC if you're specifying timestamps in milliseconds.

Finally, as you probably know if you're familiar with MLT, MltXml files can be used as producers, so it would be easy to make MltXml file that specifies itself as a resource and puts MC into infinite loop. To prevent that the proper limitation is set in _/application/config/config.ini_

```
xml.depth = 5
```

It decides how "deep" MC will recursively go into Westley files that specify each other as producers. You can adjust it to your needs.