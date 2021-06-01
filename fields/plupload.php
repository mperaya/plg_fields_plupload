<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

//use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
/**
 * Provides a modal media selector for upload big files.
 */
class JFormFieldPlupload extends FormField
{
	/**
	 * The form field type.
	 * @var    string
	 */
	protected $type = 'Plupload';

	/**
	 * The authorField.
	 * @var    string
	 */
	protected $authorField;

	/**
	 * The asset.
	 * @var    string
	 */
	protected $asset;

	/**
	 * The link.
	 * @var    string
	 */
	protected $link;

	/**
	 * Modal width.
	 * @var    integer
	 */
	protected $width;

	/**
	 * Modal height.
	 * @var    integer
	 */
	protected $height;

	/**
	 * The upload path.
	 * @var    string
	 */
	protected $upload_path;

	/**
	 * The dynamic upload path.
	 * @var    string
	 */
	protected $upload_field;

	/**
	 * The mime types.
	 * @var    object
	 */
	protected $mime_types;

	/**
	 * Prevent upload duplicates.
	 * @var    bool
	 */
	protected $prevent_duplicates;

	/**
	 * Multiple file upload at once.
	 * @var    bool
	 */
	protected $multiple_uploads;

	/**
	 * File size limit.
	 * @var    integer
	 */
	protected $max_file_size;

	/**
	 * Scope of the field. com_fields or component model custom field.
	 * @var    string
	 */
	protected $scope;

	/**
	 * Layout to render
	 * @var    string
	 */
	protected $layout = 'plupload-single';

//	public function __construct($form = null) {
//		parent::__construct($form);
//
//		//print_r($this->params->get('multiple_uploads'));
//		
//		$plugin = Joomla\CMS\Plugin\PluginHelper::getPlugin('fields', 'plupload');
//		// Check if plugin is enabled
//		if ($plugin)
//		{
//		    // Get plugin params
//		    $pluginParams = new JRegistry($plugin->params);
//		}
//
//	}
	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 * @param   string  $name  The property name for which to get the value.
	 * @return  mixed  The property value or null.
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'layout':
			case 'upload_path':
			case 'upload_field';
			case 'scope':
			case 'max_file_size':
			case 'prevent_duplicates':
			case 'multiple_uploads':
			case 'width':
			case 'height':
				return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 * @param   string  $name   The property name for which to set the value.
	 * @param   mixed   $value  The value of the property.
	 * @return  void
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'layout':
			case 'upload_path':
			case 'upload_field';
			case 'scope':
				$this->$name = (string) $value;
				break;
			case 'max_file_size':
			case 'prevent_duplicates':
			case 'multiple_uploads':
			case 'width':
			case 'height':
				$this->$name = (int) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 * @return  boolean  True on success.
	 * @see 	FormField::setup()
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$result = parent::setup($element, $value, $group);
		if ($result === true)
		{
			$this->scope              = ($this->__get('group') == 'com_fields') ? 'com_fields' . '.' . $this->form->getName() : $this->form->getName() . '.' . $this->getAttribute('name');
			$scope = explode('.',$this->scope);
			$this->groups  	          = $this->element['groups'];
			$this->multiple_uploads   = (string) $this->element['multiple_uploads'];
			$this->upload_field       = (string) $this->element['upload_field'];
			$this->upload_path        = (string) $this->element['upload_path'];
			if ($this->upload_field != '') {
				$upload_field = $this->upload_field;
				if ($this->form->getField($this->upload_field)) {
					$dynamic_upload_path = $this->form->getValue($this->upload_field);
				} else {
					$data = $this->form->getData()->get('com_fields');
					if (is_object($data) && key_exists($upload_field, (array) $data)) {
						$dynamic_upload_path = $data->$upload_field;
					} else {
						$dynamic_upload_path = '';
					}
				}
				if ($dynamic_upload_path != '') {
					if (is_dir($dynamic_upload_path)) {
						$this->upload_path = $dynamic_upload_path;
					}
				}
			}
			$this->max_file_size      = (int) $this->element['max_file_size'];
			$this->prevent_duplicates = (int) $this->element['prevent_duplicates'];

			$json = \Joomla\Registry\Factory::getFormat('json');		
			$this->mime_types         = $json->stringToObject($this->element['mime_types']);
			$this->width  	          = isset($this->element['width']) ? (int) $this->element['width'] : 640;
			$this->height 	          = isset($this->element['height']) ? (int) $this->element['height'] : 384;

			if($this->multiple_uploads) {
				$this->layout = 'plupload-multiple';
			}
		}

		return $result;
	}

	/**
	 * Method to get the field input markup for a media selector.
	 * Use attributes to identify specific created_by and asset_id fields
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		if (empty($this->layout))
		{
			throw new \UnexpectedValueException(sprintf(Text::_('PLG_FIELDS_PLUPLOAD_NO_LAYOUT'), $this->name));
		}

		return $this->getRenderer($this->layout)->render($this->getLayoutData());
	}

	protected function getRenderer($layoutId = 'default')
	{
		$renderer = parent::getRenderer($layoutId);

		if ($layoutId == $this->layout) {
			$renderer->addIncludePath(__DIR__ . '/../layouts');
		}

		return $renderer;
	}

	/**
	 * Get the data that is going to be passed to the layout
	 *
	 * @return  array
	 */
	public function getLayoutData()
	{
		// Get the basic field data
		$data = parent::getLayoutData();

		$extraData = array(
			'upload_path'        => $this->upload_path,
			'upload_field'       => $this->upload_field,
			'max_file_size'      => $this->max_file_size,
			'prevent_duplicates' => $this->prevent_duplicates,
			'mime_types'         => static::parseMimeTypes($this->mime_types),
			'width'              => $this->width,
			'height'             => $this->height,
			'scope'              => $this->scope,
			'groups'             => $this->groups,
			'lang'               => $this->getLang(),
			'access'             => $this->hasAccess(),
		);

		return array_merge($data, $extraData);
	}

	public function hasAccess()
	{
		$user = Factory::getUser();
		$access = false;
		
		if ($user->get('isRoot')) {
			$access = true;
		} else {
			$groups = explode(',', (string) $this->groups);
			foreach($groups as $group) {
				if (in_array($group, $user->getAuthorisedGroups())) {
					$access = true;
				}
			}
		}
		return $access;
	}

	public function getLang()
	{
		$lang = Factory::getLanguage()->getTag();
		switch ($lang) {
			case 'ku_IQ':
			case 'pt_BR':
			case 'th_TH':
			case 'uk_UA':
			case 'zh_CN':
			case 'zh_TW':
				return $lang;
				break;
			default :
				if (strlen($lang)>2) {
					if (count($a = explode('-',$lang)) > 1) {
						return $a[0];
					}
					if (count($a = explode('_',$lang)) > 1) {
						return $a[0];
					}
				} else {
					return $lang;
				}
				break;
		}
	}
	
	static function parseMimeTypes($mime_types)
	{
		$extensions = array();
		
		foreach ($mime_types as $type) {
			$extensions[] = '{title : "' .  $type->title . '", extensions : "' . $type->extensions . '"}';
		}
		
		return implode(',', $extensions);
	}
}
