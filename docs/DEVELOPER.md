## Developer Notes for Musahimoun Plugin

### Contributing
- Fork the repository and create a feature branch.
- Follow WordPress coding standards for PHP, JS, and CSS.
- Submit pull requests with clear descriptions and reference related issues.

### Architecture
- Follows WordPress plugin structure: main PHP file, `inc/` for core classes, `admin/` and `front/` for UI and blocks.
- Uses REST API endpoints for contributor and role management.
- Frontend blocks are built with JavaScript/TypeScript and bundled via Webpack.

### Build Tools
- JavaScript/TypeScript code is managed in `front/src/` and built using Webpack (`webpack.config.js`).
- Run `npm install` and `npm run build` in the `front/` directory to build assets.

### Packaging (`package-plugin.ps1`)
- Use the PowerShell script `package-plugin.ps1` to create a distributable ZIP.
- The script excludes development files and folders (e.g., node_modules, tests, Docker files).
- Run the script from the project root: `./package-plugin.ps1`.

### Docker
- Dockerfiles are provided for different PHP versions (`Dockerfile.74`, `Dockerfile.81`).
- Use `docker-compose.yml` for local development and testing.
- Example: `docker-compose up` to start the environment.

### Other Notes
- Assets (images, icons, banners) are in the `assets/` folder.
- See `README.md` for plugin usage and installation instructions.
