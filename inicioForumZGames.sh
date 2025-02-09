#!/bin/bash

# Derriba los servicios y elimina los volúmenes
docker compose down -v

# Construye y levanta los servicios nuevamente
docker compose up --build
