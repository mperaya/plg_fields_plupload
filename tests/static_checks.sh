#!/usr/bin/env bash
set -euo pipefail

rg -n "case 'download'" plupload.php >/dev/null
rg -n "'action' => 'download'" plupload.php >/dev/null
rg -n "use Joomla\\\\CMS\\\\Language\\\\Text;" plupload.php >/dev/null

echo "Static checks passed"
