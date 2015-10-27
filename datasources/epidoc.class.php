<?php
/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | Subplugin: epidoc
 * @link 		
 * @author 		
 *
 * Status: Alpha 1
 * 
 * Takes a link to a ressource encoded in epidoc and builds a Esa Item around it.
 *
 *
 * Proovided List of EpiDoc Using Projects
 * 
 * * http://www.eagle-network.eu/advanced-search/
 * 
 * * Ancient Inscriptions of the Northern Black Sea - http://iospe.kcl.ac.uk/
 * * Epigraphische Datenbank Heidelberg - http://edh-www.adw.uni-heidelberg.de/
 * * Inscriptions of Greek Cyrenaicaica - https://igcyr.unibo.it/
 * * Inscriptions of Israel/Palestine - http://library.brown.edu/cds/projects/iip/search/
 * * http://www.trismegistos.org/
 * * http://papyri.info
 * 
 * * The Inscriptions of Roman Tripolitania: http://inslib.kcl.ac.uk/
 * * Vindolanda Tablets Online: http://vindolanda.csad.ox.ac.uk/
 * * Inscriptions of Aphrodisias: http://insaph.kcl.ac.uk/iaph2007/index.html
 * 
 * 
 * * Datenbank zur jüdischen Grabsteinepigraphik - http://steinheim-institut.de
 * 
 * 
 * http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD006705.xml
 * http://vindolanda.csad.ox.ac.uk/Search/tablet-xml-files/128.xml
 * 
 */


namespace esa_datasource {
	class epidoc extends abstract_datasource {

		public $title = 'Epidoc'; // Label / Title of the Datasource
		public $info = 'http://insaph.kcl.ac.uk/iaph2007/xml/iAph010008.xml<br>http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000106.xml<br>http://library.brown.edu/cds/projects/iip/view_xml/akko0100/'; 
		public $homeurl = ''; // link to the dataset's homepage
		public $debug = true;
		
		public $pagination = false; // are results paginated?
		public $optional_classes = array(); // some classes, the user may add to the esa_item

		public $require = array('inc/epidocConverter/epidocConverter.class.php');
		
		
		function api_search_url($query, $params = array()) {
			//http://edh-www.adw.uni-heidelberg.de/edh/inschrift/HD000015.xml
			if ($this->_ckeck_url($query)) {
				return $query;
			}
			return "";
		}
			
		function api_single_url($id) {
			if ($this->_ckeck_url($id)) {
				return $id;
			}
			return "";
		}
		
		function api_record_url($id) {
			return $id;
		}
			
		function api_url_parser($string) {
			return $string;
		}
			
		function parse_result_set($response) {
			return array($this->parse_result($response[0]));
		}

		function parse_result($response) {
			
			$c = \epidocConverter::create('', true);
			$c->workingDir = $this->path . '/inc/epidocConverter';
			$c->set($response);
			$epi = $c->convert(true);
			
			$map = array(
				'edition' => true,
				'translation' => true
			);
						
			$epiDom = new \DOMDocument();
			@$epiDom->loadHTML($epi);
			
			$divs = $epiDom->getElementsByTagName('div');
			
			foreach ($divs as $div) {
					
				
				$idx = explode('_', $div->getAttribute('id'));
				
				$id = $idx[0];
				$lang = isset($idx[1]) ? $idx[1] : 0; 
				
				
				if (!isset($map[$id])) {
					continue;
				}
				
				$field = $map[$id] ? 'text' : 'table';
				
				$title = $id;
				
				// saxon stylesheets provide some headlines
				
				$h2s = $div->getElementsByTagName('h2');
				foreach ($h2s as $h2) {
					//$title = $h2->nodeValue;
					$h2->parentNode->removeChild($h2);
				}
				$h3s = $div->getElementsByTagName('h3');
				foreach ($h3s as $h3) {
					//$title = $h3->nodeValue;
					$h3->parentNode->removeChild($h3);
				}
				
				
				$firstchild = $div->firstChild;
				if ($firstchild->nodeName == 'br') {
					$firstchild->parentNode->removeChild($firstchild);
				}
				
				$data[$field][$title][$lang] = trim($epiDom->saveHTML($div));
			}
			
			// get translation, english if avalable
			foreach ($map as $title => $isText) {
				if (!isset($data[$field][$title])) {
					continue;
				}
				
				
				$field = $isText ? 'text' : 'table';
				if (isset($data[$field][$title]['en'])) {
					$data[$field][$title] = $data[$field][$title]['en'];
				} else {
					$last = array_pop($data[$field][$title]);
					$data[$field][$title] = $last;
				}
			}
			
			
			
			// fetch the rest of relevant data manually
			
			$xml = new \SimpleXMLElement($response);
			
			// teiHeader
			// teiHeader->fileDesc
			$data['title'] = $this->_get(
				$xml->teiHeader->fileDesc->titleStmt->title
			);

			$data['table']['provider'] = $this->_get(
				$xml->teiHeader->fileDesc->publicationStmt->authority
			);
			
			// teiHeader->fileDesc->sourceDesc
			
			$data['table']['objectType'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->objectType,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->p->objectType
			);
			$data['table']['material'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->material,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->supportDesc->support->p->material
			);				
			$data['table']['execution'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->physDesc->objectDesc->layoutDesc->layout->execution
			);
			$data['table']['modernFindSpot'] = $this->_get(
					$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->provenance->placeName,
					$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->provenance->p->placeName
			);
			$data['table']['ancientFindSpot'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origPlace->placeName,
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origPlace
			);
			$data['table']['origDate'] = $this->_get(
				$xml->teiHeader->fileDesc->sourceDesc->msDesc->history->origin->origDate
			);

			$data['table']['urls'] = $this->_get(
					$xml->teiHeader->fileDesc->publicationStmt->idno
			);
			$data['url'] = $this->_get(
					(string) $xml->teiHeader->fileDesc->publicationStmt->idno[0],
					$this->id
			);
			
			
			$data['images'] = $this->_getImage(
				$xml->facsimile->graphic
			);
			
			
			//$debug = '<textarea>'. print_r($data['text'], 1) . '</textarea>';

			return new \esa_item('epidoc', $this->query, $debug . $this->render_item($data), $data['url']);
		}
		
		
		private function _getImage() {
			$alternatives = func_get_args();
				
			while (count($alternatives)) {
				$elems = array_shift($alternatives);
				if (!$elems instanceof \SimpleXMLElement) {
					continue;
				}
				
				if (count($elems)) {
					$list = array();
						
					foreach($elems as $elem) {
						$img = (object) array();
						
						$img->url = $elem['url'];
						$img->text = (string) $elem->desc;
						$img->title = (string) $elem->desc->ref;
						
						$list[] = $img;
					}
					return $list;
				}
			}

			
		}
		
		
		private function _get() {
			$alternatives = func_get_args();
			
			while (count($alternatives)) {
				$elems = array_shift($alternatives);
				if (!$elems instanceof \SimpleXMLElement) {
					return (string) $elems;
				}
				
				
								
				if (isset($elems) and count($elems)) {
					
					$texts = array();
					
					foreach($elems as $elem) {
						$text = (string) $elem;
						
						// rs elements
						if (($text == 'rs') && (isset($elem['type']))) {
							$text = $elem['type'];
						}
						
						// links
						if (isset($elem['ref'])) {
							$text = "<a target='_blank' href='{$elem['ref']}'>$text</a>";
						}
						if (isset($elem['type']) and ($elem['type'] == 'URI')) {
							$text = "<a target='_blank' href='{$text}'>$text</a>";
						}
						
						$texts[] = $text;
					}
					
					
					return implode(', ', array_unique($texts));
					
				}
			}
			
			return '';
			
		} 
		
		
		function dependency_check() {
			

			$c = new \epidocConverter;
			
			try {
				$c->status();
			} catch (\Exception $e) {
				return $e->getMessage();
			}
			
			return true;
			
			
		}

	}
}
?>