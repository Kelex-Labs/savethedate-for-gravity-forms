"""Exercise release version/metadata gates without altering the real checkout."""
from pathlib import Path
import json
import re
import shutil
import subprocess
import sys
import tempfile
import unittest
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'savethedate-for-gravity-forms'

class BuildTests(unittest.TestCase):
    def fixture(self, changes=None, arguments=None):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            (root / 'bin').mkdir()
            for name in [SLUG + '.php', 'readme.txt', 'bin/build.py']:
                shutil.copy2(ROOT / name, root / name)
            for name, value in (changes or {}).items():
                (root / name).write_text(value)
            return subprocess.run([sys.executable, str(root / 'bin/build.py')] + (arguments or []), capture_output=True, text=True)

    def test_version_mismatch_is_rejected(self):
        result = self.fixture({'readme.txt': (ROOT / 'readme.txt').read_text().replace(re.search(r'^Stable tag: (.+)$', (ROOT / 'readme.txt').read_text(), re.M).group(0), 'Stable tag: 9.9.9')}, ['--draft'])
        self.assertNotEqual(0, result.returncode)
        self.assertIn('disagree', result.stderr)

    def test_wrong_numeric_tag_is_rejected(self):
        result = self.fixture(arguments=['--draft', '--tag', '9.9.9'])
        self.assertNotEqual(0, result.returncode)
        self.assertIn('tag does not match', result.stderr)

    def test_unverified_publisher_metadata_blocks_stable_build(self):
        readme = re.sub(r'^Contributors:.*$', 'Contributors:', (ROOT / 'readme.txt').read_text(), flags=re.M)
        result = self.fixture({'readme.txt': readme}, arguments=['--tag', re.search(r'^Stable tag: (.+)$', (ROOT / 'readme.txt').read_text(), re.M).group(1)])
        self.assertNotEqual(0, result.returncode)
        self.assertIn('Contributors and Tested up to', result.stderr)

    def test_built_zip_contains_only_runtime_allowlist(self):
        report = json.loads((ROOT / 'dist/build-manifest.json').read_text())
        with zipfile.ZipFile(ROOT / 'dist' / report['package']) as package:
            roots = set()
            for name in package.namelist():
                self.assertTrue(name.startswith(SLUG + '/'))
                relative = name[len(SLUG)+1:]
                roots.add(relative.split('/')[0])
                self.assertNotIn('..', relative.split('/'))
                self.assertNotIn('SaveTheDate\\Pro', package.read(name).decode('utf-8', errors='ignore'))
            if SLUG + '/composer.json' in package.namelist():
                manifest = json.loads(package.read(SLUG + '/composer.json'))
                self.assertNotIn('require-dev', manifest)
                self.assertNotIn('scripts', manifest)
                self.assertEqual({'php': '>=7.4'}, manifest['require'])
            self.assertTrue(roots <= {SLUG + '.php', 'uninstall.php', 'readme.txt', 'LICENSE', 'src', 'templates', 'assets', 'languages', 'examples', 'vendor', 'composer.json'})

if __name__ == '__main__':
    unittest.main()
