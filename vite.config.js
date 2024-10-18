import { v4wp } from './v4wp/v4wp';
import mkcert from 'vite-plugin-mkcert';

export default {
	server: { https: true },
	plugins: [
		v4wp({
			input: {
				data_fetch: 'src/admin/settings/tabs/data_fetch/data_fetch.js',
			},
			outDir: 'dist',
		}),
		mkcert(),
	],
	resolve: {
		alias: {
			src: '/src',
		},
	},
};
