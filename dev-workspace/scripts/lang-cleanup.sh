#!/usr/bin/env bash

source /project/dev-workspace/scripts/lang-constants.sh

for locale in $LANG_LOCALES
do
    for scriptHandler in $LANG_SCRIPT_HANDLERS
    do
        mo_file="./$LANG_DIR/$PLUGIN_NAME-${locale}-${scriptHandler}.mo"
        if [ -f "$mo_file" ]; then
            rm $mo_file
        fi
    done
done
