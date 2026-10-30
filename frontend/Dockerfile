FROM node:22-alpine
WORKDIR /ui
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY index.html vite.config.js ./
COPY src ./src
EXPOSE 5173
CMD ["npm", "run", "dev"]
