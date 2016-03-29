# Introduction #

Oto jak używać serwera MC.

# Parametry zapytań uploadujących #

Upload przez **GET** lub **POST**

## obowiązkowe ##

  * format - format do którego plik ma zostać skonwertowany
  * url - url pliku do wgrania na serwer (tylko kiedy zapytanie get)

## opcjonalne ##

  * quality - jakość
  * statusUrl - url na który serwer ma przesyłać status pliku

### zwraca xml ###


&lt;jobId&gt;

xxxx

Unknown end tag for &lt;/jobID&gt;



# Inne zapytania #

Zapytanie **GET**

## parametry ##

  * method
  * jobId


### sprawdzanie statusu ###

  * method = check
  * jobId = jobId otrzyame przy uploadzie


### download miniaturki ###

  * method = thumb
  * jobId = jobId otrzyame przy uploadzie

### download ###
  * method = get
  * jobId = jobId otrzyame przy uploadzie