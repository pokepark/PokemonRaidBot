job "pokemonraidbot" {
  datacenters = ["dc1"]
  type        = "service"

  group "raidbot" {
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
      port "http" {to = 80}
    }
    volume "raidbot" {
      type = "host"
      source = "raidbot"
      read_only = false
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
        "traefik.enable=true",
        "traefik.http.routers.raidbot.rule=Host(`raidbot.example.com`)", # needs to be customized!
        "traefik.http.routers.raidbot.tls=true",
        "traefik.http.routers.raidbot.tls.certResolver=myresolver", # needs to be customized!
        "traefik.http.routers.raidbot.entrypoints=websecure", # needs to be customized!
      ]
    }
    task "raidbot" {
      meta {
        HOST            = "raidbot.example.com"
        APIKEY          = "123456789:AABBCCDDEEFFGGHHIIJJKKLLMMNN"
        CLEANUP_SECRET  = "super-strong-cleanup-password"
      }
      driver = "docker"
      resources {
        cpu = 500
        memory = 1024
      }
      env {
        TZ                = "UTC"
        TAIL_LOGS         = "info"
        TEMPLATE_PHP_INI  = "production"
        PHP_INI_EXTENSION = "gd"
      }
      config {
        image = "ghcr.io/pokepark/pokemonraidbot:latest"
        ports = [ "http" ]
        labels = {
          "com.centurylinklabs.watchtower.enable": "true",
          "ofelia.enabled": "true",
          "ofelia.job-exec.raidbot-cleanup.schedule": "@every 20s",
          "ofelia.job-exec.raidbot-cleanup.command": "/usr/bin/curl -s --user-agent cleanup -d '{\"cleanup\":{\"secret\":\"${NOMAD_META_CLEANUP_SECRET}\"}}' https://${NOMAD_META_HOST}/index.php?apikey=${NOMAD_META_APIKEY}",
          "ofelia.job-exec.raidbot-overview.schedule": "@every 1m",
          "ofelia.job-exec.raidbot-overview.command": "/usr/bin/curl -s --user-agent overview -d '{\"callback_query\":{\"data\":\"0:overview_refresh:0\"}}' https://${NOMAD_META_HOST}/index.php?apikey=${NOMAD_META_APIKEY}",

        }
        volumes = [
          "/ssd/srv/raidbot/pokemon_PokeMiners:/var/www/html/images/pokemon_PokeMiners",
          "/ssd/srv/raidbot/pokemon_ZeChrales:/var/www/html/images/pokemon_ZeChrales",
          "/ssd/srv/raidbot/config.json:/var/www/html/config/config.json",
          "/ssd/srv/raidbot/access:/var/www/html/access",
        ]
        command = "apache2-foreground"
      }
      volume_mount {
        volume = "raidbot"
        destination = "/local/raidbot"
      }
    }
  }
}
