<?php
/**
 * Takes an element ID finds it's settings, returns either raw data or markup
 * to be used in the requesting app
 *
 * @package seed.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2010, CASH Music
 * Licensed under the Affero General Public License version 3.
 * See http://www.gnu.org/licenses/agpl-3.0.html
 *
 **/
class ElementPlant extends PlantBase {
	public function __construct($request_type,$request) {
		$this->request_type = 'element';
		$this->plantPrep($request_type,$request);
	}
	
	public function processRequest() {
		if ($this->action) {
			switch ($this->action) {
				case 'addelement':
					if (!$this->checkRequestMethodFor('direct')) { return $this->sessionGetLastResponse(); }
					if (!$this->requireParameters('name')) { return $this->sessionGetLastResponse(); }
					if (!$this->requireParameters('type')) { return $this->sessionGetLastResponse(); }
					if (!$this->requireParameters('options_data')) { return $this->sessionGetLastResponse(); }
					$result = $this->addElement($this->request['name'],$this->request['type'],$this->request['options_data']);
					if ($result) {
						return $this->response->pushResponse(
							200,$this->request_type,$this->action,
							array('element_id' => $result),
							'success. element id included in payload'
						);
					} else {
						return $this->response->pushResponse(
							500,$this->request_type,$this->action,
							$this->request,
							'there was an error adding the element'
						);
					}
					break;
				case 'getelement':
					if (!$this->requireParameters('element_id')) { return $this->sessionGetLastResponse(); }
						$result = $this->getElement($this->request['element_id']);
						if ($result) {
							return $this->response->pushResponse(
								200,$this->request_type,$this->action,
								$result,
								'success. element included in payload'
							);
						} else {
							return $this->response->pushResponse(
								500,$this->request_type,$this->action,
								$this->request,
								'there was an error retrieving the element'
							);
						}
					break;
				case 'getmarkup':
					if (!$this->checkRequestMethodFor('direct')) { return $this->sessionGetLastResponse(); }
					if (!$this->requireParameters('element_id')) { return $this->sessionGetLastResponse(); }
					$result = $this->getElementMarkup($this->request['element_id'],$this->request['status_uid']);
					if ($result) {
						return $this->response->pushResponse(
							200,$this->request_type,$this->action,
							$result,
							'success. markup in the payload'
						);
					} else {
						return $this->response->pushResponse(
							500,$this->request_type,$this->action,
							$this->request,
							'markup not found'
						);
					}
					break;
				default:
					return $this->response->pushResponse(
						400,$this->request_type,$this->action,
						$this->request,
						'unknown action'
					);
			}
		} else {
			return $this->response->pushResponse(
				400,
				$this->request_type,
				$this->action,
				$this->request,
				'no action specified'
			);
		}
	}
	
	public function getElement($element_id) {
		$query = "SELECT name,type,options FROM seed_elements WHERE id = $element_id";
		$result = $this->db->doQueryForAssoc($query);
		if ($result) {
			$the_element = array(
				'name' => $result['name'],
				'type' => $result['type'],
				'options' => json_decode($result['options'])
			);
			return $the_element;
		} else {
			return false;
		}
	}

	public function getElementMarkup($element_id,$status_uid) {
		$element = $this->getElement($element_id);
		$element_type = $element['type'];
		$element_options = $element['options'];
		if ($element_type) {
			$for_include = SEED_ROOT.'/elements/'.$element_type.'.php';
			if (file_exists($for_include)) {
				include($for_include);
				$element_object = new $element_type($status_uid,$element_options);
				return $element_object->getMarkup();
			}
		} else {
			return false;
		}
	}

	public function addElement($name,$type,$options_data,$user_id=0) {
		$options_data = json_encode($options_data);
		$options_data = "'" . mysql_real_escape_string($options_data) . "'";
		$current_date = time();
		$query = "INSERT INTO seed_elements (name,type,data,user_id,creation_date) VALUES ($name,$type,$options_data,$user_id,$current_date)";
		if ($this->db->doQuery($query)) { 
			return mysql_insert_id();
		} else {
			return false;
		}
	}

} // END class 
?>