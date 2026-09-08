<?php

namespace Mayala\Plugin\Fields\Plupload\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;
use Joomla\Event\SubscriberInterface;
use Mayala\Plugin\Fields\Plupload\Field\PluploadField;
use Mayala\Plugin\Fields\Plupload\PluploadHandler;

final class Plupload extends FieldsPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onAfterInitialise' => 'onAfterInitialise',
            'onAjaxPlupload' => 'onAjaxPlupload',
        ]);
    }

    public function onAfterInitialise(): void
    {
        $this->registerAssets();
    }

    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form): ?\DOMElement
    {
        $node = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if ($node) {
            $node->setAttribute('addfieldprefix', 'Mayala\\Plugin\\Fields\\Plupload\\Field');

            if (strtolower($node->getAttribute('type')) === 'plupload') {
                $this->registerAssets();
            }
        }

        return $node;
    }

    private function registerAssets(): void
    {
        PluploadField::registerAssets();
    }

    public function onAjaxPlupload(): void
    {
        $user = $this->getApplication()->getIdentity();

        if (!$user || !$user->get('id')) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_SECURITY_ERR');
        }

        $params = $this->resolveRequestParameters();
        $requestedAction = $this->getApplication()->getInput()->getCmd('action', '');
        $action = $requestedAction !== '' ? $requestedAction : (string) ($params->action ?? '');

        if (!in_array($action, ['upload', 'delete', 'download', 'check_directory', 'create_directory', 'check_file'], true)) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_UNKNOWN_ERR');
        }

        if (!hash_equals(Session::getFormToken(), (string) ($params->token ?? ''))) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_SECURITY_ERR');
        }

        if (!$this->hasAccess($action, $params)) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_SECURITY_ERR');
        }

        $targetDirectory = $this->resolveTargetDirectory($params);

        if ($action === 'check_file') {
            $fileName = PluploadHandler::sanitizeFileName(
                (string) $this->getApplication()->getInput()->get('name', '', 'raw')
            );

            if ($fileName === '') {
                $this->sendError('PLG_FIELDS_PLUPLOAD_INPUT_ERR');
            }

            if (is_file(rtrim($targetDirectory, '/\\') . '/' . $fileName)) {
                $this->sendError('PLG_FIELDS_PLUPLOAD_FILE_EXIST_ERR', PLUPLOAD_FILE_EXIST_ERR, ['can_overwrite' => true]);
            }

            die(new JsonResponse(['info' => ['name' => $fileName]]));
        }

        if (in_array($action, ['check_directory', 'create_directory'], true)) {
            $scope = explode('.', (string) ($params->scope ?? ''), 2);
            $isDirectField = ($scope[0] ?? '') !== 'com_fields';

            if ($action === 'check_directory' && !is_dir($targetDirectory)) {
                if ($isDirectField
                    && !@mkdir($targetDirectory, 0755, true)
                    && !is_dir($targetDirectory)) {
                    $this->sendError('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATE_ERR');
                }

                if ($isDirectField) {
                    die(new JsonResponse(['info' => ['directory' => $targetDirectory]]));
                }

                $this->sendError(
                    'PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_NOT_FOUND',
                    null,
                    ['can_create' => !file_exists($targetDirectory)]
                );
            }

            if ($action === 'create_directory' && !is_dir($targetDirectory)
                && !@mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                $this->sendError('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATE_ERR');
            }

            if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
                $this->sendError('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_NOT_WRITABLE');
            }

            die(new JsonResponse(['info' => ['directory' => $targetDirectory]]));
        }

        $restriction = self::normaliseMimeRestriction((string) ($params->mime_restriction ?? 'joomla'));
        $handler = new PluploadHandler([
            'action'          => $action,
            'target_dir'      => $targetDirectory,
            'file_name'       => (string) ($params->file_name ?? $this->getApplication()->getInput()->get('name', '', 'raw')),
            'overwrite'       => $this->getApplication()->getInput()->getBool('overwrite', false),
            'allow_extensions'=> false,
            'restriction_mode' => $restriction,
            'custom_mime_types' => $params->custom_mime_types ?? [],
            'custom_check_mime' => (bool) ($params->custom_check_mime ?? false),
            'chunk'           => (int) $this->getApplication()->getInput()->getInt('chunk', 0),
            'chunks'          => (int) $this->getApplication()->getInput()->getInt('chunks', 0),
            'input_file'      => $this->getApplication()->getInput()->files->get('file'),
        ]);

        if ($action === 'upload') {
            $handler->sendNoCacheHeaders();
            $handler->sendCORSHeaders();
            $result = $handler->handleUpload();
        } elseif ($action === 'delete') {
            $result = $handler->purge();
        } else {
            if ($handler->download() === false) {
                $this->sendError($handler->getErrorMessage(), $handler->getErrorCode());
            }
            return;
        }

        if ($result === false) {
            $this->sendError($handler->getErrorMessage(), $handler->getErrorCode());
        }

        die(new JsonResponse(['info' => $result], $result));
    }

    private function resolveRequestParameters(): \stdClass
    {
        $encoded = (string) $this->getApplication()->getInput()->get('params', '', 'raw');
        $decoded = base64_decode($encoded, true);
        $data = json_decode(urldecode($decoded ?: ''), false, 512, JSON_THROW_ON_ERROR);
        $scope = explode('.', (string) ($data->scope ?? ''), 3);
        $isDirectField = ($scope[0] ?? '') !== 'com_fields';

        if (count($scope) < 3) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_SECURITY_ERR');
        }

        $field = null;
        if ($scope[0] === 'com_fields') {
            foreach (FieldsHelper::getFields($scope[1] . '.' . $scope[2]) as $candidate) {
                if ($candidate->type === 'plupload') {
                    $field = $candidate;
                    break;
                }
            }
        } else {
            $path = JPATH_ROOT . '/components/' . $scope[0] . '/forms/' . $scope[1] . '.xml';
            if (!is_file($path)) {
                $path = JPATH_ADMINISTRATOR . '/components/' . $scope[0] . '/forms/' . $scope[1] . '.xml';
            }
            if (is_file($path)) {
                $form = Form::getInstance($scope[1], $path);
                $field = $form->getField($scope[2]);
            }
        }

        if (!$field) {
            $this->sendError('PLG_FIELDS_PLUPLOAD_SECURITY_ERR');
        }

        $fieldParams = isset($field->fieldparams) ? $field->fieldparams : null;
        $get = static function ($name, $default = null) use ($field, $fieldParams) {
            $attribute = method_exists($field, 'getAttribute')
                ? $field->getAttribute($name, null)
                : ($field->{$name} ?? null);

            if ($attribute !== null && (string) $attribute !== '') {
                return $attribute;
            }

            if ($fieldParams && method_exists($fieldParams, 'get')) {
                return $fieldParams->get($name, $default);
            }

            if (is_array($fieldParams) && array_key_exists($name, $fieldParams)) {
                return $fieldParams[$name];
            }

            if (is_object($fieldParams) && isset($fieldParams->{$name})) {
                return $fieldParams->{$name};
            }

            return $default;
        };

        $configuredPath = (string) $get('upload_path', $this->params->get('upload_path', ''));
        $requestedPath = (string) ($data->upload_path ?? '');
        $data->base_upload_path = $configuredPath;
        $data->upload_path = $requestedPath !== '' ? $requestedPath : $configuredPath;
        $data->upload_field = (string) $get('upload_field', $this->params->get('upload_field', ''));
        $data->storage_mode = (string) ($data->storage_mode ?? $get('storage_mode', $this->params->get('storage_mode', 'legacy')));
        $data->media_subpath = (string) ($data->media_subpath ?? $get('media_subpath', $this->params->get('media_subpath', 'plupload')));
        $legacyUseJoomlaMime = $get('use_joomla_mime', null);
        $configuredRestriction = $get('mime_restriction', null);
        if ($configuredRestriction === null || $configuredRestriction === '') {
            $configuredRestriction = $legacyUseJoomlaMime === null
                ? ($isDirectField
                    ? 'joomla'
                    : ($this->params->get('mime_restriction', null)
                        ?? ((int) $this->params->get('use_joomla_mime', 1) ? 'joomla' : 'custom')))
                : ((int) $legacyUseJoomlaMime ? 'joomla' : 'custom');

            if (!$isDirectField && $legacyUseJoomlaMime === null && $this->params->get('mime_restriction', null) === null
                && $get('mime_types', null) !== null) {
                $configuredRestriction = 'custom';
            }
        }
        $data->mime_restriction = self::normaliseMimeRestriction((string) $configuredRestriction);
        $data->custom_check_mime = (bool) $get('custom_check_mime', 0);
        $data->custom_mime_types = self::normaliseMimeTypes($get('mime_types', $this->params->get('mime_types', [])));
        $data->mime_types = PluploadField::getMimeTypesForRestriction(
            $data->mime_restriction,
            $data->custom_mime_types
        );
        $data->upload_groups = self::normaliseGroups($get('upload_groups', $this->params->get('upload_groups', [])));
        $data->download_groups = self::normaliseGroups($get('download_groups', $this->params->get('download_groups', [])));

        return $data;
    }

    private function hasAccess(string $action, \stdClass $params): bool
    {
        if ($action === 'delete') {
            return (bool) $this->getApplication()->getIdentity()->get('isRoot');
        }

        $groups = $action === 'download' ? $params->download_groups : $params->upload_groups;
        $user = $this->getApplication()->getIdentity();
        $scope = explode('.', (string) ($params->scope ?? ''), 2);
        $component = $scope[0] ?? '';

        return $user->get('isRoot')
            || ($component !== '' && $user->authorise('core.edit', $component))
            || array_intersect($groups, $user->getAuthorisedGroups()) !== [];
    }

    private function resolveTargetDirectory(\stdClass $params): string
    {
        if (($params->storage_mode ?? 'legacy') === 'media_manager') {
            $subpath = trim(str_replace('..', '', (string) ($params->media_subpath ?? 'plupload')), '/\\');
            return rtrim(JPATH_ROOT . '/images/' . $subpath, '/\\');
        }

        $base = rtrim((string) ($params->base_upload_path ?? $params->upload_path ?? ''), '/\\');

        if (($params->action ?? '') === 'download' && isset($params->file_path)) {
            $relative = trim(str_replace('\\', '/', (string) $params->file_path), '/');

            if ($relative === '' || str_contains('/' . $relative . '/', '/../')) {
                return '';
            }

            return $base . '/' . $relative;
        }

        $requested = rtrim((string) ($params->upload_path ?? ''), '/\\');

        if ($requested !== '' && $base !== ''
            && self::isSafePath($requested)
            && self::isPathWithin($requested, $base)) {
            return $requested;
        }

        return $base;
    }

    private function sendError(string $message, ?int $code = null, array $details = []): never
    {
        $message = str_starts_with($message, 'PLG_') ? Text::_($message) : $message;
        $response = new JsonResponse(array_merge(['code' => $code, 'message' => $message], $details), $message, true);
        $application = $this->getApplication();
        $application->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo (string) $response;
        $application->close();
    }

    private static function parseMimeExtensions($mimeTypes): string
    {
        $extensions = [];
        foreach ((array) $mimeTypes as $type) {
            if (is_array($type)) {
                $value = $type['extensions'] ?? '';
            } elseif (is_object($type)) {
                $value = $type->extensions ?? '';
            } else {
                $value = '';
            }

            $extensions = array_merge($extensions, explode(',', (string) $value));
        }

        return implode(',', array_filter(array_map('trim', $extensions)));
    }

    private static function normaliseMimeRestriction(string $restriction): string
    {
        return in_array($restriction, ['joomla', 'custom', 'both'], true) ? $restriction : 'joomla';
    }

    private static function normaliseMimeTypes($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, false);
        }

        return is_object($value) ? array_values(get_object_vars($value)) : (array) $value;
    }

    private static function normaliseGroups($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return array_values(array_filter(array_map('strval', (array) $value), static fn ($group) => $group !== ''));
    }

    private static function isPathWithin(string $path, string $base): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $base = rtrim(str_replace('\\', '/', $base), '/');
        return $path === $base || str_starts_with($path, $base . '/');
    }

    private static function isSafePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return !preg_match('~(^|/)\.\.(/|$)~', $path)
            && !str_contains($path, "\0");
    }
}
