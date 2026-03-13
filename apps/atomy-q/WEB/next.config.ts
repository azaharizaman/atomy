import path from "path";
import type { NextConfig } from "next";

// Use WEB directory as the sole root for module resolution so that
// tailwindcss, @tailwindcss/postcss, and other deps resolve from WEB/node_modules
// instead of the monorepo root (apps/atomy-q, apps, then root).
const webRoot = __dirname;

const nextConfig: NextConfig = {
  // Turbopack (next dev): resolve modules from WEB only
  turbopack: {
    root: webRoot,
  },
  // Webpack (next build / CSS pipeline): prefer WEB/node_modules
  webpack: (config) => {
    config.resolve = config.resolve ?? {};
    const webNodeModules = path.join(webRoot, "node_modules");
    config.resolve.modules = [
      webNodeModules,
      ...(Array.isArray(config.resolve.modules) ? config.resolve.modules : ["node_modules"]),
    ];
    return config;
  },
};

export default nextConfig;
