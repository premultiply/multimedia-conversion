# Cron #

In order to start converting uploaded media you need to run Cron controller, which is done by accessing <url to mc>/cron. It's recommended to add proper line to your crontab in order Cron controler to be executed every minute. This line should look like this:

```
* * * * *       lynx -mime_header "http://yourmcserver.com/cron" >> /var/log/mccron
```

Output of Cron controller (if any) will be saved to /var/log/mccron.

# Upload requests parameters #

Uploading is possible using **GET** or **POST**.

## Mandatory ##

  * format - format the file is supposed to be converted to
  * url - url of a file you want server to download (only needed when request type is GET)
  * method = upload (only needed when request type is GET)

## Optional ##

  * quality - quality of the output file ("normal" by default)
  * statusUrl - URL that status of conversion should be sent to

### The upload request returns following XML ###
```
<?xml version="1.0" encoding="UTF-8"?>
<jobId>[42-characters-long, unique JobID (small and capital letters, numbers)]</jobID>
```

or

```
<?xml version="1.0" encoding="UTF-8"?>
<error>Sorry, this media format or quality are not supported</error>
```

or

```
<?xml version="1.0" encoding="UTF-8"?>
<error>Sorry, you have to specify URL of the file you want to upload</error>
```

or

```
<?xml version="1.0" encoding="UTF-8"?>
<error>An error occured when uploading a file</error>
```

# Other available requests #

Request type: **GET**

## Checking state ##

### Parameters ###

  * method = check
  * jobId = <JobID received after uploading your file>

### Returns XML ###

```
<?xml version="1.0" encoding="UTF-8"?>
<state>[uploaded | converted | deleted | error]</state>
```

### Thumbnail download (if available) ###

  * method = thumb
  * jobId = <JobID received after uploading your file>

### Download ###


  * method = get
  * jobId = <JobID received after uploading your file>