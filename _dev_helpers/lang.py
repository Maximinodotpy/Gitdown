
import os, polib

all=[
    'af',
    'sq',
    'eu',
    'be',
    'bg',
    'ca',
    'zh-hk',
    'zh-cn',
    'zh-tw',
    'hr',
    'cs',
    'da',
    'nl',
    'en',
    'en-ca',
    'en-gb',
    'en-us',
    'et',
    'fa',
    'fi',
    'fr',
    'gd',
    'de',
    'el',
    'he',
    'hi',
    'hu',
    'is',
    'id',
    'ga',
    'it',
    'ja',
    'ko',
    'ko',
    'ku',
    'lv',
    'lt',
    'mk',
    'ml',
    'ms',
    'mt',
    'no',
    'nb',
    'pl',
    'pt-br',
    'pt',
    'pa',
    'ro',
    'ru',
    'sr',
    'sk',
    'sl',
    'es-mx',
    'es',
    'sv',
    'th',
    'tr',
    'ur',
    'vi',
    'cy',
    'xh',
    'ji',
    'zu',
]

os.chdir(os.path.join(os.getcwd(), 'languages'))

for lang in all:
    print(lang)

    poPath = f'po/gitdown-{lang}.po'

    os.system(f'xls-to-po {lang} translations.xlsx {poPath}')
    poFile = polib.pofile(poPath)
    poFile.save_as_mofile(f'po/gitdown-{lang}.mo')