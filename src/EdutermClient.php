<?php
namespace Kennisnet\EdutermClient;

use \UnexpectedValueException;
use \InvalidArgumentException;

/**
 * PHP package for interfacing with Eduterm.
 *
 * @version 0.0.0
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
	private $endpoint = "";
	private $statuscode = 0;

	public $response_data = "";
	public $response_json = array();
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

	/**
	 * Wrapper for requesting and packaging the return data in phases.
	 * 1. get the raw string data in $response_data
	 * 2. if json, decode and put in $response_json
	 * 3. if not empty, put into easy-iterable $response_table
	 */
	public function request( $query, $args = array() ) {
		$this->getData( $query, $args );
		$this->checkStatusCode();

		$json = json_decode($this->response_data, TRUE);
		if ( !is_null( $json  )) {
			$this->response_json = $json;
		}

		if ( !empty( $this->response_json) ) {
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
	 * Requests data from Eduterm using provided query and arguments.
	 * Fills the $response_data and $statuscode.
	 */
	private function getData( $query, $args ) {
		$arglist = array();
		foreach( $args as $key => $value ) {
			$arglist[] = $key."=".$value;
		}

		$argstring = "";
		if ( !empty( $arglist ) ) {
			$argstring = "&".implode( "&", $arglist );
		}
		$this->query = $this->baseurl.$query."?api_key=".$this->apikey.$this->endpoint.$argstring;
		
		$curl = curl_init( $this->query );
		curl_setopt( $curl, CURLOPT_HEADER, FALSE );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $curl, CURLOPT_ENCODING, "gzip,deflate" );
		curl_setopt( $curl ,CURLOPT_USERAGENT, $this->useragent );
		$this->response_data = curl_exec( $curl );
		$this->statuscode = curl_getinfo( $curl, CURLINFO_HTTP_CODE);
		curl_close( $curl );
	}

	/**
	 * Checks the statuscode after getting data. Throw exception
	 * if statuscode is not 200.
	 */
	private function checkStatusCode() {
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
