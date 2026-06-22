import './bootstrap';
import { initializeMobileNavbar } from './modules/navbar';
import { initializeAlertDismissals } from './modules/alerts';
import { initializeDashboard } from './modules/dashboard';
import { initializePasswordToggle } from './modules/auth';
import { initializeBookingApp } from './modules/booking';
import { initializeBookingHistory } from './modules/booking_history';
import { initializeReschedule } from './modules/reschedule';
import { initializeCancelForm, initializeCancelReview } from './modules/cancel';

document.addEventListener('DOMContentLoaded', () => {
    initializeMobileNavbar();
    initializeAlertDismissals();
    initializeDashboard();
    initializePasswordToggle();
    initializeBookingApp();
    initializeBookingHistory();
    initializeReschedule();
    initializeCancelForm();
    initializeCancelReview();
});
