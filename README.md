# PLUpload Joomla 6 custom field

PLUpload integration for Joomla 6. The plugin provides a `plupload` form field
that supports single-file and multiple-file uploads, chunked transfers, MIME
restrictions, duplicate detection, and configurable storage paths.

## Requirements

- Joomla 6.x
- PHP 8.2 or later
- The plugin must be installed and enabled.

## Installation and updates

Install the plugin package through Joomla's Extension Manager and enable
**Fields - PLUpload**. The package registers its CSS, JavaScript, language
files, and Joomla web assets through the standard Web Asset Manager.

The installer preserves existing plugin settings. In particular, the default
MIME restrictions are initialized only when `mime_types` is empty.

## Plugin options

### Storage

- `storage_mode="legacy"`: use the configured filesystem path.
- `storage_mode="media_manager"`: store files below Joomla's `images`
  directory.
- `media_subpath`: relative path below `images` when using `media_manager`.
  Nested paths are supported, for example `plupload/records`.
- `upload_path`: base filesystem path for legacy storage. It must exist and be
  writable by the web-server user unless the field offers directory creation.

Paths must be relative where a relative path is expected. Absolute paths and
path traversal using `..` are rejected for dynamic subpaths.

### Upload mode

- `multiple_uploads=0`: single-file upload with a progress dialog.
- `multiple_uploads=1`: multiple-file queue using the native PLUpload
  interface.
- `prevent_duplicates`: prevent duplicate entries in a multiple-file queue.
- `max_file_size`: maximum file size in megabytes. `0` means no plugin limit.

### MIME restrictions

`mime_restriction` accepts:

- `joomla`: use Joomla Media's permitted extensions and MIME rules.
- `custom`: use the field's `mime_types` configuration.
- `both`: accept a file when it is accepted by either Joomla or the custom
  restriction set.

When custom MIME checking is enabled, custom MIME values can be supplied in
the `mime_types` subform. The custom extension and MIME checks are independent
of Joomla's restriction setting.

### Permissions

- `upload_groups`: groups allowed to upload.
- `download_groups`: groups allowed to download.
- File deletion from single-upload fields is restricted to super users.

## Dynamic upload paths

`upload_field` can reference another field in the same form. Its value is
treated as a safe relative subpath and appended to `upload_path`.

For example, if:

```text
upload_path = /var/www/site/uploads
upload_field = directory
directory = articles/2026
```

the target directory becomes `/var/www/site/uploads/articles/2026`.

The value must not be absolute, contain a Windows drive prefix, or contain a
path traversal segment. For media storage, the resulting path remains below
the configured `media_subpath`.

## Using the field as a Joomla custom field

Create a Joomla custom field of type **PLUpload** and configure its options in
the field editor. The plugin automatically registers the required assets.

## Using the field directly in an XML form

Add the PLUpload field prefix and define the field in the form XML:

```xml
<field
    name="file_name"
    type="plupload"
    label="COM_EXAMPLE_FILE_NAME_LABEL"
    description="COM_EXAMPLE_FILE_NAME_DESC"
    addfieldprefix="Mayala\Plugin\Fields\Plupload\Field"
    storage_mode="media_manager"
    media_subpath="plupload/records"
    upload_path="/tmp"
    multiple_uploads="0"
    mime_restriction="joomla"
    max_file_size="0"
    upload_groups="8"
    download_groups="2"
    class="col-md-8"
/>
```

For multiple uploads, change only the mode and add the duplicate option:

```xml
<field
    name="files"
    type="plupload"
    label="COM_EXAMPLE_FILES_LABEL"
    addfieldprefix="Mayala\Plugin\Fields\Plupload\Field"
    storage_mode="media_manager"
    media_subpath="plupload/records"
    upload_path="/tmp"
    multiple_uploads="1"
    prevent_duplicates="1"
    mime_restriction="joomla"
/>
```

Direct XML fields use the same assets, validation, path checks, duplicate
handling, and upload dialogs as Joomla custom-field instances. Manual loading
of the plugin's CSS or JavaScript is not required.

## Duplicate files

Before uploading, the field checks whether the sanitized target filename
already exists. The user can cancel, keep the existing file, or explicitly
confirm overwriting it. The multiple-file interface also allows a duplicate
entry to be removed from the queue.

## Release contents

The install manifest includes the plugin PHP sources, layouts, language files,
parameters, templates, and the `media/plg_fields_plupload` directory. The
media directory contains the Joomla asset manifest, CSS, PLUpload libraries,
and the plugin-specific JavaScript files:

- `plupload-field.js`
- `plupload-single.js`
- `plupload-multiple.js`

The update manifest must point to a tagged GitHub release whose ZIP filename
matches the version advertised by the update server.

## License

GNU Affero General Public License version 3.
