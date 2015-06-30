<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Abstract Datasource Class
 * @link 		http://www.europeana.eu/
 * @author 		Philipp Franck
 * 
 * 
 * Every Datasource wich is connected to the Eagle Story Telling Application (such as europeana, Isai Gazetteer etc.) is an implementation
 * of this partly abstract class.
 * 
 * 
 */
namespace esa_datasource {
	abstract class abstract_datasource {
		
		// infotext to this data source
		public $title;
		public $info; 
		public $homeurl; 
		
		// array of esa_items containing the results of a performed search
		public $results = array();


		// saves current serach params
		public $query;
		public $id;
		public $params;
		
		// pagination data
		public $pagination = true; //is pagination possible / supported in the serach results
		public $page = 1; //current page
		public $pages = false; // number of pages. false means: unknown
		
		// some classes, the user may add to the esa_item
		public $optional_classes = array(); //'test' => 'test'
		//public $query_options = array(); // some additional options, the user may use to specify his query. can be used in the implementation of a datasource
		
		//error collector
		public $errors = array();
		
		
		/**
		 * just to generate a generic info text
		 */
		function __construct() {
			if (!$this->info) {
				$this->info = "Insert anything you want to search for <strong>or</strong> <a href='{$this->homeurl}' target='_blank'> search at the {$this->title} itself</a> and paste the URL of one record in the field below.";
			}
		}
		
		
		/**
		 * a generic search dialogue (can be overwitten) 	
		 * - needs $this->apiurl to be set
		 * - you can overwrite this whole function in the implementation or just the search_form_params part 
		 */
		function search_form() {
			
			echo "<p>{$this->info}</p>";
			
			$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : '';
			echo "<form method='post'>";
			echo "<input type='text' name='esa_ds_query' value='{$query}'>";

			echo "<input type='hidden' name='esa_ds_page' value='1'>";
			
			echo $this->search_form_params($_POST);
			
			echo "<input type='submit' class='button button-primary' value='Search'>";
			echo "</form>";
		}
		
		/**
		 * to overwritten by implementation if needed
		 * @return string
		 */
		function search_form_params($post) {
			return "";
		}
		
		
		/**
		 * 
		 * Serach given Data Source for Query
		 * 
		 * This is a generic function, it can be overwritten in some implementations
		 * 
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result_set or false if error
		 */
		function search() {
			try {
				$query = (isset($_POST['esa_ds_query'])) ? $_POST['esa_ds_query'] : null;
				
				if (!$query) {
					return;
				}
				
				// collect $_POST data
				$this->page  = (isset($_POST['esa_ds_page'])) ? $_POST['esa_ds_page'] : null;
				$this->pages  = (isset($_POST['esa_ds_pages'])) ? $_POST['esa_ds_pages'] : null;
				$navi  = (isset($_POST['esa_ds_navigation'])) ? $_POST['esa_ds_navigation'] : '';
				$this->query = $query;
				
				// additional $_POST data
				$params = array();
				foreach ($_POST as $k => $v) {
					if ($v and preg_match('#^esa_ds_param_(.*)#', $k, $real_k)) {
						$params[$real_k[1]] = $v;
					}
				}
				$this->params = $params;
				
				// go
				
				// is url pasted?
				if ($url = $this->api_url_parser($query)) {
					//print_r($url);
					$this->results = array($this->parse_result($this->_generic_api_call($url)));
					
				} else {
					// perform search
					$fun = "api_search_url_$navi";
					
					if ($navi and method_exists($this, $fun)) {
						$queryurl = $this->$fun($query, $params);
					} else {
						$queryurl = $this->api_search_url($query, $params);
					}
					if (ESA_DEBUG) {
						echo $queryurl;
					}
					
					$this->parse_result_set($this->_generic_api_call($queryurl));
				
				}

				
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			
			
			return (!count($this->errors));
		}
		
		/**
		 * 
		 * get data from source for a specific unique identifier
		 * 
		 * This is generic function, it can be overwritten in some implementations
		 * 
		 * @param $id - unique identifier
		 * 
		 * @return array of result, wich has to be parsed by $this->parse_result
		 */
		function get($id) {
			$this->id = (isset($_POST['esa_ds_id'])) ? $_POST['esa_ds_id'] : $id;
			return $this->parse_result($this->_generic_api_call($this->api_single_url($this->id)));
		}
		
		/**
		 * used for the generic get and search function only; 
		 * 
		 * @param string $api
		 * @param string $param
		 */
		private function _generic_api_call($url) {
				
			if (!$url) {
				throw new \Exception('No Query');
			}
				
			$response = $this->_fetch_external_data($url);
			
			
			return $response;
		}
		
		
		/**
		 * 
		 * This functions parses a result from a api and brings it in the needed form
		 * it HAS to be implemented in every data source class
		 * 
		 * @param unknown $result
		 */
		abstract function parse_result_set($result);
		
		abstract function parse_result($result);
		
		abstract function api_single_url($id);
		
		abstract function api_search_url($query, $params = array());
		
		abstract function api_record_url($id);
		
		abstract function api_url_parser($id);
		
		/**
		 * 
		 * display error message
		 * 
		 * @param string $error_text
		 */
		protected function error($error_text) {
			$this->errors[] = $error_text;
		}
		

		/**
		 * shows the list of search results to select one! 
		 * 
		 */
		function show_result() {
			
			$this->show_pagination();
			
			echo "<div class='esa_item_list'>";
			foreach ($this->results as $result) {
				if (is_object($result)) {
					$result->html();
				}
			}
			echo "</div><div style='clear:both'></div>";
		}
		
		/**
		 * shows the pagination control of the results (next page etc.), when
		 * $this->pagination contains data
		 * it is a task of the specific implementation of this class, to fill the array,
		 * because how pagination works strongly differs from datasource to datasource  
		 * 
		 * 
		 */
		function show_pagination() {
			if ($this->pagination) {
				
				echo "<div class='esa_item_list_pagination'>";
				
				if (method_exists($this, "api_search_url_first")) {
					$this->show_pagination_button('first');
				}
				
				if (method_exists($this, "api_search_url_prev") and ($this->page > 1)) {
					$this->show_pagination_button('prev');
				}

				echo "<div class='esa_item_list_pagination_current'>";
				
				if ($this->page) {
					echo "Page " . $this->page;
				}
								
				if ($this->pages) {
					echo ($this->page) ? ' of ' : 'Pages: '; 
					echo $this->pages;
				}
				
				echo "</div>";
				
				if (method_exists($this, "api_search_url_next") and ($this->page < $this->pages)) {
					$this->show_pagination_button('next');
				}
				
				if (method_exists($this, "api_search_url_last") and $this->pages) {
					$this->show_pagination_button('last');
				}
				
				echo "</div>";
			}
		}
		
		function show_pagination_button($type) {
			
			$labels = array(
					'prev' => "Previous",
					'next' => "Next",
					'first' => "First",
					'last' => "Last"
			);
			
		
			echo "<form method='post' class='esa_item_list_pagination_button'>";
			echo "<input type='hidden' name='esa_ds_query' value='{$this->query}'>";
			echo "<input type='hidden' name='esa_ds_page' value='{$this->page}'>";
			echo "<input type='hidden' name='esa_ds_pages' value='{$this->pages}'>";
			echo "<input type='hidden' name='esa_ds_navigation' value='$type'>";
			foreach ($this->params as $k => $v) {
				echo "<input type='hidden' name='esa_ds_param_$k' value='$v'>";
			}
			echo "<input type='submit' class='button button-secondary' value='{$labels[$type]}'>";
			echo "</form>";
		}
		
		/**
		 * shows the list of errors
		 *
		 */
		function show_errors() {
			echo "<div class='esa_error_list'>";
			foreach ($this->errors as $error) {
				echo "<div class='error'>$error</div>";
			}
			echo "</div>";
		}
		
		/**
		 * fetches $data from url, unsing curl if possible, if not it uses file_get_contents
		 * 
		 * (curl version never tested :D )
		 */
		protected function _fetch_external_data($url) {
			if (!$url) {
				throw new \Exception('no $url!');
			}
				
			/*
			if(ESA_CURL & function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close") ) {
				$ch = curl_init();
				
				$http_headers = array(
					"Accept: application/json",
					"Connection: close",                    // Disable Keep-Alive
					"Expect:",                              // Disable "100 Continue" server response
					"Content-Type: application/json"        // Content Type json
				);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLOPT_VERBOSE, true);               // Verbose mode for diagnostics
				curl_setopt($ch, CURLOPT_POST, true);  
				curl_setopt($ch, CURLOPT_URL, $url);
				$response = curl_exec($ch);
				
				
				if (ESA_DEBUG) {
					echo "<pre class='esa_debug'>";
					echo "url: ", $url, "\nPOST: ", print_r($_POST,1 ), "\nResponse: ";
					print_r((array) json_decode($response));
					echo "\nerror:" . curl_error($ch);
					echo "</pre>";
				}
				
				curl_close($ch);
				return $response;
			}
		

			*/
			//echo $url;
			
			if (!$json = file_get_contents($url)) {
				$this->error('some error');
				throw new \Exception('no response!');
				
				// a _temporary hack_ for the idai subplugin
				//$json = '{"@id":"http://gazetteer.dainst.org/place/2281530","gazId":"2281530","parent":"http://gazetteer.dainst.org/place/2321390","relatedPlaces":["http://gazetteer.dainst.org/place/2281792"],"types":["populated-place"],"prefName":{"title":"Palmyra","language":"","ancient":true},"names":[{"title":"Palmyre","language":""},{"title":"تدمر","language":"ara"},{"title":"Palmyra","language":""},{"title":"Tedmor","language":""},{"title":"Palmyra","language":"pol"},{"title":"Tadmor","language":""},{"title":"Palmyra","language":"eng"},{"title":"Palmira","language":"spa"},{"title":"Palmyra","language":"deu"},{"title":"مملكة تدمر","language":"ara"},{"title":"Palmira","language":"ita"},{"title":"Palmyre","language":"fra"},{"title":"Пальмира","language":"rus"},{"title":"Palmira","language":"por"}],"prefLocation":{"coordinates":[38.28055,34.56077],"confidence":0,"publicSite":true},"locations":[{"coordinates":[38.28055,34.56077],"confidence":3,"publicSite":true},{"coordinates":[38.31667,34.55],"confidence":3,"publicSite":true}],"identifiers":[{"value":"668331","context":"pleiades"},{"value":"18964","context":"arachne-place"},{"value":"zTopogAsienVordeSyriePalmyra","context":"zenon-thesaurus"},{"value":"2023","context":"arachne-place"},{"value":"1205728","context":"arachne-entity"},{"value":"000074435","context":"zenon-systemnr"},{"value":"163809","context":"geonames"}],"links":[{"object":"http://whc.unesco.org/en/list/23","predicate":"rdfs:seeAlso"},{"object":"http://arachne.uni-koeln.de/entity/1205728","predicate":"owl:sameAs"},{"object":"http://pleiades.stoa.org/places/668331","predicate":"owl:sameAs"},{"object":"http://sws.geonames.org/163809","predicate":"owl:sameAs"}],"provenance":["syrher","geonames"]}';
			}
			return $json;
		}
		
		/**
		 * json decode with error handling
		 */
		protected function _json_decode($json) {

			//echo "input json string<br><pre>$json</pre><hr>";

			
			$dec = json_decode($json);
			
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					break;
				case JSON_ERROR_DEPTH:
					$this->error('json error: - Maximum stack depth exceeded');
					break;
				case JSON_ERROR_STATE_MISMATCH:
					$this->error('json error: - Underflow or the modes mismatch');
					break;
				case JSON_ERROR_CTRL_CHAR:
					$this->error('json error: - Unexpected control character found');
					break;
				case JSON_ERROR_SYNTAX:
					$this->error('json error: - Syntax error, malformed JSON');
					break;
				case JSON_ERROR_UTF8:
					$this->error('json error: - Malformed UTF-8 characters, possibly incorrectly encoded');
					break;
				default:
					$this->error('json error: - Unknown error');
					break;
			}
			
			//echo "<br><hr>output json<br><pre>", print_r($dec, 1), "</pre>";
			
			
			return $dec;
		}
		
		
	}
}
?>