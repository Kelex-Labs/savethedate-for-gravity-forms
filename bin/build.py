#!/usr/bin/env python3
"""Allowlist Free packaging; draft builds are installable but not release approval."""
import argparse, hashlib, json, os, re, shutil, subprocess, tempfile, zipfile
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
SLUG = 'savethedate-for-gravity-forms'
p = argparse.ArgumentParser()
p.add_argument('--draft', action='store_true')
p.add_argument('--submission', action='store_true', help='Build a submission ZIP with explicitly accepted verification limits recorded in private evidence.')
p.add_argument('--tag')
p.add_argument('--qa', type=Path)
a = p.parse_args()
if a.draft and a.submission:
    p.error('Choose either a draft or a submission build.')
main = (ROOT / (SLUG + '.php')).read_text()
readme = (ROOT / 'readme.txt').read_text()
version = re.search(r'^ \* Version: (\d+\.\d+\.\d+)$', main, re.M).group(1)
constant = re.search(r"define\( 'STDGF_VERSION', '(.*?)' \)", main).group(1)
stable = re.search(r'^Stable tag: (.+)$', readme, re.M).group(1)
if len({version, constant, stable}) != 1:
    p.error('Plugin header, constant and Stable tag disagree.')
if a.tag and a.tag != version:
    p.error('Git tag does not match the plugin version.')
if not a.draft:
    if not re.search(r'^Contributors: [a-zA-Z0-9_, -]+$', readme, re.M) or not re.search(r'^Tested up to: \d+\.\d+$', readme, re.M):
        p.error('Finalize verified WordPress.org Contributors and Tested up to first.')
    try:
        tags = subprocess.check_output(['git', 'tag', '--points-at', 'HEAD'], cwd=ROOT, text=True, stderr=subprocess.DEVNULL).splitlines()
    except subprocess.CalledProcessError:
        p.error('Stable packaging requires a committed exact numeric tag at HEAD.')
    if not a.tag or a.tag not in tags:
        p.error('Stable packaging requires the exact numeric tag at HEAD.')
    if subprocess.check_output(['git', 'status', '--porcelain'], cwd=ROOT, text=True).strip():
        p.error('Stable packaging requires a clean checkout.')
    if not a.qa or not a.qa.is_file():
        p.error('Stable packaging requires recorded release evidence via --qa.')
    evidence = json.loads(a.qa.read_text())
    required = ['live_qa', 'minimum_versions', 'current_versions', 'provider_imports', 'accessibility_rtl', 'multisite_uninstall', 'empty_debug_log', 'readme_validator', 'screenshots', 'plugin_check', 'name_availability']
    accepted = evidence.get('accepted_limits', {}) if a.submission else {}
    optional = {'minimum_versions', 'provider_imports', 'multisite_uninstall'}
    def verified(key):
        return evidence.get(key) == 'pass' or (key in optional and evidence.get(key) == 'limited' and isinstance(accepted.get(key), str) and bool(accepted[key].strip()))
    if evidence.get('version') != version or not all(verified(k) for k in required):
        p.error('Release evidence is incomplete or for a different version.')
    if accepted and not evidence.get('limits_accepted_by'):
        p.error('Submission verification limits require recorded publisher acceptance.')
    if not evidence.get('tested_by') or not evidence.get('tested_at'):
        p.error('Release evidence must identify the tester and test date.')
    subprocess.run(['composer', 'check'], cwd=ROOT, check=True)
# Production autoloading is generated from a separate manifest: no dev packages copied.
dist = ROOT / 'dist'
dist.mkdir(exist_ok=True)
with tempfile.TemporaryDirectory(prefix='stdgf-build-') as tmp:
    stage = Path(tmp) / SLUG
    stage.mkdir()
    for name in [SLUG + '.php', 'uninstall.php', 'readme.txt', 'LICENSE', 'src', 'templates', 'assets', 'languages', 'examples']:
        source = ROOT / name
        if source.is_symlink() or (source.is_dir() and any(file.is_symlink() for file in source.rglob('*'))):
            p.error('Source symlinks are prohibited in release packages.')
        if source.is_dir(): shutil.copytree(source, stage / name, ignore=shutil.ignore_patterns('.*'))
        else: shutil.copy2(source, stage / name)
    manifest = {'version': version, 'config': {'autoloader-suffix': 'stdgf' + version.replace('.', '')}, 'name': 'kelex-labs/' + SLUG, 'license': 'GPL-2.0-or-later', 'require': {'php': '>=7.4'}, 'autoload': {'psr-4': {'SaveTheDate\\': 'src/'}}}
    (stage / 'composer.json').write_text(json.dumps(manifest, indent=2))
    subprocess.run(['composer', 'dump-autoload', '--no-dev', '--optimize', '--no-interaction'], cwd=stage, check=True)
    for file in stage.rglob('*'):
        if file.is_symlink(): p.error('Symlinks are prohibited in release packages.')
        if file.is_file():
            if file.suffix == '.php': subprocess.run(['php', '-l', str(file)], check=True, stdout=subprocess.DEVNULL)
            content = file.read_bytes()
            if any(x in content for x in [b'SaveTheDate\\Pro', b'purchase code', b'api.envato.com']): p.error('Private/commercial source detected.')
    suffix = '-candidate' if a.draft else ''
    archive = dist / (SLUG + '-' + version + suffix + '.zip')
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as zip_out:
        for file in sorted(stage.rglob('*')):
            if file.is_file():
                info = zipfile.ZipInfo(str(file.relative_to(stage.parent)), date_time=(2026, 1, 1, 0, 0, 0))
                info.external_attr = 0o100644 << 16
                zip_out.writestr(info, file.read_bytes(), compress_type=zipfile.ZIP_DEFLATED)
    unpacked = dist / SLUG
    if unpacked.exists(): shutil.rmtree(unpacked)
    shutil.copytree(stage, unpacked)
result = {'version': version, 'mode': 'candidate' if a.draft else ('submission' if a.submission else 'stable'), 'package': archive.name, 'sha256': hashlib.sha256(archive.read_bytes()).hexdigest(), 'files': {str(file.relative_to(unpacked)): hashlib.sha256(file.read_bytes()).hexdigest() for file in sorted(unpacked.rglob('*')) if file.is_file()}}
(dist / 'build-manifest.json').write_text(json.dumps(result, indent=2) + '\n')
print(json.dumps(result, indent=2))
