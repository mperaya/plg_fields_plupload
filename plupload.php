<?php
/**
 * Plupload Plugin
 *
 * @copyright  (C) 2020 Manuel P. Ayala. All rights reserved
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Format\Json;

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
JLoader::import('pluploadhandler',__DIR__);

/**
 * Plupload Plugin
 */
class PlgFieldsPlupload extends FieldsPlugin
{
	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 * @param   stdClass    $field   The field.
	 * @param   DOMElement  $parent  The field node parent.
	 * @param   JForm       $form    The form.
	 * @return  DOMElement
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);
		if (!$fieldNode) {
			return $fieldNode;
		}

		$fieldNode->setAttribute('validate', 'options');
		if (!empty($mime_types = $field->fieldparams->get('mime_types'))) {
			$fieldNode->setAttribute('mime_types',json_encode($field->fieldparams->get('mime_types')));
		}

		$textValue = htmlspecialchars(Text::_($this->getTextValue($field)), ENT_COMPAT, 'UTF-8');

		$option = new DOMElement('option');
		$option->nodeValue = $textValue;
		$element = $fieldNode->appendChild($option);
		$element->setAttribute('value', 'plupload');

// Para imprimir un domobject completo
//echo "<pre>4#".print_r(htmlspecialchars($fieldNode->ownerDocument->saveXML($fieldNode)),true)."#</pre>";

		return $fieldNode;
	}

	/**
	 * Returns the text value from the given field.
	 * @param   stdClass  $field  The field.
	 * @return  string
	 */
	public function getTextValue($field)
	{
		// Fetch the options from the plugin
		$params = clone $this->params;
		$params->merge($field->fieldparams);

		return strip_tags($params->get('textvalue', ''), '<a>');
	}

	public function  onAjaxPlupload()
        {
		if (Factory::getUser()->get('id') != 0) {

			$params = $this->getParams();
//			$params->log_path = '/tmp/otro.log';
//error_log(print_r($params,true)."\n",3,'/tmp/error.log');

			$ph = new PluploadHandler(array(
				'target_dir' => $params->upload_path,
				'allow_extensions' => static::parseMimeExt($params->mime_types),
				'log_path' => $params->log_path,
			));
			$ph->sendNoCacheHeaders();
			$ph->sendCORSHeaders();

			if ($result = $ph->handleUpload()) {
				die(json_encode(array(
					'OK' => 1,
					'info' => $result
				)));
			} else {
//				header('HTTP/1.1 400');
				header('Content-Type: application/json');
				//http_response_code(500);
error_log(print_r($ph->getErrorCode(),true).":".print_r($ph->getErrorMessage(),true)."\n",3,'/tmp/error.log');
				die(json_encode(array(
					'OK' => 0,
					'error' => array(
						'code' => $ph->getErrorCode(),
						'message' => $ph->getErrorMessage()
					)
				)));
			}
		} else {
error_log("else:\n",3,'/tmp/error.log');
			die(json_encode(array(
				'OK' => 0,
				'error' => array(
					'code' => $ph->getErrorCode(),
					'message' => $ph->getErrorMessage()
				)
			)));
		}
	}
	
	public function hasAccess()
	{
		$user = Factory::getUser();
		$access = false;
		
		if ($user->get('isRoot')) {
			$access = true;
		} else {
			$groups = $this->getParams()->groups;
			foreach($groups as $group) {
				if (in_array($group, $user->getAuthorisedGroups())) {
					$access = true;
				}
			}
		}
		return $access;
	}

	private function getParams()
	{
//$headers =  getallheaders();
//foreach($headers as $key=>$val){
//	error_log(print_r($key . ': ' . $val,true)."\n",3,'/tmp/error.log');
//}
//	error_log(print_r($_POST,true)."\n",3,'/tmp/error.log');
//	error_log(print_r($_GET,true)."\n",3,'/tmp/error.log');
//die();
		$params = json_decode(urldecode(base64_decode(Factory::getApplication()->input->get('params'))));

		$scope = explode('.',$params->scope);

		$json = \Joomla\Registry\Factory::getFormat('json');

		if ($scope[0] == 'com_fields') {
//com_fields.com_content.article
			$fields = FieldsHelper::getFields($scope[1] . '.' . $scope[2]);
			foreach ($fields as $field) {
				if ($field->type == 'plupload') {
					$params->mime_types = $field->fieldparams->get('mime_types');
					$params->groups = $field->fieldparams->get('groups');
					break;
				}
			}
		} else {
//com_uexmngr.record.file_path
			$xml_path = '/components/' . $scope[0] . '/models/forms/' . $scope[1] . '.xml';
			if(file_exists(JPATH_ROOT . $xml_path)) {
				$xml_path = JPATH_ROOT . $xml_path;
			} else if(file_exists(JPATH_ADMINISTRATOR . $xml_path)) {
				$xml_path = JPATH_ADMINISTRATOR . $xml_path;
			}
			if ($form = Form::getInstance($scope[1], $xml_path)) {
				if ($field = $form->getField($scope[2]) ) {
					$params->mime_types = $json->stringToObject($field->getAttribute('mime_types'));
					$params->groups = $field->getAttribute('groups');
				}
			}
		}
		if (count( (array) $params) == 0) {
			$params->mime_types = $this->params['mime_types'];
			$params->groups = $this->params['groups'];
		}

		return $params;
	}

	static function parseMimeExt($mime_types, $format = null)
	{
		if (count( (array) $mime_types) != 0) {
			$extensions = array();

			foreach ($mime_types as $type) {
				$extensions = array_merge($extensions, explode(',',$type->extensions));
			}
			if ($format == 'array') {
				return $extensions;
			} else {
				return implode(',', $extensions);
			}
		} else {
			return '';
		}
	}
	
	static function isBackend()
	{
		return Factory::getApplication()->getName() == 'administrator';
	}
	
}