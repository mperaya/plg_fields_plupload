# plg_fields_plupload
<h3>PLUpload Joomla 4 integration as custom form field.</h3>
<p>You can add this custom field to any joomla components that support the 
joomla custom fields system and this will display a button for multiple 
file upload with the only size limit of the "Max. file size (MB)" param, or
none if set to 0.</p>
<p>If you set "Multiple file upload" as "no", you will have a field for store the
uploaded file name, and you can define another field as the destination
target for the uploaded file.</p>
<p>In either cases the upload will be made with the plupload js library
and plupload php upload handler for allow uploading of files with sizes
many times bigger than the php upload limit permit. Even you can upload
files of many GigaBytes in size.</p>
<p>You can also include this custom field in your component or any other extension.</p>
<p>The only need is to install the plugin, enable it, and add this to your extension
in the extension ".php" file before any call to render or show any form.</p>
<code>
if(is_dir(JPATH_PLUGINS . '/fields/plupload')) {
	Form::addFieldPath(JPATH_PLUGINS . '/fields/plupload/fields');
}
</code>
<br />
<br />
<p>Those are the plugin field params that you can configure on the field config
of the field added to the component as any standard custom field, or you can 
use it as attributes of the field if you add the field to any xml form you want to
include it.</p>
<dl>
	<dt>Option label: Upload path<br /><code>Option name: upload_path</code></dt>
	<dd>
		This is a required value. In it yo can set the default upload base path
		for any file you want to upload. It must be a real path to any folder of your site
		and must to be writeable for the www server user of your system.
	</dd>
	<dt>Option label: Multiple uploads<br /><code>Option name: multiple_uploads</code></dt>
	<dd>
		If yo leave this as no or false, you will have a field with a button for select and
		upload a unique file. If you set this as Yes or true, you will have an upload button
		to show a multiple file upload dialog of PLUpload
	</dd>
	<dt>Option label: Prevent duplicates<br /><code>Option name: prevent_duplicates</code></dt>
	<dd>
		If you have set the multiple_uploads as Yes or true, you will have a checkbox  to 
		prevent selection of duplicate files for upload.
	</dd>
	<dt>Option label: Upload field<br /><code>Option name: upload_field</code></dt>
	<dd>
		If you have set the multiple_uploads as No or false, you will have a text box for
		type an optional field name from where to define an additional relative path for 
		uploads. This way the upload path for the current uploaded file will be the result
		of concatenate the upload_path content plus the content from the defined upload_field
		of the current element. If the combined path not exists or does not have write
		permissions, only the base upload_path will be used.
	</dd>
	<dt>Option label: Mime types<br /><code>Option name: mime_types</code></dt>
	<dd>
		This is a subform joomla field where you can define as many mime types by extension
		as you want, grouped or not but a descriptive File kind descriptor. You can include 
		an extension in every line or add many extensions with ',' as separator in one line.
	</dd>
	<dt>Option label: Max. file size (MB)<br /><code>Option name: max_file_size</code></dt>
	<dd>
		In this option you can set if there are no limit to uploaded file sizes if you leave it
		as cero, or you can limit the max. file size of uploaded files if you define another value.
	</dd>
	<dt>Option label: Width (vh)<br /><code>Option name: width</code></dt>
	<dd>
		You can change the upload dialog width in viewport units if you change this option.
	</dd>
	<dt>Option label: Height (vh)<br /><code>Option name: height</code></dt>
	<dd>
		You can change the upload dialog height in viewport units if you change this option.
	</dd>
	<dt>Option label: Groups<br /><code>Option name: groups</code></dt>
	<dd>
		You can define as many joomla groups as you want to allow its members 
		to upload files with this field.
	</dd>
</dl>
