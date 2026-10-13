#!/usr/bin/env python3
"""Stage an approved stable ZIP in an existing WordPress SVN working copy; no commit."""
import argparse
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import xml.etree.ElementTree as ET
import zipfile

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('working_copy', type=Path)
p.add_argument('stable_zip', type=Path)
p.add_argument('--assets', type=Path, default=Path(__file__).resolve().parents[1] / 'wordpress-assets')
a = p.parse_args()
slug = 'savethedate-for-gravity-forms'
wc = a.working_copy.resolve()
if not (wc / '.svn').is_dir(): p.error('Use an existing approved WordPress.org SVN working copy.')
match = re.fullmatch(re.escape(slug) + r'-(\d+\.\d+\.\d+)\.zip', a.stable_zip.name)
if not match: p.error('Only stable numeric release ZIPs can be staged; candidates are refused.')
version = match[1]
if (wc / 'tags' / version).exists(): p.error('Stable SVN tags are immutable; this version already exists.')
for name in ['banner-772x250.png', 'banner-1544x500.png', 'icon-128x128.png', 'icon-256x256.png'] + ['screenshot-' + str(i) + '.png' for i in range(1, 5)]:
    if not (a.assets / name).is_file(): p.error('Missing approved listing asset: ' + name)
with tempfile.TemporaryDirectory(prefix='stdgf-svn-') as temp:
    unpack = Path(temp)
    with zipfile.ZipFile(a.stable_zip) as package:
        for name in package.namelist():
            relative = Path(name)
            if '..' in relative.parts or relative.is_absolute() or not name.startswith(slug + '/'):
                p.error('Unexpected path in ZIP.')
        package.extractall(unpack)
    plugin = unpack / slug
    readme = (plugin / 'readme.txt').read_text()
    if not re.search(r'^Stable tag: ' + re.escape(version) + '$', readme, re.M): p.error('SVN version and readme do not agree.')
    trunk = wc / 'trunk'
    trunk.mkdir(exist_ok=True)
    for item in trunk.iterdir():
        if item.is_dir(): shutil.rmtree(item)
        else: item.unlink()
    shutil.copytree(plugin, trunk, dirs_exist_ok=True)
    (wc / 'tags').mkdir(exist_ok=True)
    shutil.copytree(plugin, wc / 'tags' / version)
    (wc / 'assets').mkdir(exist_ok=True)
    for item in a.assets.glob('*.png'):
        shutil.copy2(item, wc / 'assets' / item.name)
status = subprocess.check_output(['svn', 'status', '--xml', str(wc)], text=True)
for entry in ET.fromstring(status).iter('entry'):
    item = entry.find('wc-status').attrib['item']
    if 'missing' == item: subprocess.run(['svn', 'delete', '--force', entry.attrib['path']], check=True)
subprocess.run(['svn', 'add', '--force', str(wc / 'trunk'), str(wc / 'tags' / version), str(wc / 'assets')], check=True)
subprocess.run(['svn', 'status', str(wc)], check=True)
print('Staged only. Review svn diff, then commit using the approved publisher account.')
