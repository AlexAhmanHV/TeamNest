import './bootstrap';

import Alpine from 'alpinejs';
import searchPalette from './search-palette';

window.Alpine = Alpine;

Alpine.data('searchPalette', searchPalette);

Alpine.start();
