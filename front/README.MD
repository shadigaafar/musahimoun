# Musahimoun Frontend Build Tools

This directory contains the frontend build system for the Musahimoun WordPress plugin. It uses modern web technologies including TypeScript, React, and Webpack.

## Prerequisites

- Node.js (version 18 or higher recommended)
- npm or yarn package manager

## Installation

1. Navigate to the `front` directory:
   ```bash
   cd front
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

## Available Scripts

### Development

- **`npm run dev`** - Starts the WordPress scripts development server with hot reloading
- **`npm start`** - Starts the webpack dev server on port 9000 with auto-open browser
- **`npm run build`** - Builds the project in watch mode (automatically rebuilds on file changes)

### Building

- **`npm run build`** - Creates production build with file watching
- **`npm run build:prod`** - Creates optimized production build (when available)

### Code Quality

- **`npm run lint:js`** - Lints JavaScript/TypeScript files using WordPress coding standards
- **`npm run lint:css`** - Lints CSS files
- **`npm run format`** - Formats code using WordPress coding standards
- **`npm run check-licenses`** - Checks package licenses for compatibility

### Testing

- **`npm run test:unit`** - Runs unit tests
- **`npm run test:e2e`** - Runs end-to-end tests

## Project Structure

```
front/
├── src/
│   ├── blocks/           # WordPress Gutenberg blocks
│   │   ├── contributor-avatar/
│   │   ├── contributor-biography/
│   │   ├── contributor-email/
│   │   ├── contributor-name/
│   │   ├── contributor-query-loop/
│   │   ├── meta-box/
│   │   ├── role-assignment-query-loop/
│   │   └── role-prefix/
│   ├── store/            # State management
│   ├── index.js          # Main entry point
│   └── index.php         # PHP entry point
├── dist/                 # Build output directory
├── webpack.config.js     # Webpack configuration
├── tsconfig.json         # TypeScript configuration
└── package.json          # Dependencies and scripts
```

## Build Process

### Development Workflow

1. **Start development server:**
   ```bash
   npm run dev
   ```
   This will:
   - Watch for file changes
   - Automatically rebuild blocks
   - Provide hot reloading for development

2. **Make changes to your blocks** in the `src/blocks/` directory
3. **Files are automatically processed** and output to the `dist/` directory

### Building for Production

1. **Create production build:**
   First Change webpack.config.js mode proprty to "production", then:
   
   ```bash
   npm run build
   ```

2. **Build output** will be in the `dist/` directory:
   ```
   dist/
   ├── blocks/
   │   ├── contributor-avatar/
   │   │   ├── index.js
   │   │   ├── style.css
   │   │   ├── index.php
   │   │   └── block.json
   │   └── ... (other blocks)
   ├── index.js
   ├── style.css
   └── index.php
   ```

## Block Development

### Creating a New Block

1. Create a new directory in `src/blocks/`
2. Add the following files:
   - `index.js` - Block's main JavaScript file
   - `edit.js` - Block editor component
   - `save.js` - Block save component (if needed)
   - `style.css` - Block styles
   - `block.json` - Block metadata
   - `index.php` - PHP registration file

3. The build system will automatically:
   - Compile TypeScript/JavaScript
   - Process CSS
   - Copy PHP and JSON files
   - Output to the correct location in `dist/`

### Block File Structure Example

```javascript
// src/blocks/my-block/index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

registerBlockType(metadata, {
    edit: Edit,
    save: Save,
});
```

## Configuration Files

### Webpack Configuration (`webpack.config.js`)

- **Entry Points**: Automatically discovers all block entry points
- **Output**: Organizes builds by block name
- **Loaders**: Handles TypeScript, CSS, and image files
- **Plugins**: 
  - CSS extraction
  - File copying (PHP, JSON, CSS)
  - WordPress dependency extraction

### TypeScript Configuration (`tsconfig.json`)

- **Target**: ES5 for broad browser compatibility
- **JSX**: React support
- **Strict Mode**: Enabled for better code quality
- **Module System**: CommonJS for WordPress compatibility

## WordPress Integration

### Dependency Management

The build system automatically:
- Extracts WordPress dependencies
- Generates proper asset handles
- Manages script and style enqueuing

### Block Registration

Blocks are automatically registered through:
- `block.json` files (Gutenberg standard)
- PHP registration files
- WordPress block registry

## Troubleshooting

### Common Issues

1. **Build fails with TypeScript errors:**
   - Check `tsconfig.json` settings
   - Ensure all imports are properly typed

2. **Blocks not appearing in WordPress:**
   - Verify `block.json` files are copied to `dist/`
   - Check PHP registration files are present

3. **Styles not loading:**
   - Ensure CSS files are being processed
   - Check webpack CSS extraction plugin

### Debug Mode

Enable webpack debug output by setting:
```bash
export NODE_ENV=development
npm run build
```

## Development Tips

1. **Use TypeScript** for better code quality and IDE support
2. **Follow WordPress coding standards** for consistency
3. **Test blocks** in both editor and frontend contexts
4. **Use the watch mode** (`npm run build`) for continuous development
5. **Check the `dist/` directory** to verify build output

## Dependencies

### Core Dependencies
- **React**: UI component library
- **WordPress Scripts**: Build tools and standards
- **TypeScript**: Type-safe JavaScript

### Development Dependencies
- **Webpack**: Module bundler
- **Babel**: JavaScript transpiler
- **PostCSS**: CSS processing
- **Copy Webpack Plugin**: File copying during build

For more information about WordPress block development, see the [WordPress Developer Handbook](https://developer.wordpress.org/block-editor/).
