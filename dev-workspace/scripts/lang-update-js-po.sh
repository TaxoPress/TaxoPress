#!/usr/bin/env bash

source /project/dev-workspace/scripts/lang-constants.sh

for locale in $LANG_LOCALES
do
    for scriptHandler in $LANG_SCRIPT_HANDLERS
    do
        po_file="./$LANG_DIR/$PLUGIN_NAME-${locale}-${scriptHandler}.po"
        if [ -f "$po_file" ]; then
            wp i18n update-po ./$LANG_DIR/$PLUGIN_NAME-${scriptHandler}.pot $po_file --allow-root
        fi
    done
done
