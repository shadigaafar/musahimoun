const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const glob = require('glob');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Function to generate entry points
function generateEntries() {
	const entries = {};
	const blockFiles = glob.sync('./src/blocks/**/index.{js,ts,tsx}');
	blockFiles.forEach((file) => {
		const name = path.relative('./src/blocks', path.dirname(file)); // Use relative path as name
		entries[name] = file;
	});

	// Add the js index entry
	const jsFiles = glob.sync('./src/index.{js,ts,tsx}');
	if (jsFiles.length > 0) {
		entries['jsFile'] = jsFiles[0]; // Assume there's only one main index file inside 'src' directory
	}

	return entries;
}

module.exports = {
	mode: 'development',
	entry: generateEntries(),
	output: {
		path: path.resolve(__dirname, 'dist'),
		filename: (pathData) => {
			const name = pathData.chunk.name;
			if (name === 'jsFile') {
				return 'index.js';
			}
			return `blocks/${name}/index.js`; // Output block entries to 'blocks/[name]/index.js'
		},
	},
	module: {
		rules: [
			{
				test: /\.(js|tsx|ts)$/,
				include: path.resolve(__dirname, 'src/'),
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
							'@babel/preset-typescript',
							'@babel/preset-react',
						],
					},
				},
			},
			{
				test: /.*\.(css|scss|sass)$/,
				use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
			},
			{
				test: /\.(gif|png|jpe?g|svg)$/i,
				use: [
					'file-loader',
					{
						loader: 'image-webpack-loader',
						options: {
							bypassOnDebug: true, // webpack@1.x
							disable: true, // webpack@2.x and newer
						},
					},
				],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: (pathData) => {
				const name = pathData.chunk.name;
				if (name === 'jsFile') {
					return 'style.css';
				}
				return `blocks/${name}/style.css`; // Output block CSS to 'blocks/[name]/style.css'
			},
		}),
		new CopyWebpackPlugin({
			patterns: [
				{
					from: 'src/blocks/**/*.php',
					to: ({ absoluteFilename }) => {
						// Get the relative path from the 'src/blocks' directory
						const relativePath = path.relative(
							path.join(__dirname, 'src/blocks'),
							absoluteFilename
						);
						// Extract the folder name
						const folderName = relativePath.split(path.sep)[0];
						return path.join('blocks', folderName, 'index.php');
					},
					noErrorOnMissing: true,
				},
				{
					from: 'src/index.php',
					to: () => {
						return 'index.php';
					},
					noErrorOnMissing: true,
				},
				{
					from: 'src/blocks/**/*.json', // Update the pattern to include block.json files
					to: ({ absoluteFilename }) => {
						// Get the relative path from the 'src/blocks' directory
						const relativePath = path.relative(
							path.join(__dirname, 'src/blocks'),
							absoluteFilename
						);
						// Extract the folder name
						const folderName = relativePath.split(path.sep)[0];
						return path.join('blocks', folderName, 'block.json');
					},
					noErrorOnMissing: true,
				},
				{
					from: 'src/blocks/**/*.css', // Update the pattern to include style.css files
					to: ({ absoluteFilename }) => {
						// Get the relative path from the 'src/blocks' directory
						const relativePath = path.relative(
							path.join(__dirname, 'src/blocks'),
							absoluteFilename
						);
						// Extract the folder name
						const folderName = relativePath.split(path.sep)[0];
						return path.join('blocks', folderName, 'style.css');
					},
					noErrorOnMissing: true,
				},
			],
		}),
		new DependencyExtractionWebpackPlugin(),
	],
	resolve: {
		extensions: ['.js', '.ts', '.tsx'], // Add .ts and .tsx as resolvable extensions
	},
	devServer: {
		static: {
			directory: path.join(__dirname, 'dist'),
		},
		compress: true,
		port: 9000,
	},
};
