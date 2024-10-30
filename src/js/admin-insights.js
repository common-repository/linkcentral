import TotalClicks from './insights/total-clicks';
import MostPopularLinks from './insights/most-popular-links';
import RecentClicks from './insights/recent-clicks';

document.addEventListener('DOMContentLoaded', function() {
    const totalClicks = new TotalClicks();
    const mostPopularLinks = new MostPopularLinks();
    const recentClicks = new RecentClicks();

    totalClicks.init();
    mostPopularLinks.init();
    recentClicks.init();
});