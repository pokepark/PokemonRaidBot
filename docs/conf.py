import sphinx_rtd_theme

project = 'PokemonRaidBot'
copyright = '2021, The PokePark Dev Team'
author = 'The PokePark Dev Team'


extensions = [
    "sphinx_rtd_theme",
]

root_doc = 'index'
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

html_theme = 'sphinx_rtd_theme'
html_static_path = ['_static']

def setup(app):
    app.add_css_file('my_theme.css')
