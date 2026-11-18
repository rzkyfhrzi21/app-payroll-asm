export default function registerExecutiveTrips(Alpine) {
    Alpine.data('executiveTrips', (rows = [], summary = {}, overview = {}) => ({
        // Filter tabel kini diproses server-side (endpoint AJAX), bukan di sini.
    }));
}
