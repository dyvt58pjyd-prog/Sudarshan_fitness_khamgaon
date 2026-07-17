FROM ghcr.io/puppeteer/puppeteer:latest

WORKDIR /usr/src/app

COPY package*.json ./

RUN npm ci

COPY . .

# Render will pass the PORT env variable
EXPOSE 3000

CMD ["node", "server.js"]
