<?php
namespace Kennisnet\EdutermClient;

use \UnexpectedValueException;
use \InvalidArgumentException;

/**
 * PHP package for interfacing with Eduterm.
 *
 * @version 1.1.0
 * @author Wim Muskee <wimmuskee@gmail.com>
 * 
 * Copyright 2018 Stichting Kennisnet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
**/

class EdutermClient {
	private $baseurl = "http://api.onderwijsbegrippen.kennisnet.nl/1.0/Query/";
	private $apikey = "";
	private $useragent = "phpEdutermClient";
	private $timeout = 10;
	private $endpoint = "";
	private $statuscode = 0;

	# contains last sent query
	public $query = "";
	# contains raw response data
	public $response_data = "";
	# contains json_encoded json response, if available
	public $response_json = array();
	# contains iterable response array, if available
	public $response_table = array();


	public function __construct( $apikey ) {
		if ( !$this->checkUuid( $apikey ) ) {
			throw new UnexpectedValueException( "Provided API key is not a valid uuid: ".$apikey );
		}
		$this->apikey = $apikey;
	}

	public function setEndpoint( $endpoint ) {
		if ( !empty( $endpoint ) ) {
			$this->endpoint = "&endpoint=".$endpoint;
		}
	}

	public function setBaseUrl( $baseurl ) {
		if ( !empty( $baseurl ) ) {
			$this->baseurl = $baseurl;
		}
	}

	public function setUseragent( $useragent ) {
		if ( !empty( $useragent ) ) {
			$this->useragent = $useragent;
		}
	}

	public function setTimeout( $timeout ) {
		if ( !empty( $timeout ) && is_int( $timeout ) ) {
			$this->timeout = $timeout;
		}
	}

	/**
	 * Wrapper for requesting and packaging the return data in phases.
	 * 1. get the raw string data in $response_data
	 * 2. if json, decode and put in $response_json
	 * 3. if not empty, put into easy-iterable $response_table
	 */
	public function request( $query, $args = array() ) {
		# reset response values
		$this->response_data = "";
		$this->response_json = array();
		$this->response_table = array();

		# get data and validate response status 200
		$this->setQuery( $query, $args );
		$this->setData();
		$this->checkStatusCode();

		# if result is json, parse further in usable formats
		$this->setJson();
		$this->setTable();
	}

	/**
	 * Uses Eduterm query and arguments to create a complete request query.
	 */
	public function setQuery( $query, $args = array() ) {
		$arglist = array();
		foreach( $args as $key => $value ) {
			$arglist[] = $key."=".$value;
		}

		$argstring = "";
		if ( !empty( $arglist ) ) {
			$argstring = "&".implode( "&", $arglist );
		}
		$this->query = $this->baseurl.$query."?api_key=".$this->apikey.$this->endpoint.$argstring;
	}

	/**
	 * Requests data from Eduterm using provided query and arguments.
	 * Fills the $response_data and $statuscode.
	 */
	public function setData() {
		$curl = curl_init( $this->query );
		curl_setopt( $curl, CURLOPT_HEADER, FALSE );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $curl, CURLOPT_ENCODING, "gzip,deflate" );
		curl_setopt( $curl, CURLOPT_USERAGENT, $this->useragent );
		curl_setopt( $curl, CURLOPT_TIMEOUT, $this->timeout );
		$this->response_data = curl_exec( $curl );
		$this->statuscode = curl_getinfo( $curl, CURLINFO_HTTP_CODE);
		curl_close( $curl );
	}

	/**
	 * Checks the statuscode after getting data. Throw exception
	 * if statuscode is not 200.
	 */
	public function checkStatusCode() {
		switch( $this->statuscode ) {
			case 200:
				return;
			case 401:
				throw new InvalidArgumentException( "Error getting data, not authorized: ".$this->apikey );
			case 400:
				throw new InvalidArgumentException( "Error getting data, wrong query-argument combination." );
			default:
				throw new UnexpectedValueException( "Error getting data" );
		}
	}

	/**
	 * Sets the response_json if raw data is in json format.
	 */
	public function setJson() {
		$json = json_decode($this->response_data, TRUE);
		if ( !is_null( $json )) {
			$this->response_json = $json;
		}
	}

	/**
	 * Turns the Json-Sparql results into an iterable table.
	 */
	public function setTable() {
		if ( !empty( $this->response_json ) ) {
			foreach( $this->response_json["results"]["bindings"] as $datarow ) {
				$row = array();
				foreach( $datarow as $columnname => $celldata ) {
					$row[$columnname] = $this->getTypedValue( $celldata );
				}
				$this->response_table[] = $row;
			}
		}
	}

	/**
	 * Some return values specify a datatype. Return the value
	 * in the provided known datatype.
	 */
	private function getTypedValue( $celldata ) {
		if ( !array_key_exists("datatype", $celldata) ) {
			return $celldata["value"];
		}

		switch( $celldata["datatype"] ) {
			case "http://www.w3.org/2001/XMLSchema#boolean":
				return $this->parseBoolean( $celldata["value"] );
			case "http://www.w3.org/2001/XMLSchema#integer":
				return (int) $celldata["value"];
			default:
				return $celldata["value"];
		}
	}

	/**
	 * Return FALSE if string input not "true".
	 */
	private function parseBoolean( $string ) {
		if ( $string == "true" ) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Return FALSE if input not a uuid.
	 */
	private function checkUuid( $value ) {
		return preg_match("/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i", $value);
	}
}
