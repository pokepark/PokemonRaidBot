#!/usr/bin/env python3

from absl import flags, app, logging
import json

FLAGS = flags.FLAGS

flags.DEFINE_string('from_language', 'EN', 'Language to use as translation hint')
flags.DEFINE_string('to', None, 'Language to translate into')
flags.DEFINE_string('input', 'language.json', 'File to read base translation from.')
flags.DEFINE_string('output', 'language.json', 'File to keep saving changes to.')
flags.DEFINE_boolean('incremental', True, 'Update only strings missing translations.')
flags.DEFINE_boolean('sort_keys', False, 'Whether to output sorted or not.')
flags.mark_flag_as_required('to')


def main(argv):
    with open(FLAGS.input, encoding='utf8') as f:
        trans = json.load(f)

    logging.info('Creating placeholders for missing strings for language {0}'.format(FLAGS.to))
    for key in trans.keys():
        if FLAGS.to not in trans[key]:
            trans[key][FLAGS.to] = 'TRANSLATE'
    flush(trans)
    print('Press ^D or ^C to stop. Leave a translation empty to skip.')


    if FLAGS.incremental:
        logging.info('Iterating over strings that have not been translated to language {0}'.format(FLAGS.to))
        for key in trans.keys():
            if trans[key][FLAGS.to] == 'TRANSLATE':
                translate(trans, key)
                flush(trans)

    else:
        logging.info('Iterating over all strings of language {0}'.format(FLAGS.to))
        for key in trans.keys():
            translate(trans, key)
            flush(trans)

# Flush current changes to output file to avoid losing changes
def flush(trans):
    logging.debug('flushing into {0}'.format(FLAGS.output))
    with open(FLAGS.output, 'w', encoding='utf8') as f:
        # We dump without ASCII ensurance to get unicode output for example for Russian
        json.dump(trans, f, indent=2, sort_keys=FLAGS.sort_keys, ensure_ascii=False)

# Print from string, request to string and update it to trans
def translate(trans, key):
    print('{0}[{1}]: {2}'.format(key, FLAGS.from_language, trans[key][FLAGS.from_language]))
    translation = input('{0}[{1}]: '.format(key, FLAGS.to))
    if translation:
        trans[key][FLAGS.to] = translation


if __name__ == '__main__':
    app.run(main)
