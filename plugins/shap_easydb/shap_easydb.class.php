<?php


namespace esa_datasource {

    class shap_easydb extends abstract_datasource {

        public $title = 'SHAP - EasyDB'; // Label / Title of the Datasource
        public $index = 1; // where to appear in the menu
        public $info = "<p>...Kurzbeschreibung...</p>"; // get created automatically, or enter text
        public $homeurl; // link to the dataset's homepage
        public $debug = false;
        //public $examplesearch; // placeholder for search field
        //public $searchbuttonlabel = 'Search'; // label for searchbutton

        public $force_curl = true;

        private $_session_token;

        private $_easydb_url = "";
        private $_easydb_user = "";
        private $_easydb_pass = "";

        private $_items_per_page = 12;

        function construct() {
            $this->_easydb_url = esa_get_settings('modules', 'shap_easydb', 'easyurl');
            $this->_easydb_user = esa_get_settings('modules', 'shap_easydb', 'easyuser');
            $this->_easydb_pass = esa_get_settings('modules', 'shap_easydb', 'easypass');
        }

        function dependency_check() {
            if (!$this->check_for_curl()) {
                throw new \Exception('PHP Curl extension not installed');
            }
            $this->get_easy_db_session_token();
            return 'O. K.';
        }

        function message_abstract($e) {
            $json_msg = json_decode($e->getMessage());
            if (($e->getCode() == 666) and (is_object($json_msg))) {
                echo esa_debug($json_msg);
                return isset($json_msg->description) ? $json_msg->description : $json_msg->code;
            }
            return $e->getMessage();
        }

        function get_easy_db_session_token() {
            if ($this->_session_token) {
                return $this->_session_token;
            }
            if (!$this->_easydb_url or !$this->_easydb_pass or !$this->_easydb_user) {
                throw new \Exception('Easy-DB: credentials missing.');
            }
            try {
                $resp = json_decode($this->_fetch_external_data("{$this->_easydb_url}/session"));
                if (!isset($resp->token)) {
                    throw new \Exception('no token');
                }
                $this->_session_token = $resp->token;
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: create session failed: ' . $this->message_abstract($e));
            }
            try {
                $this->_fetch_external_data((object) array(
                    "url" => "{$this->_easydb_url}/session/authenticate?token={$this->_session_token}&login={$this->_easydb_user}&password={$this->_easydb_pass}",
                    "method" => "post"
                ));
            } catch (\Exception $e) {
                throw new \Exception('Easy-DB: authentication failed: ' . $this->message_abstract($e));
            }
            return $this->_session_token;
        }



        // id is _objecttype + "|" + id
        function api_single_url($id, $params = array()) {
            list($object_type, $object_id) = explode("|", $id);
            return "{$this->_easydb_url}/db/$object_type/_all_fields/$object_id?token={$this->_session_token}";
        }

        function api_record_url($id, $params = array()) {
            return "";
        }

        function show_errors() {
            echo "<div class='esa_error_list'>";
            foreach ($this->errors as $error) {
                $json_error = json_decode($error);
                if (is_object($json_error)) {
                    $error = isset($json_error->description) ? $json_error->description : $json_error->code;
                }
                echo "<div class='error'>$error</div>";
            }
            echo "</div>";
        }

        function api_search_url($query, $params = array()) {

            $this->get_easy_db_session_token();

            $search = array(
                "search" => array(
                    array(
                        "type" => "match",
                        "mode" => "token",
                        "string"=> $query,
                        "phrase"=> true
                    )
                ),
                "limit" => $this->_items_per_page
            );

            if (isset($params['offset'])) {
                $search['offset'] = $params['offset'];
            }

            return (object) array(
                'method' => 'post',
                'url' => "{$this->_easydb_url}/search?token={$this->_session_token}",
                'post_json' => $search
            );
        }

        function api_search_url_next($query, $params = array()) {
            $this->page += 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_prev($query, $params = array()) {
            $this->page -= 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_first($query, $params = array()) {
            $this->page = 1;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function api_search_url_last($query, $params = array()) {
            $this->page = $this->pages;
            $params['offset'] = ($this->page - 1) * $this->_items_per_page;
            return $this->api_search_url($query, $params);
        }

        function search($query = null) {
            //$query =
            return parent::search($query); // TODO: Change the autogenerated stub
        }

        function parse_result_set($response) {
            $response = json_decode($response);
            $this->results = array();
            foreach ($response->objects as $item) {
                $type = $item->_objecttype;
                $this->results[] = $this->parse_result($this->_fetch_external_data($this->api_single_url("$type|{$item->{$type}->_id}")));
            }

            $this->pages = (int) ($response->count / $this->_items_per_page) + 1;
            $this->page = isset($response->offset) ? ((int) ($response->offset / $this->_items_per_page) + 1) : 1;

            return $this->results;
        }

        function parse_field($field) {
            $lang = "de-DE";
            if (!isset($field->_standard)) {
                return "";
            }
            $one = "1";
            if (!isset($field->_standard->{$one})) {
                return "";
            }
            if (!isset($field->_standard->{$one}->text)) {
                return "";
            }
            if (!isset($field->_standard->{$one}->text->{$lang})) {
                $lang = get_object_vars($field->_standard->{$one}->text)[0];
            }
            return $field->_standard->{$one}->text->{$lang};
        }

        function parse_field_name($field_name) {
            return ucwords(str_replace(array("_id", "_"), array("", " "), $field_name));
        }


        function parse_result($response) {

            $json_response = $this->_json_decode($response);
            $object_type = $json_response[0]->_objecttype;
            $object = $json_response[0]->{$object_type};
            $id = "$object_type|{$object->_id}";
            $title = isset($object->name) ? $object->name : null;
            $lat = isset($object->latitude) ? $object->latitude : null;
            $lon = isset($object->longitude) ? $object->longitude : null;

            $data = new \esa_item\data();
            $data->title = $title;
            $data->addTable("Typ", $object_type);
            $data->addTable("ID", $object->_id);

            $fields_to_parse = array(
                'anbieter_id',
                'art_der_vorlage_id',
                'art_des_motivs_id',
                'bearbeitungsstatus_id',
                'ersteller_der_vorlage_id',
                'material_der_vorlage_id',
                'ort_des_motivs_id'
            );
            foreach ($fields_to_parse as $field_to_parse) {
                if (isset($object->{$field_to_parse})) {
                    $data->addTable($this->parse_field_name($field_to_parse), $this->parse_field($object->{$field_to_parse}));
                }
            }
            $fields_to_add = array(
                'anweisungen',
                'nutzungsbedingungen',
                'quelle',
                'verfasser_der_beschreibung'
            );
            foreach ($fields_to_add as $field_to_add) {
                if (isset($object->{$field_to_add})) {
                    $data->addTable(ucwords($field_to_add), $object->{$field_to_add});
                }
            }

            return new \esa_item("shap_easydb", $id, $data->render(), null, $title, array(), array(), $lat, $lon);
        }

        function stylesheet() {
            return array(
                'name' => get_class($this),
                'css' => ''
            );
        }

    }
}
?>