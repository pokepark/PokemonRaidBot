#!/bin/bash



# "Tail" logs if requested to get them to Docker stderr
logs=()
if [[ -z "${TAIL_LOGS}" ]]; then
  echo "You can increase the set of logs redirected to stderr by setting e.g. TAIL_LOGS=\"info\" to the preferred list of types from: sql, info, cleanup, incoming"
  echo "By default, only the Apache error.log is redirected to stderr and the access.log to stdout."
else
  echo "TAIL_LOGS=\"${TAIL_LOGS}\""
  [[ "$TAIL_LOGS" == *"sql"* ]] && logs+=("/var/log/tg-bots/raid-bot-sql.log")
  [[ "$TAIL_LOGS" == *"info"* ]] && logs+=("/var/log/tg-bots/raid-bot.log")
  [[ "$TAIL_LOGS" == *"cleanup"* ]] && logs+=("/var/log/tg-bots/raid-bot-cleanup.log")
  [[ "$TAIL_LOGS" == *"incoming"* ]] && logs+=("/var/log/tg-bots/raid-bot-incoming.log")
fi

if [[ ! -z "${logs}" ]]; then
  # Apache forking makes it really annoying to redirect to stderr, so we need to proxy it
  logpipe="/tmp/logpipe"
  mkfifo -m 660 "$logpipe"
  chown www-data:root "$logpipe"
  sudo -EHu www-data bash -c "(stdbuf -i0 -o0 -e0 cat <> "$logpipe" 1>&2)" &
  echo "Redirecting logs to stderr:"
  for log in "${logs[@]}"; do
    echo -n "$log ... "
    ln -sfT "$logpipe" "$log" && echo "done" || echo "failed!"
  done
fi

# Parse any qualifying ENV into a config.json. If there isn't any, it's assumed one has been volume mounted in or otherwise made available.
env | egrep -q '^POKEMONRAIDBOT_' && \
  (jq -n 'env |with_entries(select(.key | startswith("POKEMONRAIDBOT"))) |  with_entries( if .key | startswith("POKEMONRAIDBOT_") then .key |= sub("POKEMONRAIDBOT_";"") else . end)' \
  | tee -a config/config.json)

# Do some permission setting for any data that might've been volume mounted in, otherwise stuff will fail in weird ways.
chown -R www-data:www-data config/config.json access/ custom/ images/pokemon*

# Ensure we now have a valid config
if [[ ! -a /var/www/html/config/config.json ]]; then
  2>&1 echo "/var/www/html/config/config.json is missing, nothing will work without it. You probably want to bind mount it in or otherwise ensure it is injected in place, or set config values with POKEMONRAIDBOT_* env variables."
  exit 1
fi


# Launch image update in the background
# Highly recommended to mount volumes to /var/www/html/images/pokemon_* to avoid a full download every time!
php getPokemonIcons.php &

echo "Setup underway, launching upstream entrypoint."

# Launch the normal entrypoint
exec /usr/local/bin/docker-entrypoint.sh "$@"
