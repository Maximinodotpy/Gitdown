import shutil
import os

def clearFolder(folder):
    for filename in os.listdir(folder):
        file_path = os.path.join(folder, filename)
        try:
            if os.path.isfile(file_path) or os.path.islink(file_path):
                os.unlink(file_path)
            elif os.path.isdir(file_path):
                shutil.rmtree(file_path)
        except Exception as e:
            print('Failed to delete %s. Reason: %s' % (file_path, e))



print('Creating Dist Zip Folder ...')


distFolderName = 'dist'
cwd = os.getcwd()
distFolderPATH = os.path.join(cwd, distFolderName)

print('Clearing ...')
clearFolder(distFolderPATH)

ignore = [
    # Folders
    '.git',
    'dist',
    'languages',
    'test-repo',
    'logs',
    '_dev_helpers',
    'node_modules',
    '_releasing',

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

print('Copying ...')
shutil.copytree(cwd, os.path.join(distFolderPATH, 'temp_gitdown'), ignore = shutil.ignore_patterns(*ignore))


print('Zipping ...')
shutil.make_archive(os.path.join(distFolderPATH, 'gitdown'), "zip", os.path.join(distFolderPATH, 'temp_gitdown'))

print('Clearing ...')
""" clearFolder(os.path.join(distFolderPATH, 'temp_gitdown')) """
try:
    os.remove(os.path.join(distFolderPATH, 'temp_gitdown'))
except:
    pass
print('Finished')