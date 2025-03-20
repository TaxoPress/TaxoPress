#!/usr/bin/env bash

# If the legacy dir "cache" exists, move its content to $CACHE_PATH and remove it.
if [[ -d "cache" ]]; then
    mv cache/* $CACHE_PATH
    rm -rf cache
fi

# Create empty cache files if not exists.
[[ -d $CACHE_PATH ]] || mkdir -p $CACHE_PATH
[[ -d $CACHE_PATH/.npm/_cacache ]] || mkdir -p $CACHE_PATH/.npm/_cacache
[[ -d $CACHE_PATH/.npm/_logs ]] || mkdir -p $CACHE_PATH/.npm/_logs
[[ -d $CACHE_PATH/.composer/cache ]] || mkdir -p $CACHE_PATH/.composer/cache
[[ -d $CACHE_PATH/.oh-my-zsh/log ]] || mkdir -p $CACHE_PATH/.oh-my-zsh/log
[[ -f $CACHE_PATH/.zsh_history ]] || touch $CACHE_PATH/.zsh_history
[[ -f $CACHE_PATH/.bash_history ]] || touch $CACHE_PATH/.bash_history
[[ -f $CACHE_PATH/.composer/auth.json ]] || echo '{}' > $CACHE_PATH/.composer/auth.json
