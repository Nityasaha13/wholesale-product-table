import { render } from '@wordpress/element';
import Admin from "./Admin";
import Frontend from "./Frontend";
import Table from './components/frontend/Table';
import Certificate from './components/frontend/Certificate';

/**
 * Import the stylesheet for the plugin.
 */
import './style/main.scss';


const elementAdmin = document.getElementById('wptw-admin-settings');

if (elementAdmin) {
    render(<Admin />, elementAdmin);
}


const reactTable = document.getElementById('wpt-product-table');

if (reactTable) {
    render(<Table />, reactTable);
}







const elementFront = document.getElementById('react-frontend-root');

if (elementFront) {
    render(<Frontend />, elementFront);
}


const CirtifigateTable = document.getElementById('student-cirtifigate');

if (CirtifigateTable) {
    render(<Certificate />, CirtifigateTable);
}