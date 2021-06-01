# plg_field_plupload
PLUpload Joomla integration as custom form field

You can add a custom field in any joomla components that support the 
joomla custom fields system and this will display a button for multiple 
file upload with the only size limit of the upload file size param, or
none if set to 0.
If you choose single file upload, you will have a field for store the
uploaded file name, and you can define another field as the destination
target for de uploaded file.

In either cases the upload will be made with the plupload js library
and plupload php upload handler for allow uploading of files with sizes
many timies bigger than the php upload limit permit. Even you can upload
files of many GigaBytes in size.
