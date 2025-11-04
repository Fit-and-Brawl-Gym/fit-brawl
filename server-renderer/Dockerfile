FROM node:18-alpine

# Set working directory
WORKDIR /app

# Install Chrome dependencies for Puppeteer
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    font-noto-emoji

# Set Puppeteer to use system Chrome
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser \
    NODE_ENV=production

# Copy package files
COPY package*.json ./

# Install dependencies (use npm install if package-lock.json missing)
RUN npm ci --omit=dev || npm install --omit=dev

# Copy application files
COPY . .

# Expose port (Cloud Run uses PORT env variable, default to 8080)
EXPOSE 8080

# Set the PORT environment variable
ENV PORT=8080

# Run the renderer wrapper
CMD ["node", "render-wrapper.js"]
