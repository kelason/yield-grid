# YieldGrid frontend — local dev server with hot reload.
# Node 22 to match package.json engines + CI.
FROM node:22-alpine

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --legacy-peer-deps

COPY . .

EXPOSE 5173

# Bind 0.0.0.0 so Vite is reachable outside the container.
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
