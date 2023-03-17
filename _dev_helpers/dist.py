# Imports
import shutil
import os
from pathlib import Path
import json
import argparse

# Constants
SVN_PATH = Path(os.getcwd()) / Path('_svn')
SVN_URL = 'http://plugins.svn.wordpress.org/gitdown'
CONFIG = json.load(open('_dev_helpers/config.json'))


# Setup Argument Parser
parser = argparse.ArgumentParser(
    prog = 'Gitdown Distribution',
)
parser.add_argument('version_mode', choices=['major', 'minor', 'patch'])

ARGS = parser.parse_args()
print(ARGS.version_mode)


# Functions
def create_folder(new_path):
    if not os.path.exists(new_path):
        os.makedirs(new_path)

def path_exits(path):
    return os.path.exists(path)

def copy_folder(source, destination, ignore=[]):
    print('\nCopying %s to %s' % (source, destination))
    shutil.copytree(source, destination, ignore = shutil.ignore_patterns(*ignore))

def clear_folder(folder):
    print('\nClearing %s' % folder)
    if not path_exits(folder): return

    for filename in os.listdir(folder):
        file_path = os.path.join(folder, filename)
        try:
            if os.path.isfile(file_path) or os.path.islink(file_path):
                os.unlink(file_path)
            elif os.path.isdir(file_path):
                shutil.rmtree(file_path)
        except Exception as e:
            print('Failed to delete %s. Reason: %s' % (file_path, e))

    os.rmdir(folder)

def get_folders_within_folder(folder):
    filenames= os.listdir (folder) # get all files' and folders' names in the current directory

    result = []
    for filename in filenames:
        if (os.path.isdir(Path(folder) / Path(filename))):
            result.append(filename)

    result.sort()
    result.reverse()
    return result

# Final Script

create_folder(SVN_PATH)

while (not path_exits(SVN_PATH / Path('.svn'))):
    input(f'Checkout Repository to "{SVN_PATH}" and then press enter ...')


# Copy Assets Folder
asset_folder = SVN_PATH / Path('assets')
clear_folder(asset_folder)
copy_folder('assets', asset_folder, ignore = [ 'banner.ai', 'icon.ai', 'overview.md', 'gitdown-video.mp4' ])

trunk_folder = SVN_PATH / Path('trunk')
ignore = [
    # Folders
    '.git',
    'dist',
    'assets',
    'languages',
    'test-repo',
    'logs',
    '_dev_helpers',
    'node_modules',
    '_releasing',
    '_svn',
    '.vscode',
    
    # Files
    '.eslintrc.json',
    'eslintrc.json',
    'package.json',
    'package-lock.json',
    'todo.md',
    'tailwind.config.js',
    'readme.md',
    'overview.md',
    '.gitignore',
    'gitdown-video.mp4',
    'input.css',
    '*.ai',
]
clear_folder(trunk_folder)
copy_folder('.', trunk_folder, ignore = ignore)

tags_folder = SVN_PATH / Path('tags')
latest_version = get_folders_within_folder(tags_folder)[0]

latest_major = int(latest_version.split('.')[0])
latest_minor = int(latest_version.split('.')[1])
latest_patch = int(latest_version.split('.')[2])

new_version = ''

if (ARGS.version_mode == 'patch'):
    new_version = f'{latest_major}.{latest_minor}.{latest_patch + 1}'
elif (ARGS.version_mode == 'minor'):
    new_version = f'{latest_major}.{latest_minor + 1}.0'
elif (ARGS.version_mode == 'major'):
    new_version = f'{latest_major + 1}.0.0'

copy_folder(trunk_folder, tags_folder / Path(new_version))


print(f'\nDont forget to now bump of the version number to "{new_version}" in your readme.txt/gitdown.php and to commit the changes via a SVN Client.')