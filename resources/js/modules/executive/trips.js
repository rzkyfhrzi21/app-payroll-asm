export default function registerExecutiveTrips(Alpine) {
    Alpine.data('executiveTrips', (rows = [], summary = {}, overview = {}) => ({
        search: '',
        driver: 'all',
        route: 'all',

        rowVisible(driver, sj, route) {
            const keyword = this.search.toLowerCase().trim();
            const matchesSearch = keyword === '' || String(sj).toLowerCase().includes(keyword);
            const matchesDriver = this.driver === 'all' || driver === this.driver;
            const matchesRoute = this.route === 'all' || route === this.route;

            return matchesSearch && matchesDriver && matchesRoute;
        },
    }));
}
