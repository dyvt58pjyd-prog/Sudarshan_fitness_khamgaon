FROM ghcr.io/puppeteer/puppeteer:latest

WORKDIR /usr/src/app

COPY package*.json ./

RUN npm ci --ignore-scripts=false
RUN npx puppeteer browsers install chrome

COPY . .

# Render will pass the PORT env variable
EXPOSE 3000

CMD ["node", "server.js"]
