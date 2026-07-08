#!/bin/bash
while true; do
  echo "$(date): Starting Localtunnel on port 8000..."
  npx localtunnel --port 8000 --subdomain sudarshan-fitness
  echo "$(date): Localtunnel exited. Restarting in 5 seconds..."
  sleep 5
done
