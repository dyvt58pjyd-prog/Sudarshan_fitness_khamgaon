FROM ghcr.io/puppeteer/puppeteer:latest

# Skip chromium download since we use the one bundled in the image
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome-stable

WORKDIR /usr/src/app

COPY package*.json ./

RUN npm ci

COPY . .

# Render will pass the PORT env variable
EXPOSE 3000

CMD ["node", "server.js"]
