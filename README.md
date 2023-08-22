# phpEdutermClient
PHP client for interfacing with Eduterm.

# usage
Initiate the client with a valid api key, and request with a queryname and optional arguments.
The response table will allow you to iterate the response rows.
```php
$eduterm = new EdutermClient("994afb90-2481-4581-a6dd-a02c0de0a9f8");

$eduterm->request("VakLeergebieden", array("onderwijsniveau"=> "bk:512e4729-03a4-43a2-95ba-758071d1b725"));

foreach( $eduterm->response_table as $row ) { 
    echo $row["vakLabel"]."\n";
}
```

The raw data is also available as well as the json data array (if the response was formatted in json):
* `$eduterm->response_data`
* `$eduterm->response_json`

# eduterm
Assuming you do not know anything about Eduterm, you can find some (Dutch) documentation on the [developer wiki](https://developers.wiki.kennisnet.nl/index.php?title=Eduterm:Hoofdpagina).
Specifically useful might be the [ListQueries](https://developers.wiki.kennisnet.nl/index.php?title=Eduterm:Interface#ListQueries) operation which provides an overview to the queries to which an API-key has access, but also which arguments are required for each query.
