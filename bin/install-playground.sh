#!/usr/bin/env bash
set -euo pipefail
stdgf_site=${1:?Usage: install-playground.sh /path/to/herd/wordpress}
stdgf_repo=$(cd "$(dirname "$0")/.." && pwd)
python3 "$stdgf_repo/bin/build.py" --draft
stdgf_zip="$stdgf_repo/dist/savethedate-for-gravity-forms-1.0.0-candidate.zip"
wp --path="$stdgf_site" plugin is-active gravityforms
wp --path="$stdgf_site" plugin install "$stdgf_zip" --force --activate
wp --path="$stdgf_site" eval-file "$stdgf_repo/bin/playground-setup.php"
wp --path="$stdgf_site" plugin status savethedate-for-gravity-forms
