<?php
/**
 * @package PLUpload for Joomla
 * @copyright  (C) 2022 Manuel P. Ayala. All rights reserved
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

defined('_JEXEC') or die;

use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

use Mayala\Plugin\Fields\Plupload\PluploadHandler;
use Mayala\Plugin\Fields\Plupload\Field\PluploadField;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

/**
 * Plupload Plugin
 */
class PlgFieldsPlupload extends FieldsPlugin
{
	public function  onAjaxPlupload()
        {
		if (Factory::getUser()->get('id') != 0) {
			$params = $this->getParams();

			$ph = new PluploadHandler(array(
				'target_dir' => $params->upload_path,
				'allow_extensions' => static::parseMimeExt($params->mime_types),
				'upload_field' => $params->upload_field,
			));

			$ph->sendNoCacheHeaders();
			$ph->sendCORSHeaders();

			if (($result = $ph->handleUpload())) {
				$response = new JsonResponse(array('info' => $result), $result);
				die($response);
			} else {
				$response = new JsonResponse(array('code' => $ph->getErrorCode(),'message' => $ph->getErrorMessage()), $ph->getErrorMessage(), true);
				die($response);
			}
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
		$p1 = Factory::getApplication()->input->get('params');
		$p2 = base64_decode($p1);
		$p3 = urldecode($p2);
		$params = json_decode($p3);

		$scope = explode('.',$params->scope);

		$json = \Joomla\Registry\Factory::getFormat('json');

		if ($scope[0] == 'com_fields') {
			$fields = FieldsHelper::getFields($scope[1] . '.' . $scope[2]);
			foreach ($fields as $field) {
				if ($field->type == 'plupload') {
					$params->mime_types = $field->fieldparams->get('mime_types');
					$params->groups = $field->fieldparams->get('groups');
					break;
				}
			}
		} else {
			$xml_path = '/components/' . $scope[0] . '/forms/' . $scope[1] . '.xml';
			if(file_exists(JPATH_ROOT . $xml_path)) {
				$xml_path = JPATH_ROOT . $xml_path;
			} else if(file_exists(JPATH_ADMINISTRATOR . $xml_path)) {
				$xml_path = JPATH_ADMINISTRATOR . $xml_path;
			}
			if (($form = Form::getInstance($scope[1], $xml_path))) {
				if ( ($field = $form->getField($scope[2])) ) {
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
