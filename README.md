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
