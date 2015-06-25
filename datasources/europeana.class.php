<?php
namespace esa_datasource {
	/**
	 * 
	 * The Subplugin to retrieve Data from Europeana!
	 * 
	 * Todo
	 * * implement some useful search filters
	 * * use a different API key.. ydRg6Ujho is registered for pfranck
	 * * what shall be seen in the resulting Item
	 * 
	 * @author philipp franck
	 *
	 */
	class europeana extends abstract_datasource {
		

			public $title = "Europeana Connection Alpha 0.1";
			public $info = "Europeana Connection Alpha 0.1"; 

			private $_hits_per_page = 24;
			
			function api_search_url($query) {
				$query = urlencode($query);
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=1&rows={$this->_hits_per_page}&profile=standard";
			}
			
			function api_single_url($id) {
				return "http://www.europeana.eu/api/v2/record{$id}.json?wskey=ydRg6Ujho&profile=standard";
			}
			
			function api_search_url_next($query) {
				$query = urlencode($query);
				$this->page += 1;
				$start = 1 + ($this->page - 1) * $this->_hits_per_page;
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$start&rows={$this->_hits_per_page}&profile=standard";
			} 
			
			function api_search_url_prev($query) {
				$query = urlencode($query);
				$this->page -= 1;
				$start = 1 + ($this->page - 1) * $this->_hits_per_page;
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$start&rows={$this->_hits_per_page}&profile=standard";
			} 
			
			function api_search_url_first($query) {
				$query = urlencode($query);
				$this->page = 1;
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=1&rows={$this->_hits_per_page}&profile=standard";
			}
			
			function api_search_url_last($query) {
				$query = urlencode($query);
				$this->page = $this->pages;
				$last = 1 + ($this->pages - 1) * $this->_hits_per_page;
				return "http://www.europeana.eu/api/v2/search.json?wskey=ydRg6Ujho&query=$query&start=$last&rows={$this->_hits_per_page}&profile=standard";
			}
			
			function parse_result_set($response) {
				$response = json_decode($response);
				
				if (!$response->success) {
					throw new \Exception('Sussess = false'); // todo: better error message 
				}
				
				if ($response->totalResults == 0) {
					throw new \Exception('Zero results'); // todo: better error message
				}
				
				$this->results = array(); 
				foreach ($response->items as $item) {
					$this->results[] = new \esa_item('europeana', $item->id, $this->_item2html($item, $item->id), $item->guid);
				}
				
				// set up pagination data
				$this->pages = round($response->totalResults / $this->_hits_per_page);


				return $this->results;
			}

			function parse_result($response) {
				$response = json_decode($response);
				
				if (!$response->success) {
					throw new \Exception('Sussess = false'); // todo: better error message 
				}
				
				$item = $response->object;
				return new \esa_item('europeana', $response->object->id, $this->_item2html($response->object, $this->id));
				
				
				
			}
			

			private function _item2html($item, $id) {
				//$html  = "<div class='wrapper'>";
				$html = "<h1>{$item->title[0]}</h1>";
				$html .= "<table class='datatable'>";
				$html .= "<tr><td>id</td><td>{$id}</td></tr>";
				if (isset($item->year)) {
					$html .= "<tr><td>year</td><td>{$item->year}</td></tr>";
				}
				$html .= "<tr><td>type</td><td>{$item->type}</td></tr>";
				if (count($item->title) > 1) {
					$html .= "<tr><td>alternative titles</td><td>" . implode(',', array_slice($item->title, 1)) . "</td></tr>";
				}
				if (count($item->provider)) {
					$html .= "<tr><td>provider</td><td>" . implode(',', $item->provider) . "</td></tr>";
				}
				/*if (count($item->dataProvider)) {
					$html .= "<tr><td>data provider</td><td>" . implode(',', $item->dataProvider) . "</td></tr>";
				}*/
				$html .= "</table>";
				$html .= "<img src='{$item->edmPreview[0]}'>";
				//$html .= "</div>";
				return $html;
			}
		
	}
}
?>