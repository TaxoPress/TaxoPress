#!/usr/bin/env bash

source /project/dev-workspace/scripts/lang-constants.sh

export JSX_SCRIPTS="workflow-editor/future_workflow_editor_script backup-panel/future_backup_panel"

for locale in $LANG_LOCALES
do
    for scriptHandler in $JSX_SCRIPTS
    do
        IFS='/' read -ra scriptHandlers <<< "$scriptHandler"
        package="${scriptHandlers[0]}"
        handler="${scriptHandlers[1]}"
        source_path="./assets/jsx/${package}"
        pot_file="./$LANG_DIR/${PLUGIN_NAME}-$handler.pot"
        po_file="./$LANG_DIR/$PLUGIN_NAME-${locale}-$handler.po"

        wp i18n make-pot $source_path $pot_file --domain=$LANG_DOMAIN  --allow-root

        # If the PO file doesn't exist, create it and update it with the POT file. If exists, do nothing.
        if [ ! -f "$po_file" ]; then
            touch "$po_file"
            wp i18n update-po $pot_file $po_file --allow-root
        fi
    done
done
