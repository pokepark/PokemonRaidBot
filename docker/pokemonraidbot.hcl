job "pokemonraidbot" {
  datacenters = ["dc1"]
  type        = "service"

  # Set these to match your own values!
  meta {
    HOST            = "raidbot.example.com"
    APIKEY          = "123456789:AABBCCDDEEFFGGHHIIJJKKLLMMNN"
    CLEANUP_SECRET  = "super-strong-cleanup-password"
  }

  group "raidbot" {
    # One instance is enough for most groups
    count = 1
    update {
      max_parallel     = 1
      canary           = 1
      min_healthy_time = "10s"
      healthy_deadline = "2m"
      auto_revert      = true
      auto_promote     = true
    }
    network {
      # If not using Traefik change the port defition to 'static = 8080' or what ever port you want to use.
      # The default randomises the port but notifies Traefik of the right one, allowing you to run many instances in parallel.
      port "http" {to = 80}
    }
    service {
      port = "http"
      check {
        name     = "alive"
        type     = "http"
        path     = "/"
        interval = "60s"
        timeout  = "10s"
        check_restart {
          limit = 3
          grace = "90s"
          ignore_warnings = false
        }
      }
      tags = [
        # These tags are an example of Traefik which is used to add free LetsEncrypt SSL automatically.
        #
        "traefik.enable=true",
        "traefik.http.routers.raidbot.rule=Host(`${NOMAD_META_HOST}`)",
        "traefik.http.routers.raidbot.tls=true",
        "traefik.http.routers.raidbot.tls.certResolver=myresolver", # needs to be customized to your cert resolver!
        "traefik.http.routers.raidbot.entrypoints=websecure", # needs to be customized to your https entrypoint!
      ]
    }
    task "raidbot" {
      driver = "docker"
      resources {
        cpu = 500
        memory = 500 # This is overkill, usually 100MB is enough but it depends on many things.
      }
      env {
        TZ                = "UTC" # Change this to your timezone, for example Europe/Vienna
        TAIL_LOGS         = "info"
        TEMPLATE_PHP_INI  = "production"
        PHP_INI_EXTENSION = "gd"
      }
      config {
        image = "ghcr.io/pokepark/pokemonraidbot:latest"
        ports = [ "http" ]
        labels = {
          # If you have a watchtower container running, this tag will enable auto-updates of the running image
          "com.centurylinklabs.watchtower.enable": "true",
          # If you have an ofelia container running these tags enable automatic cleanup & overview refresh
          "ofelia.enabled": "true",
          "ofelia.job-exec.raidbot-cleanup.schedule": "@every 20s",
          "ofelia.job-exec.raidbot-cleanup.command": "/usr/bin/curl -s --user-agent cleanup -d '{\"cleanup\":{\"secret\":\"${NOMAD_META_CLEANUP_SECRET}\"}}' https://${NOMAD_META_HOST}/index.php?apikey=${NOMAD_META_APIKEY}",
          "ofelia.job-exec.raidbot-overview.schedule": "@every 1m",
          "ofelia.job-exec.raidbot-overview.command": "/usr/bin/curl -s --user-agent overview -d '{\"callback_query\":{\"data\":\"0:overview_refresh:0\"}}' https://${NOMAD_META_HOST}/index.php?apikey=${NOMAD_META_APIKEY}",

        }
        volumes = [
          # Customize this to where you want to store the images, < 100MB, they will be autodownloaded
          "/srv/raidbot/pokemon_PokeMiners:/var/www/html/images/pokemon_PokeMiners",
          # Customize this to where you have your config, you must create it yourself first based on the example
          "/srv/raidbot/config.json:/var/www/html/config/config.json",
          # Customize this to here you have your access definitions, read the documentation for that.
          "/srv/raidbot/access:/var/www/html/access",
        ]
        command = "apache2-foreground"
      }
    }
  }
}
