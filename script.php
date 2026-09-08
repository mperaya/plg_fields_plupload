<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;

final class PlgFieldsPluploadInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '6.0';
    protected $minimumPhp = '8.2';

    protected $deleteFiles = [
        '/plugins/fields/plupload/plupload.php',
        '/plugins/fields/plupload/fields/plupload.php',
        '/plugins/fields/plupload/fields/pluploadmeter.php',
    ];

    protected $deleteFolders = [
        '/plugins/fields/plupload/fields',
    ];

    public function install($parent)
    {
        $this->initialiseDefaultMimeTypes($parent);
    }

    public function update($parent)
    {
        $this->removeFiles();
        $this->initialiseDefaultMimeTypes($parent);
    }

    /**
     * Store the translated default mime types when the plugin has no value yet.
     *
     * Existing configuration is deliberately left untouched.
     *
     * @return void
     */
    private function initialiseDefaultMimeTypes($parent)
    {
        $default = Text::_('PLG_FIELDS_PLUPLOAD_MIME_TYPES_DEFAULT');

        if ($default === 'PLG_FIELDS_PLUPLOAD_MIME_TYPES_DEFAULT' || json_decode($default, true) === null) {
            return;
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('fields'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($parent->getElement()));

        $extensionId = (int) $db->setQuery($query)->loadResult();

        if (!$extensionId) {
            return;
        }

        $mimeTypes = $this->getParam('mime_types', $extensionId);

        if ($this->isEmptyMimeTypes($mimeTypes)) {
            $this->setParams(['mime_types' => $default], 'edit', $extensionId);
        }
    }

    /**
     * Determine whether a stored mime_types value is effectively empty.
     *
     * @param mixed $value Stored parameter value.
     *
     * @return bool
     */
    private function isEmptyMimeTypes($value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return in_array(trim((string) $value), ['', '[]', '{}', 'PLG_FIELDS_PLUPLOAD_MIME_TYPES_DEFAULT'], true);
    }
}
