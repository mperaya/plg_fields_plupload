<?php

namespace Mayala\Plugin\Fields\Plupload\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

final class PluploadField extends TextField
{
    private const CUSTOM_PROPERTIES = [
        'upload_path', 'upload_field', 'mime_types', 'mime_restriction', 'custom_check_mime', 'use_joomla_mime', 'upload_groups', 'download_groups',
        'prevent_duplicates', 'multiple_uploads', 'max_file_size', 'scope', 'width', 'height',
        'storage_mode', 'media_subpath',
    ];

    protected $type = 'Plupload';
    protected $upload_path = '';
    protected $upload_field = '';
    protected $mime_types = [];
    protected $mime_restriction = 'joomla';
    protected $custom_check_mime = 0;
    protected $use_joomla_mime = 1;
    protected $upload_groups = [];
    protected $download_groups = [];
    protected $prevent_duplicates = 0;
    protected $multiple_uploads = 0;
    protected $max_file_size = 0;
    protected $scope = '';
    protected $layout = 'plupload-single';
    protected $width = 0;
    protected $height = 0;
    protected $storage_mode = 'legacy';
    protected $media_subpath = 'plupload';

    public function __get($name)
    {
        if (in_array($name, self::CUSTOM_PROPERTIES, true)) {
            return $this->$name;
        }
        return parent::__get($name);
    }

    public function __set($name, $value): void
    {
        if (in_array($name, self::CUSTOM_PROPERTIES, true)) {
            $this->$name = $value;
            return;
        }
        parent::__set($name, $value);
    }

    public function setValue($value): void
    {
        parent::setValue($value);
        $this->layoutData = [];
    }

    public function setup(\SimpleXMLElement $element, $value, $group = null): bool
    {
        if (!parent::setup($element, $value, $group)) {
            return false;
        }

        $this->scope = $this->group === 'com_fields'
            ? 'com_fields.' . $this->form->getName()
            : $this->form->getName() . '.' . $this->getAttribute('name');
        $configuredPath = self::filterPath((string) ($element['upload_path'] ?? ''));
        $this->upload_path = $configuredPath === '' ? '' : '/' . $configuredPath;
        $this->upload_field = trim((string) ($element['upload_field'] ?? ''));
        $this->upload_groups = self::normaliseGroups((string) ($element['upload_groups'] ?? ''));
        $this->download_groups = self::normaliseGroups((string) ($element['download_groups'] ?? ''));
        $this->multiple_uploads = (int) ($element['multiple_uploads'] ?? 0);
        $this->prevent_duplicates = (int) ($element['prevent_duplicates'] ?? 0);
        $this->max_file_size = (int) ($element['max_file_size'] ?? 0);
        $this->width = (int) ($element['width'] ?? 0);
        $this->height = (int) ($element['height'] ?? 0);
        $this->storage_mode = trim((string) ($element['storage_mode'] ?? 'legacy')) ?: 'legacy';
        $this->media_subpath = trim((string) ($element['media_subpath'] ?? 'plupload'), '/\\') ?: 'plupload';
        $legacyUseJoomlaMime = $element['use_joomla_mime'] ?? null;
        $hasLegacyCustomMimeTypes = $this->group === 'com_fields' && (string) ($element['mime_types'] ?? '') !== '';
        $this->mime_restriction = self::normaliseMimeRestriction(
            (string) ($element['mime_restriction'] ?? ($legacyUseJoomlaMime === null
                ? ($hasLegacyCustomMimeTypes ? 'custom' : 'joomla')
                : ((int) $legacyUseJoomlaMime ? 'joomla' : 'custom')))
        );
        $this->custom_check_mime = (int) ($element['custom_check_mime'] ?? 0);
        $this->use_joomla_mime = $this->mime_restriction === 'joomla' ? 1 : 0;
        $this->mime_types = self::decodeMimeTypes((string) ($element['mime_types'] ?? ''));
        $this->checkUploadPath();
        $this->layout = $this->multiple_uploads ? 'plupload-multiple' : 'plupload-single';

        return true;
    }

    public function checkUploadPath(): void
    {
        if ($this->upload_field === '') {
            return;
        }
        $value = $this->form->getValue($this->upload_field);
        $dynamicPath = is_scalar($value) ? str_replace('\\', '/', trim((string) $value)) : '';

        if ($dynamicPath === '' || !self::isSafeRelativePath($dynamicPath)) {
            return;
        }

        if (self::filterPath($this->upload_path) === '') {
            return;
        }

        $this->upload_path = rtrim($this->upload_path, '/\\') . '/' . self::filterPath($dynamicPath);
    }

    public static function filterPath(?string $path): string
    {
        return trim((string) preg_replace(['/\/\/+/', '/^\//', '/\/$/'], ['/', '', ''], (string) $path));
    }

    private static function isSafeRelativePath(string $path): bool
    {
        return $path !== ''
            && !str_starts_with($path, '/')
            && !preg_match('~^[A-Za-z]:~', $path)
            && !preg_match('~(^|/)\.\.(/|$)~', $path)
            && !str_contains($path, "\0");
    }

    protected function getInput(): string
    {
        if ($this->layout === '') {
            throw new \UnexpectedValueException(sprintf(Text::_('PLG_FIELDS_PLUPLOAD_NO_LAYOUT'), $this->name));
        }
        self::registerAssets($this->getLang());
        return $this->getRenderer($this->layout)->render($this->getLayoutData());
    }

    public static function registerAssets(?string $locale = null): void
    {
        $wa = Factory::getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('plg_fields_plupload');
        $wa->useStyle('plg_fields_plupload.style')
            ->useStyle('plg_fields_plupload.jquery-ui.style')
            ->useStyle('plg_fields_plupload.ui.style')
            ->useScript('plg_fields_plupload.script')
            ->useScript('plg_fields_plupload.field')
            ->useScript('plg_fields_plupload.single')
            ->useScript('plg_fields_plupload.multiple');

        $locale ??= self::getLocale();
        $wa->registerAndUseScript(
            'plg_fields_plupload.i18n.' . $locale,
            'plg_fields_plupload/i18n/' . $locale . '.js',
            [],
            [],
            ['plg_fields_plupload.core']
        );
    }

    private static function getLocale(): string
    {
        $tag = Factory::getLanguage()->getTag();

        return in_array($tag, ['ku_IQ', 'pt_BR', 'th_TH', 'uk_UA', 'zh_CN', 'zh_TW'], true)
            ? $tag : substr(str_replace('_', '-', $tag), 0, 2);
    }

    protected function getRenderer($layoutId = 'default')
    {
        $this->loadLanguage();
        $renderer = parent::getRenderer($layoutId);
        $renderer->addIncludePath(JPATH_PLUGINS . '/fields/plupload/layouts');
        return $renderer;
    }

    public function loadLanguage(): bool
    {
        $language = Factory::getLanguage();
        $extension = 'plg_fields_plupload';

        return $language->load($extension, JPATH_ADMINISTRATOR, null, false, true)
            || $language->load($extension, JPATH_PLUGINS . '/fields/plupload', null, false, true);
    }

    public function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $user = Factory::getApplication()->getIdentity();
        $authorised = $user ? $user->getAuthorisedGroups() : [];
        return array_merge($data, [
            'upload_path' => $this->upload_path,
            'upload_field' => $this->upload_field,
            'max_file_size' => $this->max_file_size,
            'prevent_duplicates' => $this->prevent_duplicates,
            'mime_types' => self::parseMimeTypes(self::getMimeTypesForRestriction($this->mime_restriction, $this->mime_types)),
            'mime_extensions' => self::getMimeExtensions(self::getMimeTypesForRestriction($this->mime_restriction, $this->mime_types)),
            'mime_restriction' => $this->mime_restriction,
            'custom_check_mime' => $this->custom_check_mime,
            'use_joomla_mime' => $this->use_joomla_mime,
            'width' => $this->width,
            'height' => $this->height,
            'storage_mode' => $this->storage_mode,
            'media_subpath' => $this->media_subpath,
            'scope' => $this->scope,
            'upload_groups' => $this->upload_groups,
            'download_groups' => $this->download_groups,
            'upload_access' => $user && ($user->get('isRoot') || array_intersect($this->upload_groups, $authorised)),
            'download_access' => $user && ($user->get('isRoot') || array_intersect($this->download_groups, $authorised)),
            'lang' => $this->getLang(),
        ]);
    }

    public function getLang(): string
    {
        $tag = Factory::getLanguage()->getTag();
        return in_array($tag, ['ku_IQ', 'pt_BR', 'th_TH', 'uk_UA', 'zh_CN', 'zh_TW'], true)
            ? $tag : substr(str_replace('_', '-', $tag), 0, 2);
    }

    public static function getJoomlaMimeTypes(): array
    {
        $params = ComponentHelper::getParams('com_media');

        if (!$params->get('restrict_uploads', 1)) {
            return [];
        }

        $extensions = trim((string) $params->get('restrict_uploads_extensions', ''));

        if ($extensions === '') {
            $extensions = implode(',', array_filter([
                $params->get('image_extensions', ''),
                $params->get('audio_extensions', ''),
                $params->get('video_extensions', ''),
                $params->get('doc_extensions', ''),
            ]));
        }

        return $extensions === '' ? [] : [(object) ['title' => '', 'extensions' => $extensions]];
    }

    public static function getMimeTypesForRestriction(string $restriction, array $customMimeTypes): array
    {
        $restriction = self::normaliseMimeRestriction($restriction);

        if ($restriction === 'custom') {
            return $customMimeTypes;
        }

        if ($restriction === 'both') {
            return array_merge(self::getJoomlaMimeTypes(), $customMimeTypes);
        }

        return self::getJoomlaMimeTypes();
    }

    public static function getMimeExtensions(array $mimeTypes): array
    {
        $extensions = [];

        foreach ($mimeTypes as $type) {
            $value = is_array($type) ? ($type['extensions'] ?? '') : ($type->extensions ?? '');
            $extensions = array_merge($extensions, explode(',', (string) $value));
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($extension) => strtolower(trim($extension)),
            $extensions
        ))));
    }

    private static function normaliseMimeRestriction(string $restriction): string
    {
        return in_array($restriction, ['joomla', 'custom', 'both'], true) ? $restriction : 'joomla';
    }

    private static function decodeMimeTypes(string $value): array
    {
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, false);
        return is_array($decoded) ? $decoded : (is_object($decoded) ? get_object_vars($decoded) : []);
    }

    private static function normaliseGroups(string $groups): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $groups)), static fn ($group) => $group !== ''));
    }

    private static function parseMimeTypes(array $mimeTypes): string
    {
        $result = [];
        foreach ($mimeTypes as $type) {
            $result[] = '{title: "' . addslashes((string) ($type->title ?? '')) . '", extensions: "'
                . addslashes((string) ($type->extensions ?? '')) . '"}';
        }
        return implode(',', $result);
    }
}
