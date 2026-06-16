/**
 * A custom hCaptcha dark palette, copied verbatim from the "Load Dark Theme" button
 * in hCaptcha's Custom Theme Configurator (https://docs.hcaptcha.com/custom_themes/).
 *
 * We need this because hCaptcha's built-in `theme: 'dark'` string leaves the challenge
 * card light/white on a night-mode page (it is ignored on the custom=true build,
 * T408795). Passing this object instead puts hCaptcha in custom-theme mode and emits
 * a themeConfig, darkening every surface — whether or not the API URL sets `custom=true`.
 *
 * @type {Object}
 */
const wikimediaDarkTheme = {
	palette: {
		mode: 'dark',
		grey: {
			100: '#2e2e2e', 200: '#333333', 300: '#4f4f4f', 400: '#555555', 500: '#828282',
			600: '#bdbdbd', 700: '#e0e0e0', 800: '#f2f2f2', 900: '#fafafa', 1000: '#ffffff'
		},
		primary: { main: '#26c6da' },
		warn: { main: '#ff8a80' },
		text: { heading: '#fafafa', body: '#e0e0e0' }
	},
	component: {
		checkbox: { main: { fill: '#333333', border: '#f5f5f5' }, hover: { fill: '#222222' } },
		modal: { main: { fill: '#222222' }, hover: { fill: '#333333' }, focus: { outline: '#80deea' } },
		challenge: { main: { fill: '#333333', border: '#f5f5f5' }, hover: { fill: '#222222' } },
		breadcrumb: { main: { fill: '#333333' }, active: { fill: '#00838f' } },
		button: {
			main: { fill: '#333333', icon: '#e0e0e0', text: '#e0e0e0' },
			hover: { fill: '#4f4f4f' },
			focus: { icon: '#80deea', text: '#80deea', outline: '#80deea' },
			active: { fill: '#4f4f4f', icon: '#e0e0e0', text: '#e0e0e0' }
		},
		link: { focus: { outline: '#80deea' } },
		list: { main: { fill: '#222222', border: '#4f4f4f' } },
		listItem: {
			main: { fill: '#222222', line: '#333333', text: '#e0e0e0' },
			hover: { fill: '#333333' },
			selected: { fill: '#4f4f4f' },
			focus: { outline: '#80deea' }
		},
		input: {
			main: { fill: '#fafafa', border: '#f5f5f5' },
			focus: { fill: '#4f4f4f', border: '#bdbdbd', outline: '#4de1d2' }
		},
		radio: {
			main: { file: '#333333', border: '#828282', check: '#333333' },
			selected: { check: '#26c6da' },
			focus: { outline: '#80deea' }
		},
		task: {
			main: { fill: '#4f4f4f' },
			selected: { badge: '#26c6da', outline: '#26c6da' },
			report: { badge: '#ff8a80', outline: '#ff8a80' },
			focus: { badge: '#26c6da', outline: '#26c6da' }
		},
		prompt: {
			main: { fill: '#2f3232', border: '#00838f', text: '#ffffff' },
			report: { fill: '#eb5757', border: '#eb5757', text: '#ffffff' }
		},
		skipButton: {
			main: { fill: '#555555', border: '#555555', text: '#fafafa' },
			hover: { fill: '#828282', border: '#828282', text: '#fafafa' },
			focus: { outline: '#80deea' }
		},
		verifyButton: {
			main: { fill: '#00838f', border: '#00838f', text: '#ffffff' },
			hover: { fill: '#00838f', border: '#00838f', text: '#ffffff' },
			focus: { outline: '#0074bf' }
		},
		slider: { main: { bar: '#4f4f4f', handle: '#80deea' }, focus: { handle: '#80deea' } },
		textarea: {
			main: { fill: '#4f4f4f', border: '#828282' },
			focus: { fill: '#4f4f4f', outline: '#80deea' },
			disabled: { fill: '#828282' }
		}
	}
};

/**
 * Whether the page is currently displayed in a dark color scheme. MediaWiki night
 * mode sets `skin-theme-clientpref-night` on <html>, or `skin-theme-clientpref-os`
 * to follow the OS (in which case `prefers-color-scheme` decides).
 *
 * @param {Window} win Reference to the browser window.
 * @return {boolean}
 */
function isDarkMode( win ) {
	const classList = win.document.documentElement.classList;
	if ( classList.contains( 'skin-theme-clientpref-night' ) ) {
		return true;
	}
	if ( classList.contains( 'skin-theme-clientpref-os' ) ) {
		return !!( win.matchMedia &&
			win.matchMedia( '(prefers-color-scheme: dark)' ).matches );
	}
	return false;
}

/**
 * Returns the custom dark theme object to pass to hcaptcha.render(). Passing an
 * object rather than the built-in `'dark'` string puts hCaptcha in custom-theme
 * mode, letting every surface be set explicitly.
 *
 * @return {Object}
 */
function getDarkThemeValue() {
	return wikimediaDarkTheme;
}

module.exports = {
	isDarkMode: isDarkMode,
	getDarkThemeValue: getDarkThemeValue
};
