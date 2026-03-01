// Theme switching functionality for Taiga API
const themeToggle = document.getElementById('themeToggle');
const htmlElement = document.documentElement;

// Check for saved theme preference or respect OS preference
const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

// Set initial theme
htmlElement.setAttribute('data-bs-theme', savedTheme);
updateThemeIcon(savedTheme);

// Theme toggle event
if (themeToggle) {
	themeToggle.addEventListener('click', () => {
		const currentTheme = htmlElement.getAttribute('data-bs-theme');
		const newTheme = currentTheme === 'light' ? 'dark' : 'light';
		
		htmlElement.setAttribute('data-bs-theme', newTheme);
		localStorage.setItem('theme', newTheme);
		updateThemeIcon(newTheme);
	});
}

function updateThemeIcon(theme) {
	if (themeToggle) {
		const icon = themeToggle.querySelector('i');
		if (theme === 'dark') {
			icon.className = 'bi bi-moon-fill';
			themeToggle.classList.remove('btn-outline-secondary');
			themeToggle.classList.add('btn-outline-light');
		} else {
			icon.className = 'bi bi-sun-fill';
			themeToggle.classList.remove('btn-outline-light');
			themeToggle.classList.add('btn-outline-secondary');
		}
	}
}